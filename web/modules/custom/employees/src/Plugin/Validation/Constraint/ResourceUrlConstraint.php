<?php

namespace Drupal\employees\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Make sure resource URL has not been added before.
 *
 * @Constraint(
 *   id = "ResourceUrl",
 *   label = @Translation("Make sure resource URL has not been added before.", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class ResourceUrlConstraint extends Constraint {

  /**
   * Message shown when the resource URL already exists.
   *
   * @var string
   */
  public $message = 'Found duplicate resource URL: ';

  /**
   * {@inheritdoc}
   */
  // public function coversFields() {
  //   return ['field_resource_link'];
  // }
}
