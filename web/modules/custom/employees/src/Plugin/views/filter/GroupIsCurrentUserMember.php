<?php

namespace Drupal\employees\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;
use Drupal\views\ResultRow;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View filter for whether the current user is a member of the group.
 *
 * @ViewsFilter("group_is_current_user_member")
 */
class GroupIsCurrentUserMember extends BooleanOperator implements ContainerFactoryPluginInterface {
  protected AccountInterface $currentUser;
  protected Connection $database;
  protected EntityTypeManagerInterface $entityTypeManager;


  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('database'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!$this->currentUser->isAuthenticated()) return;

    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityType());
    $keys = $entity_type->getKeys();
    $entity_id_key = $keys['id'];
    $query_base_table = $this->relationship ?: $this->view->storage->get('base_table');

    $uid = $this->currentUser->id();
    $subselect = $this->database
      ->select('group_relationship_field_data', 'gc')
      ->fields('gc', ['type', 'gid', 'entity_id'])
      ->where("[gc].[gid] = [$query_base_table].[$entity_id_key]")
      ->condition('type', '%-group_membership', 'LIKE')
      ->condition('entity_id', $uid, '=');

    $condition = $this->database->condition('OR');
    // If filter is (Equal to True OR Not Equal to False): Check that user is a member
    // Else: Check that user is not a member
    if (
      ($this->operator === '=' && $this->value)
      || ($this->operator === '!=' && !$this->value)) {
      $condition = $condition->exists($subselect);
    } else {
      $condition = $condition->notExists($subselect);
    }
    $this->query->addWhere($this->options['group'], $condition);
  }
}
