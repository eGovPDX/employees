<?php

namespace Drupal\employees\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines a custom entity reference selection plugin.
 *
 * @EntityReferenceSelection(
 *   id = "default:employees_selection",
 *   label = @Translation("Employees Selection"),
 *   entity_types = {"node"},
 *   group = "employees",
 *   weight = 0,
 *   description = @Translation("Provides a custom selection method for entity references."),
 * )
 */
class NodeSelectionInGroup extends DefaultSelection implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $group_id;

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = [], $match_operator = 'CONTAINS', $limit = 0) {

    $this->setConfiguration([
      'target_type' => 'node',
      'target_bundles'=>['resource'=>'resource', 'page'=>'page']
    ]);

    $target_type = $this->getConfiguration()['target_type'];
    $query = $this->buildEntityQuery($match, $match_operator);
    $query->condition("field_display_in_group", $this->group_id);
    if ($limit > 0) {
        $query->range(0, $limit);
    }
    $result = $query->execute();
    if (empty($result)) {
        return [];
    }
    $options = [];
    $entities = $this->entityTypeManager
        ->getStorage($target_type)
        ->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
        $bundle = $entity->bundle();
        $options[$bundle][$entity_id] = Html::escape($this->entityRepository
            ->getTranslationFromContext($entity)
            ->label() ?? '');
    }
    return $options;
  }
}
