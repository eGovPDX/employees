<?php

namespace Drupal\employees\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Unpublish content
 *
 * @Action(
 *   id = "employees_unpublish_action",
 *   label = @Translation("Unpublish content (custom action)"),
 *   type = "",
 *   confirm = FALSE,
 * )
 */
class UnpublishAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {

    // Get current user's display name
    $user_display_name = \Drupal::currentUser()->getDisplayName();
    $entity->status->value = 0;
    $entity->moderation_state->value="unpublished";
    $entity->save();
    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Bulk operation: unpublished by '. $user_display_name);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityTypeId() === 'node' || $object->getEntityTypeId() === 'media') {
      $moderation_info = \Drupal::service('content_moderation.moderation_information');

      // For moderated content, edit access to the entity is not enough. 
      // Users should also have permission to the proper workflow transition to unpublish moderated content.
      if ($moderation_info->isModeratedEntity($object)) {
        // Find the workflow transition to unpublished state and get the workflow ID and transition ID
        // so we can build the permission name to check.
        $workflow_id = $moderation_info->getWorkflowForEntity($object)->get('id');
        $transitions = $moderation_info->getWorkflowForEntity($object)->get('type_settings')['transitions'];
        $transition_id = null;
        foreach ($transitions as $key => $transition) {
          if ($transition['to'] == 'unpublished') {
            $transition_id = $key;
            break;
          }
        }
        if ($transition_id == null) {
          // No transition to unpublished state found.
          return ($return_as_object ? AccessResult::forbidden() : false );
        }

        // The workflow transition permission is named like "use editorial transition unpublish"
        $access = $object->access('update', $account, TRUE)
          ->andIf(AccessResult::allowedIfHasPermission($account, "use $workflow_id transition $transition_id"));
      } else {
        $access = $object->access('update', $account, TRUE);
      }
      
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different access methods and properties.
    return ($return_as_object ? AccessResult::allowed() : true );
  }
}
