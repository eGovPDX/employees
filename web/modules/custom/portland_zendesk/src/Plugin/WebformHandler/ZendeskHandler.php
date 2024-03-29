<?php
/**
 * Created by PhpStorm.
 * User: Steven
 * Date: 2019-05-18
 * Time: 10:05 AM
 */

namespace Drupal\portland_zendesk\Plugin\WebformHandler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\portland_zendesk\Client\ZendeskClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\file\Entity\File;
use Drupal\portland_zendesk\Utils\Utility;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;


/**
 * Form submission to Zendesk handler.
 *
 * @WebformHandler(
 *   id = "zendesk",
 *   label = @Translation("Zendesk new ticket"),
 *   category = @Translation("Zendesk"),
 *   description = @Translation("Sends a form submission to Zendesk to create a support ticket. This handler must fire after any other validation handlers, and it should not be used for forms that allow users to update their original submission. Updating will create a new Zendesk ticket, which is most likely not the desired behavior."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 *
 * Took inspiration from the following packages:
 * - incomplete Zendesk webform handler port from Drupal 7: https://git.drupalcode.org/sandbox/hanoii-2910895
 * - package synchronizing Drupal forms and Zendesk forms: https://git.drupalcode.org/project/zendesk_tickets
 *
 */
class ZendeskHandler extends WebformHandlerBase
{

  /**
   * @var WebformTokenManagerInterface $token_manager
   */
  protected $token_manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    /**
     * @var WebformTokenManagerInterface $webform_token_manager
     */

    $static = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $webform_token_manager = $container->get('webform.token_manager');
    $static->setTokenManager( $webform_token_manager );

    return $static;
  }

  /**
   * Provides list of setting fields
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return [
      'requester_name' => '',
      'requester_email' => '',
      'subject' => '',
      'comment' => '[webform_submission:values]', // by default lists all submission values as body
      'tags' => 'drupal webform',
      'priority' => 'normal',
      'status' => 'new',
      'group_id' => '',
      'assignee_id' => '',
      'type' => 'question',
      'collaborators' => '',
      'custom_fields' => '',
      'ticket_id_field' => '',
    ];
  }

  /**
   * @return array
   */
  public function defaultConfigurationNames()
  {
    return array_keys( $this->defaultConfiguration() );
  }

  /**
   * Provide settings fields for configuration
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {

    $webform_fields = $this->getWebform()->getElementsDecoded();
    $zendesk_subdomain = \Drupal::config('portland_zendesk.adminsettings')->get('subdomain');
    $options = [
      'email' => [''],
      'name' => [''],
      'hidden' => [''],
    ];
    $form_ticket_fields = [];
    $form_field_exclusions = [
      'Subject',
      'Description',
      'Status',
      'Type',
      'Priority',
      'Group',
      'Assignee',
    ];

      // get available email fields to use as requester email address
      foreach($webform_fields as $key => $field){
        if( Utility::checkIsGroupingField($field) ){
          foreach($field as $subkey => $subfield){
            if(!preg_match("/^#/",$subkey) && isset($subfield['#type'])) {
              if (Utility::checkIsEmailField($subfield)) {
                $options['email'][$subkey] = $subfield['#title'];
              }
              elseif (Utility::checkIsNameField($subfield)) {
                $options['name'][$subkey] = $subfield['#title'];
              }
              elseif (Utility::checkIsHiddenField($subfield)) {
                $options['hidden'][$subkey] = $subfield['#title'];
              }
            }
          }
        }
        else{
          if( Utility::checkIsEmailField($field) ){
            $options['email'][$key] = $field['#title'];
          }
          elseif( Utility::checkIsNameField($field) ){
            $options['name'][$key] = $field['#title'];
          }
          elseif( Utility::checkIsHiddenField($field) ){
            $options['hidden'][$key] = $field['#title'];
          }
        }
      }

      $assignees = [];
      $groups = [];

      try {
        // Get available groups and assignees from zendesk.
        // NOTE: Typically we don't want to use individual users here, only groups.
        // Individual users shouldn't be stored in config, which has to be deployed,
        // in case there is an urgent change required. However, if tickets are to be
        // creatd as Solved, they need to have an individual assignee. Using the
        // service account would be acceptable and necessary in this case.
        
        $client = new ZendeskClient();

        // get list of all groups
        $response_groups = $client->groups()->findAll();
        // store found groups
        foreach($response_groups->groups as $group){
          $groups[ $group->id ] = $group->name;
        }
        // order groups by name
        asort($groups);

        // get list of all admin and agent users to populate assignee field
        $response_agents = $client->users()->findAll([ 'role' => 'agent' ]);
        $response_admins = $client->users()->findAll([ 'role' => 'admin' ]);
        $users = array_merge( $response_agents->users, $response_admins->users );

        // store found agents
        foreach($users as $user){
          $assignees[ $user->id ] = $user->name;
        }

        // order agents by name
        asort($assignees);

        // get list of ticket fields and assign them to an array by id->title
        $response_fields = $client->ticketFields()->findAll();

        if( $response_fields->ticket_fields ) {
            foreach($response_fields->ticket_fields as $field) {
                // exclude system ticket fields and inactive fields
                if( !in_array($field->title,$form_field_exclusions) && $field->active ) {
                    $form_ticket_fields[$field->id] = $field->title;
                }
            }
        }

        // order ticket fields by name
        asort($form_ticket_fields);

      }
      catch( \Exception $e ){
        // Encode HTML entities to prevent broken markup from breaking the page.
        $message = nl2br(htmlentities($e->getMessage()));

        // Log error message.
        $this->getLogger()->error('Retrieval of groups or assignees for @form webform Zendesk handler failed. @exception: @message. Click to edit @link.', [
          '@exception' => get_class($e),
          '@form' => $this->getWebform()->label(),
          '@message' => $message,
          'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
        ]);
      }

      // build form fields

      $form['requester_name'] = [
        '#type' => 'webform_select_other',
        '#title' => $this->t('Requester name'),
        '#description' => $this->t('The name of the user who requested this ticket. Select from available name fields, or specify a name.'),
        '#default_value' => $this->configuration['requester_name'],
        '#options' => $options['name'],
        '#required' => false
      ];

      $form['requester_email'] = [
        '#type' => 'webform_select_other',
        '#title' => $this->t('Requester email address'),
        '#description' => $this->t('The email address of user who requested this ticket. Select from available email fields, or specify an email address.'),
        '#default_value' => $this->configuration['requester_email'],
        '#options' => $options['email'],
        '#required' => true
      ];

      $form['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#description' => $this->t('The value of the subject field for this ticket'),
        '#default_value' => $this->configuration['subject'],
        '#required' => true
      ];

      $form['comment'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Ticket Body'),
        '#description' => $this->t('The initial comment/message of the ticket.'),
        '#default_value' => $this->configuration['comment'],
        '#format' => '',
        '#required' => true
      ];

      $form['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Ticket Type'),
        '#description' => $this->t('The type of this ticket. Possible values: "problem", "incident", "question" or "task".'),
        '#default_value' => $this->configuration['type'],
        '#options' => [
          'question' => 'Question',
          'incident' => 'Incident',
          'problem' => 'Problem',
          'task' => 'Task'
        ],
        '#required' => false
      ];

      // space separated tags
      $form['tags'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Ticket Tags'),
        '#description' => $this->t('The list of tags applied to this ticket.'),
        '#default_value' => $this->configuration['tags'],
        '#multiple' => true,
        '#required' => false
      ];

      $form['priority'] = [
        '#type' => 'select',
        '#title' => $this->t('Ticket Priority'),
        '#description' => $this->t('The urgency with which the ticket should be addressed. Possible values: "urgent", "high", "normal", "low".'),
        '#default_value' => $this->configuration['priority'],
        '#options' => [
          'low' => 'Low',
          'normal' => 'Normal',
          'high' => 'High',
          'urgent' => 'Urgent'
        ],
        '#required' => false
      ];

      $form['status'] = [
        '#type' => 'select',
        '#title' => $this->t('Ticket Status'),
        '#description' => $this->t('The state of the ticket. Possible values: "new", "open", "pending", "hold", "solved", "closed".'),
        '#default_value' => $this->configuration['status'],
        '#options' => [
          'new' => 'New',
          'open' => 'Open',
          'pending' => 'Pending',
          'hold' => 'Hold',
          'solved' => 'Solved',
          'closed' => 'Closed'
        ],
        '#required' => false
      ];

      // prep groups field
      // if found groups from Zendesk, populate dropdown.
      $form['group_id'] = [
        '#title' => $this->t('Ticket Group'),
        '#description' => $this->t('The id of the intended group'),
        '#default_value' => $this->configuration['group_id'],
        '#required' => false
      ];
      if(!empty($groups) ){
        $form['group_id']['#type'] = 'select';
        $form['group_id']['#options'] = ['' => '-- None --'] + $groups;
        $form['group_id']['#description'] = $this->t('The group to which the ticket should be assigned. Set either Ticket Group or Ticket Assignee, but not both.');
      }

      // prep assignees field
      // if found assignees from Zendesk, populate dropdown.
      // otherwise provide field to specify assignee ID
      $form['assignee_id'] = [
        '#title' => $this->t('Ticket Assignee'),
        '#description' => $this->t('The id of the intended assignee'),
        '#default_value' => $this->configuration['assignee_id'],
        '#required' => false
      ];
      if(! empty($assignees) ){
        $form['assignee_id']['#type'] = 'webform_select_other';
        $form['assignee_id']['#options'] = ['' => '-- None --'] + $assignees;
        $form['assignee_id']['#description'] = $this->t('The assignee to which the ticket should be assigned. Set either Ticket Group or Ticket Assignee, but not both. Typically tickets created by webforms should not be assigned to individual users, but tickets that are created as Solved must have an individual assignee. In this case, use the Portland.gov Support service account.');
      }
      else {
        $form['assignee_id']['#type'] = 'textfield';
        $form['assignee_id']['#attribute'] = [
          'type' => 'number'
        ];
      }

      $form['collaborators'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Ticket CCs'),
        '#description' => $this->t('Users to add as cc\'s when creating a ticket.'),
        '#default_value' => $this->configuration['collaborators'],
        '#multiple' => true,
        '#required' => false
      ];

      $form['custom_fields'] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('Ticket Custom Fields'),
        '#help' => $this->t('Custom form fields for the ticket'),
        '#description' => $this->t(
          '<div id="help">To set the value of one or more custom fields in the new Zendesk ticket, in <a href="https://learn.getgrav.org/16/advanced/yaml#mappings" target="_blank">YAML format</a>, specify a list of pairs consisting of IDs and values. You may find the custom field ID when viewing the list of <a href="https://'.$zendesk_subdomain.'.zendesk.com/agent/admin/ticket_fields" target="_blank">Ticket Fields</a> in Zendesk, or by clicking "<strong>Field Reference</strong>" below for a list of available fields. Values may be plain text, or Drupal webform tokens/placeholders. <p class="">Eg. <code class="CodeMirror"><span>12345678</span>: <span>\'foobar\'</span></code></p> </div>'
        ),
        '#default_value' => $this->configuration['custom_fields'],
        '#description_display' => 'before',
        '#weight' => 90,
        '#attributes' => [
          'placeholder' => '146455678: \'[webform_submission:value:email]\''
        ],
        '#required' => false,
        '#more_title' => 'Field Reference',
        '#more' => '<div class="zd-ticket-reference">' . Utility::convertTable($form_ticket_fields) .'</div>',
      ];

      // display link for token variables
      $form['token_link'] = $this->getTokenManager()->buildTreeLink();

      $form['ticket_id_field'] = [
        '#type' => 'webform_select_other',
        '#title' => $this->t('Zendesk Ticket ID Field'),
        '#description' => $this->t('The name of hidden field which will be updated with the created Ticket ID.'),
        '#default_value' => $this->configuration['ticket_id_field'],
        '#options' => $options['hidden'],
        '#required' => false
      ];

      return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * Saves handler settings to config
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitConfigurationForm($form, $form_state);

    $submission_value = $form_state->getValues();
    foreach($this->configuration as $key => $value){
      if(isset($submission_value[$key])){
        $this->configuration[$key] = $submission_value[$key];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // the file upload button triggers the validation handler, which is undesired.
    // in order to prevent that, we need to determine the triggering element for the submission.
    // call our validation function only if it's not an upload button. be extra careful about
    // null references here, since we're not sure when/if these elements will be populated.
    // Candiates for checking whether this is an upload submit:
    //    $form_state->getTriggeringElement()['#submit'][0] == "file_managed_file_submit"
    //    $form_state->getTriggeringElement()['#value']->getUntranslatedString() == "Uplooad"
    if ($form_state->getTriggeringElement() && $form_state->getTriggeringElement()['#value'] === "Submit") {
      $this->sendToZendeskAndValidateTicket($form, $form_state);
    }
  }

  /**
   * Submit report to Zendesk API and validate the ticket was successfully created.
   * 
   * By submitting to the API during the validate phase, we can interrupt the form submission,
   * prevent the email handlers from firing, and display an error message to the user. Validation
   * in a custom handler is performed after all the built-in webform validation, so this is a
   * safe approach.
   */
  private function sendToZendeskAndValidateTicket(array &$form, FormStateInterface $form_state) {
    if (!$form_state->hasAnyErrors()) {
      // comment out the line below to test the error handling
      $ticket_id = $this->sendToZendesk($form, $form_state);
      if (!$ticket_id) {
        // throw error and don't let form submit
        \Drupal::messenger()->addError(t('There was a problem submitting your report to our support system. Please try again in a few minutes. If the error persists, please <a href="/feedback?subject=The page looks broken&feedback=Report could not be submitted to the support ticketing system.">contact us</a>.'));
        $form_state->setErrorByName('');
      }
    }
  }

  public function sendToZendesk(array &$form, FormStateInterface &$form_state)
  {
    // NOTE: This will run for both new and update webform submissions, so this handler should only
    // be used on forms that don't allow updating. Otherwise, a new Zendesk ticket will be created
    // on every submit of the form.

    // Since we're doing this in the validate phase, instead of postSave, we need to manually generate
    // a webform_submission object from form_state and pull form values from that for the API submission.

    // declare working variables
    $new_ticket_id = 0;
    $request = [];
    $webform_submission = $form_state->getFormObject()->getEntity();
    $submission_fields = $webform_submission->toArray(TRUE);
    $configuration = $this->getTokenManager()->replace($this->configuration, $webform_submission);

    // Allow for either values coming from other fields or static/tokens
    foreach ($this->defaultConfigurationNames() as $field) {
      $request[$field] = $configuration[$field];
      if (!empty($submission_fields['data'][$configuration[$field]])) {
        $request[$field] = $submission_fields['data'][$configuration[$field]];
      }
    }

    // clean up tags
    $request['tags'] = Utility::cleanTags( $request['tags'] );
    $request['collaborators'] = preg_split("/[^a-z0-9_\-@\.']+/i", $request['collaborators'] );

    // restructure requester
    if(!isset($request['requester'])){
      $request['requester'] = $request['requester_name']
        ? [
          'name' => Utility::convertName($request['requester_name']),
          'email' => $request['requester_email'],
        ]
        : $request['requester_email'];

      unset($request['requester_name']);
      unset($request['requester_email']);
    }

    // restructure comment array
    if(!isset($request['comment']['body'])){
      $comment = $request['comment'];
      $request['comment'] = [
        'html_body' => $comment
      ];
    }

    // convert custom fields format from [key:data} to [id:key,value:data] for Zendesk field referencing
    $custom_fields = Yaml::decode($request['custom_fields']);
    unset($request['custom_fields']);
    $request['custom_fields'] = [];
    if($custom_fields) {
      foreach ($custom_fields as $key => $value) {
        $request['custom_fields'][] = [
          'id' => $key,
          'value' => $value
        ];
      }
    }

    // set external_id to connect zendesk ticket with submission ID
    $request['external_id'] = $webform_submission->id();

    // get list of all webform fields with a file field type
    $file_fields = $this->getWebformFieldsWithFiles();

    // attempt to send request to create zendesk ticket
    try {
      // initiate api client
      $client = new ZendeskClient();

      // Checks for files in submission values and uploads them if found
      foreach($submission_fields['data'] as $key => $submission_field){
        if( in_array($key, $file_fields) && !empty($submission_field) ){

          // pack file index/indices into an array for looping
          if( is_array( $submission_field ) ){
              $file_indices = $submission_field;
          } else {
              $file_indices = []; // clear var
              $file_indices[] = $submission_field;
          }

          // individually attach each uploaded file per file submission_field
          foreach( $file_indices as $file_index) {
            // get file from index for upload
            $file = File::load($file_index);

            // add uploads key to Zendesk comment, if not already present
            if ($file && !array_key_exists('uploads', $request['comment'])) {
              $request['comment']['uploads'] = [];
            }

            // upload file and get response
            $attachment = $client->attachments()->upload([
              'file' => $file->getFileUri(),
              'type' => $file->getMimeType(),
              'name' => $file->getFileName(),
            ]);

            // add upload token to comment
            if ($attachment && isset($attachment->upload->token)) {
              $request['comment']['uploads'][] = $attachment->upload->token;
            }
          }
        }
      }

      // create ticket
      $new_ticket = $client->tickets()->create($request);

      // retrieve the name of the field in which to store the created Zendesk Ticket ID
      $zendesk_ticket_id_field_name = $configuration['ticket_id_field'];
      
      // retrieve submission data
      $data = $webform_submission->getData();

      $new_ticket_id = $new_ticket->ticket->id;

      // if name field is set and present, add ticket ID to hidden Zendesk Ticket ID field
      if ($zendesk_ticket_id_field_name && array_key_exists( $zendesk_ticket_id_field_name, $data ) && $new_ticket){
        $new_ticket_id = $new_ticket->ticket->id;
        $data[$zendesk_ticket_id_field_name] = $new_ticket_id;
        $form_state->setValue($zendesk_ticket_id_field_name, $new_ticket_id);
        $form['values'][$zendesk_ticket_id_field_name] = $new_ticket_id;
      }

    }
    catch( \Exception $e ){

      // Encode HTML entities to prevent broken markup from breaking the page.
      $message = nl2br(htmlentities($e->getMessage()));

      // Log error message.
      $this->getLogger()->error('@form webform submission to zendesk failed. @exception: @message. Click to edit @link.', [
        '@exception' => get_class($e),
        '@form' => $this->getWebform()->label(),
        '@message' => $message,
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
      ]);
    }

    return $new_ticket_id;
  }

  /**
   * Displays a list of configured values on the Handlers page
   * {@inheritdoc}
   */
  public function getSummary()
  {
    $markup = [];
    $configNames = array_keys($this->defaultConfiguration());
    $excluded_fields = ['comment','custom_fields'];

    // loop through fields to display an at-a-glance summary of settings
    foreach($configNames as $configName){
      if(! in_array($configName, $excluded_fields) ) {
        $markup[] = '<strong>' . $this->t($configName) . ': </strong>' . ($this->configuration[$configName]);
      }
    }

    return [
      '#markup' => implode('<br>',$markup),
    ];
  }

  /**
   * Token manager setter
   * @param WebformTokenManagerInterface $token_manager
   */
  public function setTokenManager( WebformTokenManagerInterface $token_manager ){
    $this->token_manager = $token_manager;
  }

  /**
   * Token manager getter
   * @return WebformTokenManagerInterface
   */
  public function getTokenManager(){
    return $this->token_manager;
  }

  /**
   * @return array
   */
  protected function getWebformFieldsWithFiles(){
    return $this->getWebform()->getElementsManagedFiles();
  }

  // Deprecated functions

  /**
   * @param array $field
   * @return bool
   * @deprecated 
   */
  protected function checkIsNameField( array $field ){
    return Utility::checkIsNameField($field);
  }

  /**
   * @param array $field
   * @return bool
   * @deprecated
   */
  protected function checkIsEmailField( array $field ){
    return Utility::checkIsEmailField($field);
  }

  /**
   * @param array $field
   * @return bool
   * @deprecated
   */
  protected function checkIsHiddenField( array $field ){
    return Utility::checkIsHiddenField($field);
  }

  /**
   * @param array $field
   * @return bool
   * @deprecated
   */
  protected function checkIsGroupingField( array $field ){
    return Utility::checkIsGroupingField($field);
  }

  /**
   * @param string $text
   * @return string
   * @deprecated
   */
  protected function cleanTags( $text = '' ){
    return Utility::cleanTags($text);
  }

  /**
   * @param string $text
   * @return string
   * @deprecated
   */
  protected function convertTags( $text = '' ){
    return Utility::convertTags($text);
  }

  /**
   * @param string $text
   * @return string
   * @deprecated
   */
  protected function convertName( $name_parts ){
    return Utility::convertName($name_parts);
  }

  /**
   * @param array $text
   * @return string
   * @deprecated
   */
  protected function convertTable( $set ){
    return Utility::convertTable($set);
  }
}
