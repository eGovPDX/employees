<?php

namespace Drupal\employees_org_chart\Plugin\views\style;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders view rows as a d3-org-chart, linking each row to its manager.
 *
 * Each row must be an entity, and the parent field must be an entity
 * reference to the same entity type (e.g. user.field_managers).
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: 'employees_org_chart',
  title: new TranslatableMarkup('Org chart (d3)'),
  help: new TranslatableMarkup('Displays rows as an interactive org chart, using a reference field as the parent.'),
  theme: 'employees_org_chart',
  display_types: ['normal'],
)]
class OrgChart extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * The cache backend for the computed chart data.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * The cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected CacheContextsManager $cacheContextsManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The field_permissions service, if that module is installed.
   *
   * @var object|null
   */
  protected ?object $fieldPermissions = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    $instance->cache = $container->get('cache.data');
    $instance->cacheContextsManager = $container->get('cache_contexts_manager');
    $instance->database = $container->get('database');
    $instance->languageManager = $container->get('language_manager');
    $instance->fieldPermissions = $container->has('field_permissions.permissions_service') ? $container->get('field_permissions.permissions_service') : NULL;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['name_field'] = ['default' => ''];
    $options['title_field'] = ['default' => ''];
    $options['group_field'] = ['default' => ''];
    $options['image_field'] = ['default' => ''];
    $options['image_style'] = ['default' => 'thumbnail'];
    $options['parent_field'] = ['default' => ''];
    $options['hide_isolated'] = ['default' => TRUE];
    $options['root_label'] = ['default' => ''];
    $options['top_level_titles'] = ['default' => ''];
    $options['levels'] = ['default' => 2];
    $options['compact'] = ['default' => TRUE];
    $options['height'] = ['default' => 700];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $field_options = ['' => $this->t('- None -')] + $this->displayHandler->getFieldLabels();
    $reference_options = ['' => $this->t('- None -')];
    $image_options = ['' => $this->t('- None -')];
    foreach ($this->displayHandler->getHandlers('field') as $id => $handler) {
      $type = $this->getFieldType($handler);
      if ($type === 'entity_reference') {
        $reference_options[$id] = $field_options[$id];
      }
      elseif ($type === 'image') {
        $image_options[$id] = $field_options[$id];
      }
    }

    $form['name_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Name field'),
      '#description' => $this->t('Rendered as plain text on each card.'),
      '#options' => $field_options,
      '#default_value' => $this->options['name_field'],
      '#required' => TRUE,
    ];
    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Job title field'),
      '#options' => $field_options,
      '#default_value' => $this->options['title_field'],
    ];
    $form['group_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Bureau field'),
      '#description' => $this->t('Shown next to the name and title in the "Find a person" suggestions.'),
      '#options' => $field_options,
      '#default_value' => $this->options['group_field'],
    ];
    $form['image_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Photo field'),
      '#description' => $this->t('People without a photo show their initials.'),
      '#options' => $image_options,
      '#default_value' => $this->options['image_field'],
    ];
    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Photo image style'),
      '#options' => array_map(
        fn($style) => $style->label(),
        $this->entityTypeManager->getStorage('image_style')->loadMultiple(),
      ),
      '#default_value' => $this->options['image_style'],
      '#states' => [
        'invisible' => [':input[name="style_options[image_field]"]' => ['value' => '']],
      ],
    ];
    $form['parent_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Manager field'),
      '#description' => $this->t('An entity reference to the same entity type as the rows. If it has several values, the first one that is also in the results is used.'),
      '#options' => $reference_options,
      '#default_value' => $this->options['parent_field'],
      '#required' => TRUE,
    ];
    $form['hide_isolated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide people with no manager and no reports'),
      '#description' => $this->t('People whose manager field is empty, or points to someone outside the results, are otherwise shown as top-level boxes.'),
      '#default_value' => $this->options['hide_isolated'],
    ];
    $form['root_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Top-level label'),
      '#description' => $this->t('When there is more than one top-level person, the one with the largest team is shown under a box with this label, and the rest are grouped in a collapsed "Not connected to the main chart" box. Defaults to the view title.'),
      '#default_value' => $this->options['root_label'],
    ];
    $form['top_level_titles'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Top-level job titles'),
      '#description' => $this->t('People without a manager whose job title is listed here (one per line, e.g. elected officials) are shown under the top box, beside the main chart, instead of in "Not connected to the main chart". Add <em>|Box label</em> to group them in a box, e.g. <em>Councilor|City Council</em>. Matching ignores case.'),
      '#default_value' => $this->options['top_level_titles'],
      '#rows' => 3,
    ];
    $form['levels'] = [
      '#type' => 'number',
      '#title' => $this->t('Levels shown initially'),
      '#description' => $this->t('1 shows only the top-level people; 2 also shows their direct reports, and so on.'),
      '#min' => 1,
      '#max' => 10,
      '#default_value' => $this->options['levels'],
    ];
    $form['compact'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Compact layout'),
      '#description' => $this->t('Stacks reports without their own reports into columns, which keeps managers with many reports readable.'),
      '#default_value' => $this->options['compact'],
    ];
    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Chart height (px)'),
      '#min' => 200,
      '#default_value' => $this->options['height'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    foreach (['name_field', 'parent_field'] as $option) {
      if (empty($this->options[$option]) || !isset($this->displayHandler->getHandlers('field')[$this->options[$option]])) {
        $errors[] = $this->t('Display "@display" uses the org chart style but its @option is not set to an existing field.', [
          '@display' => $this->displayHandler->display['display_title'],
          '@option' => $option,
        ]);
      }
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (empty($this->view->result)) {
      return [];
    }

    $chart_id = Html::getUniqueId('employees-org-chart-' . $this->view->id() . '-' . $this->view->current_display);
    return [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#chart_id' => $chart_id,
      '#attached' => [
        'library' => ['employees_org_chart/org-chart'],
        'drupalSettings' => [
          'employeesOrgChart' => [
            $chart_id => [
              'nodes' => $this->getNodes(),
              'levels' => (int) $this->options['levels'],
              'compact' => (bool) $this->options['compact'],
              'height' => (int) $this->options['height'],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Returns the chart nodes, from cache when possible.
   *
   * @return array
   *   Chart nodes, as passed to d3-org-chart.
   */
  protected function getNodes(): array {
    $cache_plugin = $this->displayHandler->getPlugin('cache');
    $max_age = $cache_plugin->getCacheMaxAge();
    if ($max_age === 0) {
      return $this->buildNodes();
    }

    // Field access depends on permissions, and labels on the language.
    $keys = $this->cacheContextsManager->convertTokensToKeys(['user.permissions', 'languages:language_interface'])->getKeys();
    $cid = implode(':', array_merge(['employees_org_chart', $this->view->id(), $this->view->current_display], $keys));
    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }

    $nodes = $this->buildNodes();
    $expire = $max_age === Cache::PERMANENT ? Cache::PERMANENT : \Drupal::time()->getRequestTime() + $max_age;
    $this->cache->set($cid, $nodes, $expire, $cache_plugin->getCacheTags());
    return $nodes;
  }

  /**
   * Builds the chart nodes from the view results.
   *
   * @return array
   *   Chart nodes, as passed to d3-org-chart.
   */
  protected function buildNodes(): array {
    // Views has loaded the entities, but reading thousands of field values
    // through the entity API is slow. Read each field for all rows with one
    // query instead.
    $entities = [];
    foreach ($this->view->result as $row) {
      if ($row->_entity instanceof FieldableEntityInterface) {
        $entities[(int) $row->_entity->id()] = $row->_entity;
      }
    }
    $ids = array_keys($entities);

    $manager_ids = $this->loadFieldValues($this->options['parent_field'], $entities, 'target_id', TRUE);
    $manager_ids += array_fill_keys($ids, []);
    $parents = $this->resolveParents($entities, $manager_ids);

    if ($this->options['hide_isolated']) {
      $has_reports = array_flip(array_filter($parents));
      foreach ($ids as $id) {
        if ($parents[$id] === NULL && !isset($has_reports[$id])) {
          unset($entities[$id], $parents[$id]);
        }
      }
    }

    $team_sizes = $this->getTeamSizes($parents);
    $direct_counts = array_count_values(array_filter($parents));
    $names = $this->loadTextValues($this->options['name_field'], $entities);
    $titles = $this->loadTextValues($this->options['title_field'], $entities);
    $groups = $this->loadTextValues($this->options['group_field'], $entities);
    $images = $this->loadImageUrls($entities);

    $people = [];
    foreach ($entities as $id => $entity) {
      $people[$id] = [
        'id' => (string) $id,
        'name' => $names[$id] ?? '',
        'title' => $titles[$id] ?? '',
        'group' => $groups[$id] ?? '',
        'directReports' => $direct_counts[$id] ?? 0,
        'teamSize' => $team_sizes[$id],
        'image' => $images[$id] ?? NULL,
        // Skipping path processing avoids thousands of alias lookups; the
        // redirect module sends visitors on to the alias.
        'url' => $entity->toUrl('canonical', ['path_processing' => FALSE])->toString(),
      ];
    }

    // Order siblings by team size, then name, so managers come before
    // individual contributors. d3-org-chart keeps the data order.
    uksort($people, fn($a, $b) => [$team_sizes[$b], $people[$a]['name']] <=> [$team_sizes[$a], $people[$b]['name']]);

    // d3-org-chart needs exactly one root. When there are several top-level
    // people, they go under a virtual top box: the one with the largest team
    // (the main chart), then people with a configured top-level job title
    // (e.g. elected officials, who have no manager by design), either
    // directly or in a named box such as "City Council". The rest (usually
    // managers whose own manager field is empty) are grouped in a collapsed
    // "Not connected" box.
    $virtual = [];
    $order = array_keys($people);
    $roots = array_values(array_filter(array_keys($people), fn($id) => $parents[$id] === NULL));
    if (count($roots) > 1) {
      $virtual['_root'] = [
        'id' => '_root',
        'parentId' => NULL,
        'name' => $this->options['root_label'] ?: (string) $this->view->getTitle(),
        'virtual' => TRUE,
      ];
      $main = array_shift($roots);
      $parents[$main] = '_root';

      // The top row beside the main chart, in team size order.
      $row = [];
      $unconnected = [];
      $box_sizes = [];
      $top_level_titles = $this->getTopLevelTitles();
      foreach ($roots as $id) {
        $box_label = $top_level_titles[mb_strtolower($people[$id]['title'])] ?? NULL;
        if ($box_label === NULL) {
          $unconnected[] = $id;
          continue;
        }
        // Keep their teams closed at first, so the overview stays on the main
        // chart.
        $people[$id]['collapsed'] = TRUE;
        if ($box_label === '') {
          $parents[$id] = '_root';
          $row[] = $id;
          continue;
        }
        $box_id = '_box_' . count($box_sizes);
        if (isset($box_sizes[$box_label])) {
          $box_id = $box_sizes[$box_label]['id'];
        }
        else {
          $box_sizes[$box_label] = ['id' => $box_id, 'count' => 0];
          $row[] = $box_id;
        }
        $box_sizes[$box_label]['count']++;
        $parents[$id] = $box_id;
      }
      foreach ($box_sizes as $label => $box) {
        $virtual[$box['id']] = [
          'id' => $box['id'],
          'parentId' => '_root',
          'name' => $label,
          'title' => (string) $this->formatPlural($box['count'], '1 person', '@count people'),
          'virtual' => TRUE,
        ];
      }

      if ($unconnected) {
        $virtual['_unconnected'] = [
          'id' => '_unconnected',
          'parentId' => '_root',
          'name' => (string) $this->t('Not connected to the main chart'),
          'title' => (string) $this->formatPlural(count($unconnected), '1 manager with no manager on record', '@count managers with no manager on record'),
          'virtual' => TRUE,
          'collapsed' => TRUE,
        ];
        foreach ($unconnected as $id) {
          $parents[$id] = '_unconnected';
        }
        $row[] = '_unconnected';
      }

      // Put the main chart in the middle of the top row, so the top box sits
      // right above it. d3-org-chart orders siblings by their data order.
      $left = array_slice($row, 0, intdiv(count($row), 2));
      $order = array_unique(array_merge($left, [$main], array_keys($people), array_keys($virtual)));
    }

    $nodes = [];
    foreach ($order as $id) {
      $nodes[] = $virtual[$id] ?? $people[$id] + ['parentId' => isset($parents[$id]) ? (string) $parents[$id] : NULL];
    }
    return $nodes;
  }

  /**
   * Returns the configured top-level job titles.
   *
   * Each line is a job title, optionally followed by "|Box label" to group
   * those people in a box, e.g. "Councilor|City Council".
   *
   * @return string[]
   *   Box labels ('' for none), keyed by lowercase job title.
   */
  protected function getTopLevelTitles(): array {
    $titles = [];
    foreach (preg_split('/\R/', (string) $this->options['top_level_titles']) as $line) {
      [$title, $label] = array_map('trim', explode('|', $line, 2)) + ['', ''];
      if ($title !== '') {
        $titles[mb_strtolower($title)] = $label;
      }
    }
    return $titles;
  }

  /**
   * Picks one in-chart manager per person and breaks reporting loops.
   *
   * @param array $people
   *   Result rows keyed by entity ID.
   * @param array $manager_ids
   *   Candidate manager IDs, in field order, keyed by entity ID.
   *
   * @return array
   *   Parent entity ID (or NULL for top-level people), keyed by entity ID.
   */
  protected function resolveParents(array $people, array $manager_ids): array {
    $parents = [];
    foreach ($manager_ids as $id => $candidates) {
      $parents[$id] = NULL;
      foreach ($candidates as $candidate) {
        if ($candidate !== $id && isset($people[$candidate])) {
          $parents[$id] = $candidate;
          break;
        }
      }
    }

    // A loop (A reports to B, B reports to A) would make the tree invalid.
    // Detach the first person found on each loop so they become top-level.
    foreach (array_keys($parents) as $id) {
      $seen = [];
      for ($current = $parents[$id]; $current !== NULL; $current = $parents[$current]) {
        if ($current === $id) {
          $parents[$id] = NULL;
          break;
        }
        if (isset($seen[$current])) {
          break;
        }
        $seen[$current] = TRUE;
      }
    }
    return $parents;
  }

  /**
   * Counts everyone under each person, directly or indirectly.
   *
   * @param array $parents
   *   Parent entity ID (or NULL), keyed by entity ID. Must contain no loops.
   *
   * @return int[]
   *   Team size keyed by entity ID.
   */
  protected function getTeamSizes(array $parents): array {
    $sizes = array_fill_keys(array_keys($parents), 0);
    foreach (array_keys($parents) as $id) {
      for ($current = $parents[$id]; $current !== NULL; $current = $parents[$current]) {
        $sizes[$current]++;
      }
    }
    return $sizes;
  }

  /**
   * Loads one column of a field for many entities, with one query per chunk.
   *
   * @param string $field
   *   The Views field ID. Fields reached through a relationship are not
   *   supported.
   * @param \Drupal\Core\Entity\FieldableEntityInterface[] $entities
   *   The entities, keyed by ID.
   * @param string $column
   *   The field property to load, e.g. 'value' or 'target_id'.
   * @param bool $all_deltas
   *   TRUE to return every value in delta order, FALSE for the first only.
   *
   * @return array
   *   Values keyed by entity ID (a list of values if $all_deltas). Entities
   *   with no value, or whose value the viewer may not see, are left out.
   */
  protected function loadFieldValues(string $field, array $entities, string $column, bool $all_deltas = FALSE): array {
    $handler = $this->view->field[$field] ?? NULL;
    if (!$entities || !$handler instanceof EntityField || $handler->options['relationship'] !== 'none') {
      return [];
    }
    $entity_type_id = $handler->getEntityType();
    $field_name = $handler->definition['field_name'];
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if (!$storage instanceof SqlEntityStorageInterface) {
      return [];
    }
    $definition = $this->entityFieldManager->getFieldDefinitions($entity_type_id, reset($entities)->bundle())[$field_name] ?? NULL;
    if (!$definition) {
      return [];
    }

    $mapping = $storage->getTableMapping();
    $table = $mapping->getFieldTableName($field_name);
    $value_column = $mapping->getFieldColumnName($definition->getFieldStorageDefinition(), $column);
    $is_shared_table = $mapping->allowsSharedTableStorage($definition->getFieldStorageDefinition());
    $id_column = $is_shared_table ? $storage->getEntityType()->getKey('id') : 'entity_id';

    $values = [];
    foreach (array_chunk(array_keys($entities), 5000) as $chunk) {
      $query = $this->database->select($table, 't')
        ->fields('t', [$id_column, $value_column])
        ->condition("t.$id_column", $chunk, 'IN')
        ->isNotNull("t.$value_column");
      if (!$is_shared_table) {
        $query->condition('t.deleted', 0);
        $query->orderBy('t.delta');
      }
      if ($storage->getEntityType()->isTranslatable()) {
        $query->condition('t.langcode', $this->languageManager->getDefaultLanguage()->getId());
      }
      foreach ($query->execute() as $record) {
        $id = (int) $record->$id_column;
        if ($all_deltas) {
          $values[$id][] = $record->$value_column;
        }
        elseif (!isset($values[$id])) {
          $values[$id] = $record->$value_column;
        }
      }
    }

    // Field access is checked once for the whole field when the viewer can
    // see it on every entity, and per entity (slow) only otherwise.
    if (!$this->canViewFieldOnAll($definition)) {
      foreach (array_keys($values) as $id) {
        if (!$entities[$id]->get($field_name)->access('view')) {
          unset($values[$id]);
        }
      }
    }
    return $values;
  }

  /**
   * Loads a field as plain text for many entities.
   *
   * The user name field uses the display name instead, read in bulk from the
   * realname module's table when available.
   *
   * @return string[]
   *   Text keyed by entity ID.
   */
  protected function loadTextValues(string $field, array $entities): array {
    $handler = $this->view->field[$field] ?? NULL;
    if (!$handler instanceof EntityField || !$entities) {
      return [];
    }
    if (reset($entities) instanceof UserInterface && $handler->definition['field_name'] === 'name') {
      $names = [];
      if ($this->database->schema()->tableExists('realname')) {
        foreach (array_chunk(array_keys($entities), 5000) as $chunk) {
          $names += $this->database->query('SELECT [uid], [realname] FROM {realname} WHERE [uid] IN (:uids[]) AND [realname] <> :empty', [':uids[]' => $chunk, ':empty' => ''])->fetchAllKeyed();
        }
      }
      foreach (array_diff_key($entities, $names) as $id => $entity) {
        $names[$id] = $entity->label();
      }
      return array_map(fn($name) => trim((string) $name), $names);
    }
    return array_map(fn($value) => trim((string) $value), $this->loadFieldValues($field, $entities, 'value'));
  }

  /**
   * Loads styled photo URLs for many entities.
   *
   * @return string[]
   *   Relative URLs keyed by entity ID.
   */
  protected function loadImageUrls(array $entities): array {
    $fids = $this->loadFieldValues($this->options['image_field'], $entities, 'target_id');
    if (!$fids) {
      return [];
    }
    $uris = [];
    foreach (array_chunk(array_unique($fids), 5000) as $chunk) {
      $uris += $this->database->query('SELECT [fid], [uri] FROM {file_managed} WHERE [fid] IN (:fids[])', [':fids[]' => $chunk])->fetchAllKeyed();
    }
    $style = $this->entityTypeManager->getStorage('image_style')->load($this->options['image_style']);
    $urls = [];
    foreach ($fids as $id => $fid) {
      if (isset($uris[$fid])) {
        $url = $style ? $style->buildUrl($uris[$fid]) : $this->fileUrlGenerator->generateAbsoluteString($uris[$fid]);
        $urls[$id] = $this->fileUrlGenerator->transformRelative($url);
      }
    }
    return $urls;
  }

  /**
   * Checks whether the viewer may see a field on every entity.
   */
  protected function canViewFieldOnAll(FieldDefinitionInterface $definition): bool {
    $account = $this->view->getUser();
    $access_handler = $this->entityTypeManager->getAccessControlHandler($definition->getTargetEntityTypeId());
    if (!$access_handler->fieldAccess('view', $definition, $account)) {
      return FALSE;
    }
    // field_permissions only applies its rules when given a specific entity,
    // so ask it directly. Its "view own" permission only covers some entities.
    if ($this->fieldPermissions && method_exists($this->fieldPermissions, 'hasFieldViewAccessForEveryEntity')) {
      return $this->fieldPermissions->hasFieldViewAccessForEveryEntity($account, $definition);
    }
    return TRUE;
  }

  /**
   * Returns the field type behind a Views field handler, if any.
   */
  protected function getFieldType($handler): ?string {
    if (!$handler instanceof EntityField) {
      return NULL;
    }
    $definitions = $this->entityFieldManager->getFieldStorageDefinitions($handler->getEntityType());
    return isset($definitions[$handler->definition['field_name']]) ? $definitions[$handler->definition['field_name']]->getType() : NULL;
  }

}
