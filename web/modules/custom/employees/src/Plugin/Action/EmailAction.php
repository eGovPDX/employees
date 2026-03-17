<?php

namespace Drupal\employees\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Email action
 *
 * @Action(
 *   id = "employees_email",
 *   label = @Translation("Email users (custom action)"),
 *   type = "",
 *   confirm = FALSE,
 * )
 */
class EmailAction extends ViewsBulkOperationsActionBase implements PluginFormInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity->getEntityTypeId() == "group_relationship") {
      $uid = $entity->get("entity_id")->__get("target_id");
    } else {  // User entity
      $uid = $entity->get("uid")->value;
    }
    $user = \Drupal::entityTypeManager()->getStorage("user")->load($uid);
    $user_email = $user->__get("mail")->__get("value");

    return $user_email;
  }


  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    $results = [];
    foreach ($objects as $entity) {
      $results[] = $this->execute($entity);
    }
    $combined_result = implode(",", $results);


    $mailManager = \Drupal::service("plugin.manager.mail");
    $module = "employees";
    $key = "employees_group_admin_email";
    $to = $combined_result;
    $params["subject"] = $this->configuration["subject"];
    $params["message"] = $this->configuration["message"]["value"];
    if ($entity->getEntityTypeId() == "group_relationship") {
      $gid = $entity->get("gid")->__get("target_id");
      $group = \Drupal::entityTypeManager()->getStorage("group")->load($gid);
      $params["group"] = $group->label();
      $params["group_path"] = "/" . $group->get("field_group_path")->value;
    }
    $langcode = "en";
    $send = true;

    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result["result"] !== true) {
      $result_msg = t("There was a problem sending your message and it was not sent.");
    }
    else {
      $result_msg = t("Your message has been sent.");
    }

    return [$result_msg];
  }


  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    // If certain fields are updated, access should be checked against them as well.
    // @see Drupal\Core\Field\FieldUpdateActionBase::access().
    return $object->access("update", $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form["subject"] = [
      "#title" => $this->t("Email subject"),
      "#type" => "textfield"
    ];
    $form["message"] = [
      "#title" => $this->t("Email message"),
      "#description" => $this->t("Enter the message to send to the selected users."),
      "#type" => "text_format",
      "#allowed_formats" => ["basic_html"]
    ];
    return $form;
  }
}
