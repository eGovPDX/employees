<?php

namespace Drupal\employees\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media_remote\Plugin\Field\FieldFormatter\MediaRemoteFormatterBase;

/**
 * Plugin implementation of the 'media_iframe' formatter.
 *
 * @FieldFormatter(
 *   id = "media_iframe",
 *   label = @Translation("Remote Media - Iframe"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class MediaRemoteMapOrChartFormatter extends MediaRemoteFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function getUrlRegexPattern() {
    $patterns = [
      // ArcGIS
      '^https?:\/\/arcg.is/\w+',
      // Google Maps
      "^https?:\/\/www\.google\.com\/maps\/embed\?pb=(.+)$",
      "^https?:\/\/www\.google\.com\/maps\/d\/embed\?mid=(?<id>.+)$",
      // PortlandMaps map or chart
      "^https?:\/\/www\.portlandmaps\.com\/(detail|apps)\/(?<id>.+)$",
      "^https:\/\/www\.portlandmaps\.com(?<id>[-_\/[:alnum:]]*\/charts\/.*)$",
      // POG
      '^https?:\/\/www\.portlandoregon\.gov\/bes\/bigpipe\/(?<id>\w+)\.cfm$',
      // Tableau
      '^https?:\/\/(online|public)\.tableau\.com\/(?<id>[^"]+\?[^"]*:embed=(true|yes|y|1)[^"]*)$',
    ];

    return "/" . join("|", $patterns) . "/";
  }

  /**
   * {@inheritdoc}
   */
  public static function getValidUrlExampleStrings(): array {
    return [
      'https://arcg.is/0CH41a0',
      'https://www.google.com/maps/d/embed?mid=1AQxUf8YDigyHgIO5IuL9sVeiqbvFy24Y&ehbc=2E312F',
      'https://www.portlandmaps.com/bps/scg/charts/?theme=fleet&chart=1',
      'https://nff.maps.arcgis.com/apps/Embed/index.html?webmap=3d7060912fff43c0a151c097ba328f18&extent=-120.8593,37.9925,-90.6909,51.0109&zoom=true&previewImage=false&scale=true&legend=true&disable_scroll=true&theme=light',
      'https://public.tableau.com/views/ReportedBiasCrimes/BiasCrime?:embed=y&:showVizHome=no&:host_url=https%3A%2F%2Fpublic.tableau.com%2F&:embed_code_version=3&:tabs=yes&:toolbar=yes&:animate_transition=yes&:display_static_image=no&:display_spinner=no&:display_overlay=yes&:display_count=yes&:loadOrderID=0&:increment_view_count=no&width=660&height=1800',
    ];
  }

  /**
   * {@inheritdoc}
   */
  // public static function deriveMediaDefaultNameFromUrl($url) {
  //   $matches = [];
  //   $pattern = static::getUrlRegexPattern();
  //   preg_match($pattern, $url, $matches);
  //   if (!empty($matches[1])) {
  //     return $matches[1];
  //   }
  //   return parent::deriveMediaDefaultNameFromUrl($url);
  // }

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
      $elements[$delta] = [
        '#theme' => 'media_embed_iframe',
        '#url' =>  $item->value,
        '#attributes' => [
          'width' => '100%',
          // 'height' => '480px',
          'frameborder' => '0',
          'allowfullscreen' => 'true',
          'parent_class' => 'ratio ratio-16x9',
        ],
      ];
    }
    return $elements;
  }
}
