<?php

namespace Drupal\portland_openid_connect\Plugin\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\portland_openid_connect\Util\PortlandOpenIdConnectUtil;
/**
 * Update the user's primary groups to match the AD group names.
 *
 * @Action(
 *   id = "retrieve_user_info_from_ad",
 *   label = @Translation("Retrieve user information from Azure AD"),
 *   type = "user"
 * )
 */
class RetrieveUserInfoFromAD extends ViewsBulkOperationsActionBase
{
  /**
   * {@inheritdoc}
   */
  public function execute($user = NULL)
  {
    if (empty($user)) return;

    if(PortlandOpenIdConnectUtil::ShouldSkipUser($user)) return;

    $domain = (str_ends_with($user->mail->value, PortlandOpenIdConnectUtil::PTLD_DOMAIN_NAME)) ? PortlandOpenIdConnectUtil::PTLD_DOMAIN_NAME : PortlandOpenIdConnectUtil::ROSE_DOMAIN_NAME;

    $tokens = PortlandOpenIdConnectUtil::GetAccessToken($domain);
    if (empty($tokens) || empty($tokens['access_token'])) {
      \Drupal::logger('portland OpenID')->error("Cannot retrieve access token for Microsoft Graph. Make sure the client secret is correct.");
      return $this->t('Cannot retrieve access token for Microsoft Graph. Make sure the client secret is correct.');
    }

    PortlandOpenIdConnectUtil::GetUserProfile($tokens['access_token'], $user);
    PortlandOpenIdConnectUtil::GetUserManager($tokens['access_token'], $user);
    // PortlandOpenIdConnectUtil::GetUserPhoto($tokens['access_token'], $account_name, $azure_ad_id);
    $user->save();
    return $this->t('User information retrieved from Entra ID.');
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
