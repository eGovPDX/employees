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
  public function execute($account = NULL)
  {
    if (empty($account)) return $this->t('User skipped');

    // Skip if cannot find a Drupal user with the email
    $users = \Drupal::entityTypeManager()->getStorage('user')
            ->loadByProperties(['mail' => $account->getEmail()]);
    if( empty($users) ) return $this->t('User skipped');
    $user = array_values($users)[0];
    // If the user is not active, skip
    // If the user is Contact Only, skip
    // If there is no Azure AD ID, skip
    $azure_ad_id = $user->field_active_directory_id->value;
    if ( ! $user->status->value || $user->field_is_contact_only->value || empty($azure_ad_id) ) return $this->t('User skipped');

    // Skip these users
    $skip_emails = [
      'BTS-eGov@portlandoregon.gov',
      'ally.admin@portlandoregon.gov',
      'marty.member@portlandoregon.gov',
      'oliver.outsider@portlandoregon.gov',
      // 'amy.archer-masters@portlandoregon.gov',  // User email address
      // 'amy.archer@portlandoregon.gov',  // Principal user name
      // 'WBUDFTeam@portlandoregon.gov',  // Outlook distribution list
      // 'council140@portlandoregon.gov',  // Actual AD group
    ];
    if (in_array(strtolower($account->getEmail()), array_map('strtolower', $skip_emails))) return $this->t('User skipped');

    $tokens = PortlandOpenIdConnectUtil::GetAccessToken();
    if (empty($tokens) || empty($tokens['access_token'])) {
      \Drupal::logger('portland OpenID')->error("Cannot retrieve access token for Microsoft Graph. Make sure the client secret is correct.");
    }

    $user_is_enabled = PortlandOpenIdConnectUtil::IsUserEnabled($tokens['access_token'], $account->getEmail(), $azure_ad_id);
    if(!$user_is_enabled) {
      PortlandOpenIdConnectUtil::DisableUser($account);
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
