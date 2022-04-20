<?php

namespace Drupal\employees\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media_remote\Plugin\Field\FieldFormatter\MediaRemoteFormatterBase;

/**
 * Plugin implementation of the 'media_remote_document' formatter.
 *
 * @FieldFormatter(
 *   id = "media_remote_document",
 *   label = @Translation("Remote Media - Document"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class MediaRemoteDocumentFormatter extends MediaRemoteFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function getUrlRegexPattern() {
    return '/^https:\/\/efiles\.portlandoregon\.gov\/Record\/([\d]+)(\/)?/';
  }

  /**
   * {@inheritdoc}
   */
  public static function getValidUrlExampleStrings(): array {
    return [
      'https://efiles.portlandoregon.gov/Record/4105477/',
      'https://efiles.portlandoregon.gov/Record/4105477/File/Document',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function deriveMediaDefaultNameFromUrl($url) {
    $matches = [];
    $pattern = static::getUrlRegexPattern();
    preg_match($pattern, $url, $matches);
    if (!empty($matches[1])) {
      return $matches[1];
    }
    return parent::deriveMediaDefaultNameFromUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      if ($item->isEmpty()) {
        continue;
      }
      $matches = [];
      $pattern = static::getUrlRegexPattern();
      preg_match($pattern, $item->value, $matches);
      if (empty($matches[1])) {
        continue;
      }
      $document_id = $matches[1];
      $elements[$delta] = [
        '#theme' => 'media_remote_document',
        // '#document_id' => $document_id,
        // '#slug' => $matches[2][0] ?? '',
      ];
    }
    return $elements;
  }

}
