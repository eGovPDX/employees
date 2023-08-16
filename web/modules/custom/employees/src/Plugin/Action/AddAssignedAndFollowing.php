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

    // Add the Employee role to user in all new groups
    foreach ($current_primary_group_ids as $current_primary_group_id) {
      $group = Group::load($current_primary_group_id);
      $group_type = $group->type->entity->id();
      $membership = $group->getMember($account);
      $group_content = $membership->getGroupContent();
      $roles = array_keys($membership->getRoles());
      $only_has_member_role = ( count($roles) === 1 && ( $roles[0] === 'private-member' || $roles[0] === 'employee-member' ) );
      // For all Private groups, if there is only Member role, add Assigned role
      if( $only_has_member_role && $group_type === 'private') {
        $group_content->group_roles->appendItem(['target_id' => 'private-assigned']);
        $group_content->save();
      }
      // For all Employee groups, if there is only Member role, add Following role
      else if($only_has_member_role && $group_type === 'employee') {
        $group_content->group_roles->appendItem(['target_id' => 'employee-following']);
        $group_content->save();
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
