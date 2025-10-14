<?php

namespace Drupal\employees\Plugin\Action;

use Drupal\group\Entity\Group;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\portland_openid_connect\Util\PortlandOpenIdConnectUtil;
use Drupal\Core\Access\AccessResult;

/**
 * Update the Member role to Assigned or Following
 *
 * @Action(
 *   id = "add_assigned_and_following",
 *   label = @Translation("Convert Member to Assigned or Following (custom action)"),
 *   type = "user",
 *   confirm = FALSE,
 * )
 */
class AddAssignedAndFollowing extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $account = $entity;
    // Get all groups the user belongs to
    $current_primary_group_ids = PortlandOpenIdConnectUtil::getGroupIdsOfUser($account);

    foreach ($current_primary_group_ids as $current_primary_group_id) {
      $group = Group::load($current_primary_group_id);
      $group_type = $group->type->entity->id();
      $membership = $group->getMember($account);
      $group_relationship = $membership->getGroupRelationship();
      $roles = array_keys($membership->getRoles(FALSE));
      $only_has_member_role = ( count($roles) === 1 && ( $roles[0] === 'private-member' || $roles[0] === 'employee-member' ) );
      if ($only_has_member_role) {
        // For all Private groups, if there is only Member role, replace it with Assigned role
        if($group_type === 'private') {
          $roles[0] = 'private-assigned';
        }
        // For all Employee groups, if there is only Member role, replace it with Following role
        else if($group_type === 'employee') {
          $roles[0] = 'employee-following';
        }
        
        $group_relationship->group_roles = $roles;
        $group_relationship->save();
      } elseif (in_array('private-member', $roles) || in_array('employee-member', $roles)) {
        // Remove the member role from their list of roles
        $key = array_search('private-member', $roles);
        if ($key !== false) {
          unset($roles[$key]);
        }

        $key = array_search('employee-member', $roles);
        if ($key !== false) {
          unset($roles[$key]);
        }

        $group_relationship->group_roles = $roles;
        $group_relationship->save();
      }
    }

    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Bulk operation: Member role converted to Assigned and Following');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return ($return_as_object ? AccessResult::allowed() : true );
  }
}
