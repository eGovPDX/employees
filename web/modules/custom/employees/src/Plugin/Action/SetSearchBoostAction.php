<?php

namespace Drupal\employees\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Some description.
 *
 * @Action(
 *   id = "employees_set_search_boost_action",
 *   label = @Translation("Set search boost (custom action)"),
 *   type = "",
 *   confirm = FALSE,
 * )
 */
class SetSearchBoostAction extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Get current user's display name
    $user_display_name = \Drupal::currentUser()->getDisplayName();
    if(!$entity->hasField('field_search_boost'))
      return $this->t('Does not have field_search_boost.');

    $new_search_boost_value = $this->configuration['search_boost'];
    $entity->field_search_boost->value = $new_search_boost_value;
    $entity->save();

    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t("Bulk operation: search boost set to " . $new_search_boost_value . " by ". $user_display_name);
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
  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['search_boost'] = [
      '#title' => $this->t('New search boost value'),
      '#description' => $this->t('A float number between 0 and 100 (max 4 digits after the decimal). Default to 1. Make it greater than 1 to increase search ranking or less than 1 to reduce it. If the boost is 0, the result will be filtered out.'),
      '#type' => 'number',
      '#min' => '0',
      '#max' => '100',
      '#step' => 0.00001,
      '#default_value' => 1, // Default value is 1
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['search_boost'] = $form_state->getValue('search_boost');
  }
}
