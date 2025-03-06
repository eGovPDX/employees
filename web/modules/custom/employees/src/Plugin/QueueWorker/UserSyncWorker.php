<?php

namespace Drupal\employees\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\portland_openid_connect\Util\PortlandOpenIdConnectUtil;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * UserSyncWorker class.
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
   *   "user_type" => "drupal" or "entra_id"
   *   "users" => users[] // Drupal User objects for "drupal" user type or PHP Arrays for "entra_id" user type
   * ]
   */
  public function processItem($data)
  {
    if ($data["user_type"] === "drupal") {
      $this->processDrupalUsers($data);
    } else if ($data["user_type"] === "entra_id") {
      $this->processEntraIDUsers($data);
    }
  }

  public function processDrupalUsers($data)
  {
    $users_disabled = [];
    /* @var \GuzzleHttp\ClientInterface $client */
    $client = new Client();
    // Perform the request.
    $options = [
      'method' => 'GET',
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $data["access_token"],
        'ConsistencyLevel' => 'eventual',
      ],
    ];
    $skip_emails = [
      'BTS-eGov@portlandoregon.gov',
      'ally.admin@portlandoregon.gov',
      'marty.member@portlandoregon.gov',
      'oliver.outsider@portlandoregon.gov',
    ];

    $email_domain = '@' . $data['domain'];
    foreach ($data["users"] as $user) {
      if ($user->field_is_contact_only->value) continue;
      if (in_array(strtolower($user->getEmail()), array_map('strtolower', $skip_emails))) continue;
      // If the user's email address is not in the domain AND is not ProsperPortland.us, skip the user
      $user_email = strtolower($user->getEmail());
      if (!str_ends_with($user_email, $email_domain) && 
      !str_ends_with($user_email, PortlandOpenIdConnectUtil::PROSPER_PORTLAND_EMAIL_SUFFIX)) continue;

      // Must use the principal name as the lookup key
      $request_url = 'https://graph.microsoft.com/v1.0/users/' . $user->field_principal_name->value;
      $result_is_404 = false;
      try {
        $response = $client->get($request_url, $options);
      } catch (RequestException $e) {
        if ($e->getCode() == 404) {
          $result_is_404 = true;
        }
        else {
          $variables = [
            '@message' => 'Error retrieving user ' . $user->getEmail(),
            '@error_message' => $e->getMessage(),
          ];
          \Drupal::logger('portland OpenID')->error('@message. Details: @error_message', $variables);
        }
      }

      // Only disable user if it's enabled
      if ($result_is_404 && $user->status->value == 1) {
        $user->status = 0;
        $user->save();
        $users_disabled[] = $user->getEmail();
      }
    }

    if (count($users_disabled) > 0) \Drupal::logger('portland OpenID')->info("(Removal) Disabled " . count($users_disabled) . " users: " . implode(",", $users_disabled));
  }

  /**
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
  public function processEntraIDUsers($data)
  {
    $users_created = [];
    $users_enabled = [];
    $users_disabled = [];
    foreach ($data["users"] as $user_data) {
      // Skip accounts without first name, last name, userPrincipalName, or email. These are not people acount.
      if (
        empty($user_data['givenName']) ||
        empty($user_data['surname']) ||
        empty($user_data['mail']) ||
        empty($user_data['userPrincipalName']) ||
        empty($user_data['id']) ||
        (str_ends_with($user_data['userPrincipalName'], 'onmicrosoft.com') &&
        ! str_ends_with($user_data['mail'], PortlandOpenIdConnectUtil::PROSPER_PORTLAND_EMAIL_SUFFIX)) || // Allow Prosper Portland users to be processed
        str_contains(strtolower($user_data['mail']), '_adm@')
      ) {
        continue;
      }

      // User name in Drupal has a limit of 60 characters
      $userName = (str_ends_with($user_data['mail'], PortlandOpenIdConnectUtil::PROSPER_PORTLAND_EMAIL_SUFFIX)) ? 
        PortlandOpenIdConnectUtil::TrimUserName($user_data['mail']) :
        PortlandOpenIdConnectUtil::TrimUserName($user_data['userPrincipalName']);

      // Look up user by email. Sometimes the email address is reused in a new AD account.
      $users = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['mail' => $user_data['mail']]);

      // Look up user by Active Directory ID if the email lookup returns no result.
      // In this case, the user's email address is changed in AD.
      if(empty($users)) {
        $users = \Drupal::entityTypeManager()->getStorage('user')
          ->loadByProperties(['field_active_directory_id' => $user_data['id']]);
      }

      // Create a new user if no user is found
      /** @var User $user */
      $user = (empty($users)) ? User::create([
        'field_active_directory_id' => $user_data['id'],
        'mail' => $user_data['mail'],
        'name' => $userName,
        'pass' => \Drupal::service('password_generator')->generate(), // Create a temp password
      ]) :  array_values($users)[0];
      $user->field_active_directory_id = $user_data['id'];
      $user->status = $user_data['accountEnabled'];
      $user->field_principal_name = $user_data['userPrincipalName']; // No length limit, can use the value from EntraID
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

      if($user->isNew()) {
        // Do not add disabled user
        if($user->status->value == 0) continue;
        $users_created []= $userName;
      }
      else {
        $user->setUsername($userName);
        if($user_data['accountEnabled'] == 1 && $user->status->value == 0) $users_enabled []= $userName;
        if($user_data['accountEnabled'] == 0 && $user->status->value == 1) $users_disabled []= $userName;
      }

      // echo (($user->isNew()) ? "created," : "updated,") . $user_data['userPrincipalName'] . PHP_EOL;
      try {
        $user->save();
      } catch (Exception $e) {
        \Drupal::logger('portland OpenID')->error('Failed to save user ' . $user_data['userPrincipalName']);
      }

      // PTLD users don't have manager data
      if ($data["domain"] == "portlandoregon.gov") {
        PortlandOpenIdConnectUtil::GetUserManager($data["access_token"], $user);
      }
    }

    if(count($users_created) > 0) \Drupal::logger('portland OpenID')->info("Created " . count($users_created) . " users: " . implode(",", $users_created));
    if(count($users_enabled) > 0) \Drupal::logger('portland OpenID')->info("Enabed " . count($users_enabled) . " users: " . implode(",", $users_enabled));
    if(count($users_disabled) > 0) \Drupal::logger('portland OpenID')->info("Disabled " . count($users_disabled) . " users: " . implode(",", $users_disabled));
  }
}
