<?php

namespace Drupal\portland_openid_connect\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Update the user's primary group to match the AD group name.
 *
 * @Action(
 *   id = "user_update_user_primary_group",
 *   label = @Translation("Update primary group for the selected users"),
 *   type = "user"
 * )
 */
class UpdateUserPrimaryGroup extends ActionBase
{

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL)
  {
    $primary_group_name = '';
    $group_names = $account->field_group_names->value;
    if (!empty($group_names)) {
      // Deduplicate group names
      if (strpos($group_names, ',') !== false) {
        $group_name_array = explode(',', $account->field_group_names->value);
        $group_name_array = array_unique($group_name_array);
        $account->field_group_names->value = implode(',', $group_name_array);
        $primary_group_name = $group_name_array[0];
      } else {
        $primary_group_name = $group_names;
      }

      // Look up the Drupal group ID with Azure AD group name
      $group_ad_name_list = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
        ->loadTree('group_ad_name_list', 0, 1, true);
      $primary_group_id = '';
      foreach ($group_ad_name_list as $group_ad_name) {
        if ($primary_group_name === $group_ad_name->name->value) {
          $primary_group_id = $group_ad_name->field_group[0]->entity->id();
          break;
        }
      }
      if (!empty($primary_group_id)) {
        // Delete the user from the original group
        if( $account->field_primary_group->count() > 0 ) {
          $original_group_id = $account->field_primary_group->target_id;
          // Update group memebership if new and old groups are different
          if( $original_group_id !== $primary_group_id) {
            // Remove from original group
            $original_group = \Drupal\group\Entity\Group::load($account->field_primary_group->target_id);
            $original_group->removeMember($account);
            $original_group->save();

            // Add the user to the primary group
            $group = \Drupal\group\Entity\Group::load($primary_group_id);
            $group->addMember($account);
            $group->save();

            $account->field_primary_group = ["target_id" => $primary_group_id];
            $account->save();
          }
        }
        // The user doesn't have a primary group before. Update the field value and add user to the group
        else {
          $account->field_primary_group = ["target_id" => $primary_group_id];
          $account->save();
  
          // Add the user to the primary group
          $group = \Drupal\group\Entity\Group::load($primary_group_id);
          $group->addMember($account);
          $group->save();
        }
      }
    }
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
}
