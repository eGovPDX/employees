<?php

namespace Drupal\portland_openid_connect\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Update the user's primary groups to match the AD group names.
 *
 * @Action(
 *   id = "user_update_user_primary_groups",
 *   label = @Translation("Update primary groups for the selected users"),
 *   type = "user"
 * )
 */
class UpdateUserPrimaryGroups extends ActionBase
{
  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL)
  {
    UpdateUserPrimaryGroups::updatePrimaryGroupsForUser($account);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Helper function to add a user to a group as roles
   */
  public static function addUserToGroupWithRoles($account, $group_id, $role_id_array)
  {
    $group = \Drupal\group\Entity\Group::load($group_id);
    $membership = $group->getMember($account);
    // The user is NOT in the group
    if (empty($membership)) {
      $group->addMember($account, ['group_roles' => $role_id_array]);
      $group->save();
    }
    // The user is already in the group, check if she has all roles specified in $role_id_array
    else {
      // https://drupal.stackexchange.com/questions/232530/programmatically-add-new-role-to-group-member/232646#232646
      // Array of Role-name=>Role_entity
      $roles = $membership->getRoles();
      $group_content = $membership->getGroupContent();
      $has_new_role = false;
      foreach ($role_id_array as $role_id) {
        if (!isset($roles[$role_id])) {
          $group_content->group_roles->appendItem(['target_id' => $role_id]);
          $has_new_role = true;
        }
      }
      if( $has_new_role ) $group_content->save();
    }
  }

  /**
   * Helper function to remove a user from a group
   */
  public static function removeUserFromGroup($account, $group_id)
  {
    $group = \Drupal\group\Entity\Group::load($group_id);
    $group->removeMember($account);
    $group->save();
  }

  /**
   * Helper function to remove a user from a group.
   * Will be called by User post-save hooks and this view bulk operation.
   */
  public static function updatePrimaryGroupsForUser($account) {
    if(empty($account)) return;
    // A comma separated string
    $group_names = $account->field_group_names->value;
    // Array of unique group names
    $primary_group_names = [];
    $new_primary_group_ids = [];
    if (!empty($group_names)) {
      // Deduplicate group names
      if (strpos($group_names, ',') !== false) {
        $group_name_array = array_map('trim', explode(',', $account->field_group_names->value));
        $primary_group_names = array_unique($group_name_array);
      } else {
        $primary_group_names = [$group_names];
      }
    }

    // Build new primary group ID array with primary group names
    $group_ad_name_list = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadTree('group_ad_name_list', 0, 1, true);
    $new_primary_group_ids = [];
    foreach ($group_ad_name_list as $group_ad_name) {
      if (in_array($group_ad_name->name->value, $primary_group_names)) {
        $drupal_groups = $group_ad_name->field_employee_groups->getValue();
        foreach ($drupal_groups as $drupal_group) {
          $new_primary_group_ids[] = $drupal_group['target_id'];
        }
      }
    }
    // Remove duplicates
    $new_primary_group_ids = array_unique($new_primary_group_ids);

    // Build current primary group ID array
    $current_primary_group_ids = array_map(
      function($item) { return $item['target_id']; },
      $account->field_primary_groups->getValue()
    );

    // Check the new and current group ID arrays and update the user's groups
    if (empty($new_primary_group_ids)) {
      // Remove user from all current groups
      foreach ($current_primary_group_ids as $current_primary_group_id) {
        UpdateUserPrimaryGroups::removeUserFromGroup($account, $current_primary_group_id);
      }
    } else {
      // For any added group, add membership
      foreach ($new_primary_group_ids as $new_primary_group_id) {
        UpdateUserPrimaryGroups::addUserToGroupWithRoles($account, $new_primary_group_id, ['employee-employee']);
      }

      // Check if any current group is not in the new group list
      foreach ($current_primary_group_ids as $current_primary_group_id) {
        if (in_array($current_primary_group_id, $new_primary_group_ids))
          continue;
        // Remove user from the new group
        UpdateUserPrimaryGroups::removeUserFromGroup($account, $current_primary_group_id);
      }
    }
  }
}
