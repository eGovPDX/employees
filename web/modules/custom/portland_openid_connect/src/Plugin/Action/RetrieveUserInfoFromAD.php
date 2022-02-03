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
 *   id = "retrieve_user_info_from_ad",
 *   label = @Translation("Retrieve user information from Azure AD"),
 *   type = "user"
 * )
 */
class RetrieveUserInfoFromAD extends ActionBase
{
  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL)
  {
    if (empty($account)) return;

    // Skip if cannot find a Drupal user with the email
    $users = \Drupal::entityTypeManager()->getStorage('user')
            ->loadByProperties(['mail' => $account->getEmail()]);
    if( empty($users) ) return;
    $user = array_values($users)[0];
    // If the user is not active, skip
    if ( ! $user->status->value ) return;
    $azure_ad_id = $user->field_active_directory_id->value;
    if( empty($azure_ad_id)) return;

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
    if (in_array(strtolower($account->getEmail()), array_map('strtolower', $skip_emails))) return;

    $tokens = PortlandOpenIdConnectUtil::GetAccessToken();
    if (empty($tokens) || empty($tokens['access_token'])) {
      \Drupal::logger('portland OpenID')->error("Cannot retrieve access token for Microsoft Graph. Make sure the client secret is correct.");
      return;
    }

    PortlandOpenIdConnectUtil::GetUserProfile($tokens['access_token'], $account->getEmail(), $azure_ad_id);
    PortlandOpenIdConnectUtil::GetUserManager($tokens['access_token'], $account->getEmail(), $azure_ad_id);
    // PortlandOpenIdConnectUtil::GetUserPhoto($tokens['access_token'], $account->getEmail(), $azure_ad_id);
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
