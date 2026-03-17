<?php

namespace Drupal\employees\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Publish content
 *
 * @Action(
 *   id = "employees_remove_contact_action",
 *   label = @Translation("Remove as contact (custom action)"),
 *   type = "node",
 *   confirm = FALSE,
 * )
 */
class RemoveContactAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity === NULL || !$entity->hasField('field_contact')) {
      return $this->t('Invalid entity or configuration.');
    }
  
    $contacts = $entity->get('field_contact');
    
    // Look through all contacts to find the right contact and remove it
    foreach ($contacts as $contact) {
      $user_id = $contact->getValue()['target_id'];
      if ($user_id == $this->configuration['user_id']) {
        $contacts->removeItem($contact->getName());

        // Get the first and last name of the contact for the revision log message
        $user = \Drupal::entityTypeManager()->getStorage('user')->load($this->configuration['user_id']);
        $user_first_name = $user->get('field_first_name')->value;
        $user_last_name = $user->get('field_last_name')->value;
        
        // Make this change a new revision
        if($entity->hasField('revision_log'))
          $entity->revision_log = "Bulk operation: Removed contact $user_first_name $user_last_name";
        $entity->setNewRevision(TRUE);
        $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
        $entity->setRevisionUserId(\Drupal::currentUser()->id());

        $entity->save();
        return $this->t('Contact successfully removed.');
      }
    }
  }


  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $user_id = $this->context['arguments'][0];
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($user_id);
    $first_name = $user->get('field_first_name')->value;
    $last_name = $user->get('field_last_name')->value;

    $form['from_username'] = [
      '#type' => 'markup',
      '#markup' => "<p><strong>$first_name $last_name</strong> will be removed as a contact from the selected items.</p>",
    ];

    $form['user_id'] = [
      '#type' => 'hidden',
      '#value' => $user_id,
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('edit', $account, $return_as_object);
  }
}
