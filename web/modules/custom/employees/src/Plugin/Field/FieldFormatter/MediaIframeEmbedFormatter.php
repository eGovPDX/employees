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
    // return [
    //   'https://arcg.is/DbHjf0',
    //   'https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d22361.96181366453!2d-122.67747803387468!3d45.52527053380518!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sus!4v1661813786487!5m2!1sen!2sus',
    //   'https://www.portlandmaps.com/bps/scg/charts/?theme=fleet&chart=1',
    //   'https://nff.maps.arcgis.com/apps/Embed/index.html?webmap=3d7060912fff43c0a151c097ba328f18&extent=-120.8593,37.9925,-90.6909,51.0109&zoom=true&previewImage=false&scale=true&legend=true&disable_scroll=true&theme=light',
    //   'https://public.tableau.com/views/ReportedBiasCrimes/BiasCrime?:embed=y&:showVizHome=no&:host_url=https%3A%2F%2Fpublic.tableau.com%2F&:embed_code_version=3&:tabs=yes&:toolbar=yes&:animate_transition=yes&:display_static_image=no&:display_spinner=no&:display_overlay=yes&:display_count=yes&:loadOrderID=0&:increment_view_count=no&width=660&height=1800',
    // ];
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
