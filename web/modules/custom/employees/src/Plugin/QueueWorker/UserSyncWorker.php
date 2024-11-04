<?php

namespace Drupal\employees\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\portland_openid_connect\Util\PortlandOpenIdConnectUtil;
use Exception;

/**
 * SyncUsersWorker class.
 *
 * A worker plugin to consume items from "user_sync"
 * and synchronize users from Entra ID.
 *
 * @QueueWorker(
 *   id = "user_sync",
 *   title = @Translation("Synchronize Users Queue"),
 *   cron = {"time" = 120}
 * )
 */
class UserSyncWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface
{
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param array $data
   * [
   *   "access_token" => $access_token,
   *   "domain" => $domain,
   *   "users" => users[{...}]
   * ]
   * 
   * User data example:
   * {
   *     "id": "1234dfc6-310c-4d5e-aa62-6237a8222f1b",
   *     "accountEnabled": true,
   *     "userPrincipalName": "John.Doe@portlandoregon.gov",
   *     "displayName": "Doe, John",
   *     "givenName": "John",
   *     "surname": "Doe",
   *     "jobTitle": "City worker",
   *     "mail": "John.Doe@portlandoregon.gov",
   *     "companyName": "Bureau of Technology Services",
   *     "department": "DepartmentA",
   *     "officeLocation": "123/1234",
   *     "businessPhones": [
   *       *  "555-555-5555"
   *     ],
   *     "mobilePhone": "555-555-5555",
   *     "employeeId": "1234567",
   *     "userType": "Member",
   *     "employeeType": "Regular",
   *     "streetAddress": "1120 SW 5th Ave, Suite 1234",
   *     "city": "Portland",
   *     "state": "OR",
   *     "postalCode": "97204",
   *     "mailNickname": "jdoe"
   * }
   */
  public function processItem($data)
  {
    foreach ($data["users"] as $user_data) {
      // Skip accounts without first name, last name, userPrincipalName, or email. These are not people acount.
      if (
        empty($user_data['givenName']) ||
        empty($user_data['surname']) ||
        empty($user_data['mail']) ||
        empty($user_data['userPrincipalName']) ||
        empty($user_data['id']) ||
        str_ends_with($user_data['userPrincipalName'], 'onmicrosoft.com') ||
        str_contains(strtolower($user_data['mail']), '_adm@')
      ) {
        continue;
      }

      // Look up user by Drupal user name (Principal name in AD)
      // Sometimes a user will be recreated with the same principal name but different AD ID
      $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $user_data['userPrincipalName']]);

      // Create a new user if no user is found
      /** @var User $user */
      $user = (empty($users)) ? User::create([
        'field_active_directory_id' => $user_data['id'],
        'mail' => $user_data['mail'],
        'name' => PortlandOpenIdConnectUtil::TrimUserName($user_data['userPrincipalName']),
        'pass' => \Drupal::service('password_generator')->generate(), // Create a temp password
      ]) :  array_values($users)[0];
      $user->field_active_directory_id = $user_data['id'];
      $user->status = $user_data['accountEnabled'];
      $user->field_principal_name = $user_data['userPrincipalName'];
      $user->field_first_name = $user_data['givenName'];
      $user->field_last_name = $user_data['surname'];
      $user->field_title = $user_data['jobTitle'];
      $user->mail->value = $user_data['mail'];
      $user->field_group_names = $user_data['companyName'];
      $user->field_division_name = $user_data['department'];
      $user->field_office_location = $user_data['officeLocation'];
      $user->field_phone = (empty($user_data['businessPhones']) ? '' : $user_data['businessPhones'][0]);
      $user->field_mobile_phone = $user_data['mobilePhone'];
      $user->field_address = empty($user_data['streetAddress']) ? '' : ($user_data['streetAddress'] . ', ' . $user_data['city'] . ', ' . $user_data['state'] . ', ' . $user_data['postalCode']);

      // echo (($user->isNew()) ? "created," : "updated,") . $user_data['userPrincipalName'] . PHP_EOL;
      try {
        $user->save();
      } catch (Exception $e) {
        \Drupal::logger('portland OpenID')->error('Failed to save user ' . $user_data['userPrincipalName']);
      }

      // PTLD users don't have manager data
      if($data["domain"] == "portlandoregon.gov") {
        PortlandOpenIdConnectUtil::GetUserManager($data["access_token"], $user);
      }
    }
  }
}
