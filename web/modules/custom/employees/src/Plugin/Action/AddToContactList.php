<?php

namespace Drupal\employees\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Add a User or Contact to a Contact List
 *
 * @Action(
 *   id = "employees_add_to_contact_list",
 *   label = @Translation("Add a User or Contact to a Contact List (custom action)"),
 *   type = "",
 *   confirm = FALSE,
 * )
 */
class AddToContactList extends ViewsBulkOperationsActionBase implements PluginFormInterface
{

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL)
  {
    // Check if the entity is already in the contact list
    $input_contact_list_id = $this->configuration['contact_list_id'];
    $already_in_list = false;
    foreach($entity->field_parent_contact_lists as $parent_contact_list) {
      if($parent_contact_list->target_id == $input_contact_list_id) {
        $already_in_list = true;
        break;
      }
    }
    if($already_in_list) return $this->t('Already in the contact list');

    $entity->field_parent_contact_lists[] = $input_contact_list_id;
    $entity->save();
    return $this->t('Added to contact list');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    // Get all contact list of the current user
    $currentUser = \Drupal::currentUser();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $contact_lists = $node_storage->loadByProperties([
      'type' => 'contact_list',
      'uid' => $currentUser->id(),
    ]);

    $options = [0 => '- None -'];
    foreach ($contact_lists as $contact_list) {
      $options[$contact_list->id()] = $contact_list->title->value;
    }

    $form['existing_contact_list'] = [
      '#title' => t('Select a contact list'),
      '#type' => 'select',
      '#default_value' => null,
      '#options' => $options,
      '#description' => t('Add content to an existing contact list'),
    ];

    $form['new_contact_list_title'] = [
      '#title' => t('New contact list title'),
      '#type' => 'textfield',
      '#size' => 20,
      '#description' => t('Add content to a new contact list'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    if (
      empty($form_state->getValue('new_contact_list_title')) &&
      $form_state->getValue('existing_contact_list') == 0
    ) {
      $form_state->setErrorByName('existing_contact_list', t("Please select a contact list or provide a new list title"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    $new_contact_list_title = $form_state->getValue('new_contact_list_title');
    $existing_contact_list_id = $form_state->getValue('existing_contact_list');
    // Ignore selected existing contact list if the new contact list title is NOT empty
    if (!empty($form_state->getValue('new_contact_list_title'))) {
      // Create a new contact list and get the ID
      $node_array = [
        'type' => 'contact_list',
        'title' => $new_contact_list_title,
      ];
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $node = $node_storage->create($node_array);
      $node->save();
      $this->configuration['contact_list_id'] = $node->id();
    } else {
      $this->configuration['contact_list_id'] = $existing_contact_list_id;
    }
  }
}
