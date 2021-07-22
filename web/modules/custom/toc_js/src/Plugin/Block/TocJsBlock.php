<?php

namespace Drupal\toc_js\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Html;

/**
 * Provides a 'SocialSimpleBlock' block.
 *
 * @Block(
 *  id = "toc_js_block",
 *  admin_label = @Translation("Toc.js block"),
 * )
 */
class TocJsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new SocialSimpleBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $current_route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => $this->t('Table of content'),
      'selectors' => 'h2, h3',
      'selectors_minimum' => 0,
      'container' => '.node',
      'prefix' => 'toc',
      'list_type' => 'ul',
      'back_to_top' => 0,
      'back_to_top_label' => $this->t('Back to top'),
      'smooth_scrolling' => 1,
      'scroll_to_offset' => 100,
      'highlight_on_scroll' => 1,
      'highlight_offset' => 100,
      'sticky' => 0,
      'sticky_offset' => 0,
      'sticky_stop' => '',
      'sticky_stop_padding' => 0
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('the title to use for the table of contents.'),
      '#default_value' => $this->configuration['title'],
    ];

    $form['selectors'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selectors'),
      '#description' => $this->t('Elements to use as headings. Each element separated by comma.'),
      '#default_value' => $this->configuration['selectors'],
    ];

    $form['selectors_minimum'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum elements'),
      '#description' => $this->t('Set a minimum of elements to display the toc. Set 0 to always display the TOC.'),
      '#default_value' => $this->configuration['selectors_minimum'],
    ];

    $form['container'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container'),
      '#description' => $this->t('Element to find all selectors in.'),
      '#default_value' => $this->configuration['container'],
    ];

    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#description' => $this->t('Prefix for anchor tags and class names.'),
      '#default_value' => $this->configuration['prefix'],
    ];

    $form['list_type'] = [
      '#type' => 'select',
      '#title' => $this->t('List type'),
      '#description' => $this->t('Select the list type to use.'),
      '#default_value' => $this->configuration['prefix'],
      '#options' => [
        'ul' => $this->t('Unordered HTML list (ul)'),
        'ol' => $this->t('Ordered HTML list (ol)'),
      ],
    ];

    $form['back_to_top'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Back to top'),
      '#description' => $this->t('Add a back to top link on heading.'),
      '#default_value' => $this->configuration['back_to_top'],
    ];

    $form['back_to_top_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Back to top label'),
      '#description' => $this->t('The back to top link label.'),
      '#default_value' => $this->configuration['back_to_top_label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[back_to_top]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['smooth_scrolling'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Smooth scrolling'),
      '#description' => $this->t('Enable or disable smooth scrolling on click.'),
      '#default_value' => $this->configuration['smooth_scrolling'],
    ];

    $form['scroll_to_offset'] = [
      '#type' => 'number',
      '#title' => $this->t('ScrollTo offset'),
      '#description' => $this->t('Offset to trigger with smooth scrolling enabled.'),
      '#default_value' => $this->configuration['scroll_to_offset'],
      '#states' => [
        'visible' => [
          ':input[name="settings[smooth_scrolling]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['highlight_on_scroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('highlight On Scroll'),
      '#description' => $this->t('Add class to heading that is currently in focus.'),
      '#default_value' => $this->configuration['highlight_on_scroll'],
    ];

    $form['highlight_offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Highlight offset'),
      '#description' => $this->t('Offset to trigger the next headline.'),
      '#default_value' => $this->configuration['highlight_offset'],
      '#states' => [
        'visible' => [
          ':input[name="settings[highlight_on_scroll]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['sticky'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sticky'),
      '#description' => $this->t('Stick the toc on window scroll.'),
      '#default_value' => $this->configuration['sticky'],
    ];

    $form['sticky_offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Sticky offset'),
      '#description' => $this->t('The offset (in px) to apply when the toc is sticky.'),
      '#default_value' => $this->configuration['sticky_offset'],
      '#states' => [
        'visible' => [
          ':input[name="settings[sticky]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['sticky_stop'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sticky stop'),
      '#description' => $this->t('Selector to use as a sticky stop. Leave empty if you don\'t need a stop for the sticky toc.'),
      '#default_value' => $this->configuration['sticky_stop'],
      '#states' => [
        'visible' => [
          ':input[name="settings[sticky]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['sticky_stop_padding'] = [
      '#type' => 'number',
      '#title' => $this->t('Sticky Stop padding (px)'),
      '#description' => $this->t('The padding to apply before the sticky stop (in pixels).'),
      '#default_value' => $this->configuration['sticky_stop_padding'],
      '#states' => [
        'visible' => [
          ':input[name="settings[sticky]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['selectors'] = $form_state->getValue('selectors');
    $this->configuration['selectors_minimum'] = $form_state->getValue('selectors_minimum');
    $this->configuration['container'] = $form_state->getValue('container');
    $this->configuration['prefix'] = $form_state->getValue('prefix');
    $this->configuration['list_type'] = $form_state->getValue('list_type');
    $this->configuration['back_to_top'] = $form_state->getValue('back_to_top');
    $this->configuration['back_to_top_label'] = $form_state->getValue('back_to_top_label');
    $this->configuration['smooth_scrolling'] = $form_state->getValue('smooth_scrolling');
    $this->configuration['scroll_to_offset'] = $form_state->getValue('scroll_to_offset');
    $this->configuration['highlight_on_scroll'] = $form_state->getValue('highlight_on_scroll');
    $this->configuration['highlight_offset'] = $form_state->getValue('highlight_offset');
    $this->configuration['sticky'] = $form_state->getValue('sticky');
    $this->configuration['sticky_offset'] = $form_state->getValue('sticky_offset');
    $this->configuration['sticky_stop'] = $form_state->getValue('sticky_stop');
    $this->configuration['sticky_stop_padding'] = $form_state->getValue('sticky_stop_padding');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $toc_js_settings = $this->configuration;

    // toc-js class is used to initialize the toc.
    $attributes = new Attribute(['class' => ['toc-js']]);
    $attributes->setAttribute('id', 'toc-js-block-' . Html::cleanCssIdentifier($this->pluginId));
    $title_attributes = new Attribute(['class' => ['toc-title', 'h2']]);

    if ($this->configuration['sticky']) {
      $attributes->addClass('sticky');
    }

    foreach ($toc_js_settings as $name => $setting) {
      if ($name == 'toc_js_active' || $name == 'title') {
        continue;
      }
      $data_name = 'data-' . Html::cleanCssIdentifier($name);
      $attributes->setAttribute($data_name, $setting);
    }

    // Provide some entity context if available.
    $entity = NULL;
    if ($node = $this->currentRouteMatch->getParameter('node')) {
      $entity = $node;
    }
    /** @var \Drupal\taxonomy\TermInterface $taxonomy_term */
    elseif ($taxonomy_term = $this->currentRouteMatch->getParameter('taxonomy_term')) {
      $entity = $taxonomy_term;
    }

    $build['toc_js'] = [
      '#theme' => 'toc_js',
      '#title' => $this->configuration['title'],
      '#tag' => 'div',
      '#title_attributes' => $title_attributes,
      '#attributes' => $attributes,
      '#entity' => $entity,
      '#attached' => [
        'library' => [
          'toc_js/toc',
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    /** @var \Drupal\node\NodeInterface $node */
    if ($node = $this->currentRouteMatch->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), $node->getCacheTags());
    }
    /** @var \Drupal\taxonomy\TermInterface $taxonomy_term */
    elseif ($taxonomy_term = $this->currentRouteMatch->getParameter('taxonomy_term')) {
      return Cache::mergeTags(parent::getCacheTags(), $taxonomy_term->getCacheTags());
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.path']);
  }

}
