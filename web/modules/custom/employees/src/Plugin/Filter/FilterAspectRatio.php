<?php

namespace Drupal\employees\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to process data-aspect-ratio.
 *
 * @Filter(
 *   id = "filter_aspect_ratio",
 *   title = @Translation("Set aspect ratio"),
 *   description = @Translation("Uses a <code>data-aspect-ratio</code> attribute on <code>&lt;data-media&gt;</code> tags to set aspect ratio."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterAspectRatio extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    // Do not process CKEditor preview
    if( str_ends_with(\Drupal::service('path.current')->getPath(), '/preview') ) return $result;

    if (stristr($text, 'data-entity-uuid') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-entity-uuid]') as $node) {
        // Check if it's an iframe
        $uuid = $node->getAttribute('data-entity-uuid');
        if(empty($uuid)) continue;

        $entity_loaded_by_uuid = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['uuid' => $uuid]);
        if(count($entity_loaded_by_uuid) == 0) continue;
        $entity_loaded_by_uuid = reset($entity_loaded_by_uuid);
        if($entity_loaded_by_uuid->bundle() != 'iframe') continue;

        // Read the data-aspect-ratio attribute's value, then delete it.
        $aspect_ratio = $node->getAttribute('data-aspect-ratio');
        $node->removeAttribute('data-aspect-ratio');

        // If one of the allowed aspect ratios, set the attribute.
        if( empty($aspect_ratio)) {
          $node->setAttribute('class', 'ratio ratio-16x9');
        }
        else if (in_array($aspect_ratio, ['ratio ratio-16x9', 'ratio ratio-4x3', 'ratio ratio-1x1'])) {
          $node->setAttribute('class', $node->getAttribute('class') . $aspect_ratio);
        }
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('<p>You can set aspect ratio to 16x9, 4x3, or 1x1.</p>');
  }
}