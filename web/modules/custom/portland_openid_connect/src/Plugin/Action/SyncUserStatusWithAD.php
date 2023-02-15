<?php

namespace Drupal\portland_openid_connect\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\portland_openid_connect\Util\PortlandOpenIdConnectUtil;
/**
 * Disalbe a user if the account is disabled in AD.
 *
 * @Action(
 *   id = "sync_user_status_with_ad",
 *   label = @Translation("Sync user status with Azure AD"),
 *   type = "user"
 * )
 */
class SyncUserStatusWithAD extends ActionBase
{
  /**
   * {@inheritdoc}
   */
  public function execute($user = NULL)
  {
    if (empty($user)) return $this->t('User skipped');

    if(PortlandOpenIdConnectUtil::ShouldSkipUser($user)) return $this->t('User skipped');

    $domain = (str_ends_with($user->mail->value, PortlandOpenIdConnectUtil::PTLD_DOMAIN_NAME)) ? PortlandOpenIdConnectUtil::PTLD_DOMAIN_NAME : PortlandOpenIdConnectUtil::ROSE_DOMAIN_NAME;

    $tokens = PortlandOpenIdConnectUtil::GetAccessToken($domain);
    if (empty($tokens) || empty($tokens['access_token'])) {
      \Drupal::logger('portland OpenID')->error("Cannot retrieve access token for Microsoft Graph. Make sure the client secret is correct.");
    }

    $user_is_enabled = PortlandOpenIdConnectUtil::IsUserEnabled($tokens['access_token'], $user);
    if($user_is_enabled) {
      PortlandOpenIdConnectUtil::EnableUser($user);
    }
    else {
      PortlandOpenIdConnectUtil::DisableUser($user);
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
