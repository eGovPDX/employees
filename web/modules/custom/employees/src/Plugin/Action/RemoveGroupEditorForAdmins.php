<?php

namespace Drupal\employees\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\portland_openid_connect\Util\PortlandOpenIdConnectUtil;
use Drupal\Core\Access\AccessResult;

/**
 * Remove the group editor role when a user is a group admin
 *
 * @Action(
 *   id = "remove_group_editor_for_admins",
 *   label = @Translation("Remove editor role for admins (custom action)"),
 *   type = "user",
 *   confirm = FALSE,
 * )
 */
class RemoveGroupEditorForAdmins extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $account = $entity;
    // Get all groups the user belongs to
    $current_primary_group_ids = PortlandOpenIdConnectUtil::getGroupIdsOfUser($account);

    // Iterate through all groups
    foreach ($current_primary_group_ids as $current_primary_group_id) {
      $group = \Drupal\group\Entity\Group::load($current_primary_group_id);
      $group_type = $group->type->entity->id();
      $membership = $group->getMember($account);
      $group_content = $membership->getGroupContent();

      $roles = $membership->getRoles();
      $role_ids = array_keys($membership->getRoles());

      $has_both_editor_and_admin_role = (
        ( in_array('employee-editor', $role_ids) && in_array('employee-admin', $role_ids) ) ||
        ( in_array('private-editor', $role_ids) && in_array('private-admin', $role_ids) )
      );
      if($has_both_editor_and_admin_role) {
        // Filter out the Editor role
        $group_content->group_roles = [];
        foreach ($roles as $role) {
          if ($role->id() === 'employee-editor' || $role->id() === 'private-editor') {
            continue;
          }
          $group_content->group_roles->appendItem(['target_id' => $role->id()]);
        }
        $group_content->save();
      }
    }

    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Bulk operation: Editor role removed for admins');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return ($return_as_object ? AccessResult::allowed() : true );
  }
}
