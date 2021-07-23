<?php

namespace Drupal\portland_openid_connect\Util;

class PortlandOpenIdConnectUtil
{
  /**
   * Helper function to remove a user from a group
   */
  public static function removeUserFromGroup($account, $group_id)
  {
    // Automated removal should only remove the "Employee" role
    $group = \Drupal\group\Entity\Group::load($group_id);
    $membership = $group->getMember($account);
    if (empty($membership)) return;

    $roles = $membership->getRoles();
    $has_employee_role = false;
    $group_content = $membership->getGroupContent();
    $group_content->group_roles = [];
    foreach ($roles as $role) {
      if ($role->id() === 'employee-employee') {
        $has_employee_role = true;
        continue;
      }
      // Remove the "member" role
      else if($role->id() === 'employee-member') {
        $has_employee_role = true;
        continue;
      }
      $group_content->group_roles->appendItem(['target_id' => $role->id()]);
    }
    if ($has_employee_role) $group_content->save();    

    // $group = \Drupal\group\Entity\Group::load($group_id);
    // $group->removeMember($account);
    // $group->save();
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
        // Check if the user has the new role
        if (!isset($roles[$role_id])) {
          $group_content->group_roles->appendItem(['target_id' => $role_id]);
          $has_new_role = true;
        }
      }
      if ($has_new_role) $group_content->save();
    }
  }

  /**
   * Helper function to remove a user from a group.
   * Will be called by User post-save hooks and this view bulk operation.
   */
  public static function updatePrimaryGroupsForUser($account)
  {
    if (empty($account)) return;

    $new_primary_group_ids = PortlandOpenIdConnectUtil::buildGroupIDlistFromGroupNames($account->field_group_names->value);

    if ($account->isNew()) {
      $current_primary_group_ids = [];
    } else {
      $current_primary_group_ids = PortlandOpenIdConnectUtil::getGroupIdsOfUser($account);
    }

    if (empty($new_primary_group_ids)) {
      // Remove user from all current groups
      foreach ($current_primary_group_ids as $current_primary_group_id) {
        PortlandOpenIdConnectUtil::removeUserFromGroup($account, $current_primary_group_id);
      }
    } else if (empty($current_primary_group_ids)) {
      // Remove user from all current groups
      foreach ($new_primary_group_ids as $new_primary_group_id) {
        PortlandOpenIdConnectUtil::addUserToGroupWithRoles($account, $new_primary_group_id, ['employee-employee']);
      }
    } else {
      // For any added group, add membership
      foreach ($new_primary_group_ids as $new_primary_group_id) {
        PortlandOpenIdConnectUtil::addUserToGroupWithRoles($account, $new_primary_group_id, ['employee-employee']);
      }

      // Check if any current group is not in the new group list
      foreach ($current_primary_group_ids as $current_primary_group_id) {
        if (in_array($current_primary_group_id, $new_primary_group_ids))
          continue;
        // Remove user from the new group
        PortlandOpenIdConnectUtil::removeUserFromGroup($account, $current_primary_group_id);
      }
    }
  }

  /**
   * Convert a comma separated string of group names into group IDs
   */
  private static function buildGroupIDlistFromGroupNames($group_names)
  {
    // Assume the group names have been cleaned by presave hook
    $group_names_array = explode(',', $group_names);

    // Build new primary group ID array with primary group names
    $group_ad_name_list = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadTree('group_ad_name_list', 0, 1, true);
    $group_ids = [];
    foreach ($group_ad_name_list as $group_ad_name) {
      if (in_array($group_ad_name->name->value, $group_names_array)) {
        $drupal_groups = $group_ad_name->field_employee_groups->getValue();
        foreach ($drupal_groups as $drupal_group) {
          $group_ids[] = $drupal_group['target_id'];
        }
      }
    }
    // Remove duplicates
    return array_unique($group_ids);
  }

  /**
   * Return an array of group IDs that the user belongs to
   */
  public static function getGroupIdsOfUser($account)
  {
    $group_ids = [];
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $grps = $grp_membership_service->loadByUser($account);
    foreach ($grps as $grp) {
      $group_ids[] = $grp->getGroup()->id();
    }
    return $group_ids;
  }
}
