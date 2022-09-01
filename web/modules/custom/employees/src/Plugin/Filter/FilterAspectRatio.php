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

    if (stristr($text, 'data-aspect-ratio') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-aspect-ratio]') as $node) {
        // Read the data-aspect-ratio attribute's value, then delete it.
        $aspect_ratio = $node->getAttribute('data-aspect-ratio');
        $node->removeAttribute('data-aspect-ratio');

        // If one of the allowed aspect ratios, set the attribute.
        if (in_array($aspect_ratio, ['ratio ratio-16x9', 'ratio ratio-4x3', 'ratio ratio-1x1'])) {
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
