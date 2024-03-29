<?php

namespace Drupal\portland_openid_connect\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\portland_openid_connect\Util\PortlandOpenIdConnectUtil;
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
    // Add a try catch block to help log any exception
    try {
      // field_primary_groups and group memberships are managed in hook_user_presave
      // and hook_user_update, we only need to save the user here
      $account->save();
    } catch (\Exception $e) {
      \Drupal::logger('portland OpenID')->notice('Exception during UpdateUserPrimaryGroups: ' . $e->getMessage() . '. ' . $account->getAccountName());
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
