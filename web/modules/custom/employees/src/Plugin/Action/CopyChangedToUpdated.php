<?php

namespace Drupal\employees\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Copy Changed date to Updated On.
 *
 * @Action(
 *   id = "employees_copy_change_to_updated",
 *   label = @Translation("Copy Changed date to Updated On date (custom action)"),
 *   type = "",
 *   confirm = FALSE,
 * )
 */
class CopyChangedToUpdated extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    try{
      \Drupal::logger('php')->notice('Start copying dates for ' . $entity->id());

    // Only process entity that has "field_updated_on"
    if(! $entity->hasField('field_updated_on')) return $this->t('Bulk operation: the entity does not have an Updated On field');

    $entity->field_updated_on->value = \Drupal::service('date.formatter')->format($entity->changed->value, 'local_datetime', '', 'UTC');
    $entity->save();

    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Bulk operation: copied date from Changed to Updated On field');

  } catch (Exception $e) {
    \Drupal::logger('php')->error('Error saving entity ' . $entity->id() . ": " . $e->getTraceAsString());
  }
  \Drupal::logger('php')->notice('Done copying dates for ' . $entity->id());

  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityTypeId() === 'node' || $object->getEntityTypeId() === 'media') {
      $access = $object->access('update', $account, TRUE)->andIf($object->access('edit', $account, TRUE));
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different
    // access methods and properties.
    return ($return_as_object ? AccessResult::allowed() : true );
  }
}
