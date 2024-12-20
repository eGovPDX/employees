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
 *   id = "employees_change_contact_action",
 *   label = @Translation("Change contact (custom action)"),
 *   type = "node",
 *   confirm = FALSE,
 * )
 */
class ChangeContactAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity === NULL || !$entity->hasField('field_contact')) {
      return $this->t('Invalid entity or configuration.');
    }
  
    $contacts = $entity->get('field_contact');
    
    // Look through all contacts to find the old contact and replace it with the new contact
    foreach ($contacts as $contact) {
      $user_id = $contact->getValue()['target_id'];
      if ($user_id == $this->configuration['old_user_id']) {
        $contact->setValue($this->configuration['new_user_id']);

        $entity->save();
        return $this->t('Contact successfully changed.');
      }
    }
  }


  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $from_user_id = $this->context['arguments'][0];
    $from_first_name = \Drupal::entityTypeManager()->getStorage('user')->load($from_user_id)->get('field_first_name')->value;
    $from_last_name = \Drupal::entityTypeManager()->getStorage('user')->load($from_user_id)->get('field_last_name')->value;

    $form['from_username'] = [
      '#type' => 'markup',
      '#markup' => '<p><strong>Change from:</strong> ' . "$from_first_name $from_last_name" . '</p>',
    ];

    $form['old_user_id'] = [
      '#type' => 'hidden',
      '#value' => $from_user_id,
    ];

    $form['new_user_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Change to'),
      '#placeholder' => $this->t('Enter user name'),
      '#target_type' => 'user',
      // '#selection_settings' => ['filter' => ['access' => 'administer members']],
      '#required' => TRUE,
      '#tags' => FALSE,
      // '#description' => $this->t('Use a comma to select multiple users.'),
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('edit', $account, $return_as_object);
  }
}
