<?php

namespace Drupal\employees\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media_remote\Plugin\Field\FieldFormatter\MediaRemoteFormatterBase;

/**
 * Plugin implementation of the 'media_iframe_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "media_iframe_embed",
 *   label = @Translation("Remote Media - Iframe Embed"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class MediaIframeEmbedFormatter extends MediaRemoteFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function getUrlRegexPattern() {
    $patterns = [
      // ArcGIS
      '^https?:\/\/arcg.is\/\w+$',
      '^https?:\/\/\w+\.maps\.arcgis\.com\/apps\/Embed\/index.html\?webmap=.+$',
      // Google Maps
      "^https?:\/\/www\.google\.com\/maps\/embed\?pb=(.+)$",
      "^https?:\/\/www\.google\.com\/maps\/d\/embed\?mid=(\w+)$",
      // PortlandMaps map or chart
      "^https?:\/\/www\.portlandmaps\.com\/(detail|apps)\/(\w+)$",
      "^https:\/\/www\.portlandmaps\.com([-_\/[:alnum:]]*\/charts\/.*)$",
      // POG
      '^https?:\/\/www\.portlandoregon\.gov\/bes\/bigpipe\/\w+\.cfm$',
      // Tableau
      '^https?:\/\/(online|public)\.tableau\.com\/([^"]+\?[^"]*:embed=(true|yes|y|1)[^"]*)$',
    ];

    return "/" . join("|", $patterns) . "/";
  }

  /**
   * {@inheritdoc}
   */
  public static function getValidUrlExampleStrings(): array {
    return ['URLs from arcg.is, arcgis.com, Google Maps, PortlandMaps.com, PortlandOregon.gov, Tableau. If you would like to request a new service, please contact website@portlandoregon.gov for review.'];
  }

  /**
   * {@inheritdoc}
   */
  public static function deriveMediaDefaultNameFromUrl($url) {
    return hash('md5', $url);
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
      if (empty($matches[0])) {
        continue;
      }
      $elements[$delta] = [
        '#theme' => 'media_embed_iframe',
        '#url' =>  $item->value,
        '#attributes' => [
          'width' => '100%',
          'height' => '100%',
          'frameborder' => '0',
          'allowfullscreen' => 'true',
          'parent_class' => 'h-100',
          // 'style' => 'min-height:300px; min-height:200px',
        ],
      ];
    }
    return $elements;
  }
}
