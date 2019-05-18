<?php

namespace Drupal\projects_stats\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use SimpleXMLElement;
use Drupal\projects_stats\ProjectsStatsSlackServiceInterface;

/**
 * Provides a 'ProjectsStatsBlock' block.
 *
 * @Block(
 *  id = "projects_stats",
 *  admin_label = @Translation("Projects Stats"),
 *  category = @Translation("Web services"),
 * )
 */
class ProjectsStatsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\projects_stats\ProjectsStatsSlackServiceInterface
   *   The Slack service.
   */
  protected $slackService;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\projects_stats\ProjectsStatsSlackServiceInterface $slack_service
   *   The Slack service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ProjectsStatsSlackServiceInterface $slack_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->slackService = $slack_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('projects_stats.slack_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_type' => 'table',
      'machine_names' => '',
      'description' => '',
      'additional_columns' => [],
      'sort_by' => 'count',
      'show_downloads' => TRUE,
      'collapsible_list' => FALSE,
      'cache_age' => 86400,
      'classes' => '',
      'target' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['display_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display type'),
      '#options' => [
        'table' => $this->t('Table'),
        'list' => $this->t('List'),
      ],
      '#default_value' => $this->configuration['display_type'],
    ];

    $form['machine_names'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Project machine names'),
      '#description' => $this->t('Specify modules/themes/distributions by using their machine names. You can also enter user ID to fetch all projects associated with that user. Separate multiple values by a comma.'),
      '#default_value' => $this->configuration['machine_names'],
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Description text is displayed above the projects list.'),
      '#default_value' => $this->configuration['description'],
    ];

    $form['additional_columns'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Additional columns'),
      '#options' => [
        'project_usage' => $this->t('Usage'),
        'created' => $this->t('Created date'),
        'changed' => $this->t('Last modified date'),
        'last_version' => $this->t('Last released version'),
      ],
      '#default_value' => $this->configuration['additional_columns'],
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[display_type]"]' => ['value' => 'table'],
          ],
        ],
      ],
    ];

    $form['show_downloads'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show download count'),
      '#default_value' => $this->configuration['show_downloads'],
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[display_type]"]' => ['value' => 'list'],
          ],
        ],
      ],
    ];

    $form['collapsible_list'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make list collapsible'),
      '#default_value' => $this->configuration['collapsible_list'],
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[display_type]"]' => ['value' => 'list'],
          ],
        ],
      ],
    ];

    $form['sort_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort by'),
      '#options' => [
        'count' => $this->t('Download count'),
        'name' => $this->t('Name'),
        'no' => $this->t('No sort'),
      ],
      '#default_value' => $this->configuration['sort_by'],
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[display_type]"]' => ['value' => 'table'],
          ],
        ],
      ],
    ];

    $form['cache_age'] = [
      '#type' => 'select',
      '#title' => $this->t('Block cache age'),
      '#options' => [
        21600 => $this->t('6 hours'),
        43200 => $this->t('12 hours'),
        86400 => $this->t('24 hours'),
        172800 => $this->t('2 days'),
        432000 => $this->t('5 days'),
        604800 => $this->t('7 days'),
        864000 => $this->t('10 days'),
        1209600 => $this->t('14 days'),
        0 => $this->t('No cache'),
      ],
      '#default_value' => $this->configuration['cache_age'],
    ];

    $form['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table/list classes'),
      '#default_value' => $this->configuration['classes'],
      '#description' => $this->t('Specify CSS classes for table/list. Separate multiple classes with empty space.'),
    ];

    $form['target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open project links in the new tab'),
      '#default_value' => $this->configuration['target'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['display_type'] = $form_state->getValue('display_type');
    $this->configuration['machine_names'] = $form_state->getValue('machine_names');
    $this->configuration['description'] = $form_state->getValue('description');
    $this->configuration['additional_columns'] = $form_state->getValue('additional_columns');
    $this->configuration['sort_by'] = $form_state->getValue('sort_by');
    $this->configuration['show_downloads'] = $form_state->getValue('show_downloads');
    $this->configuration['collapsible_list'] = $form_state->getValue('collapsible_list');
    $this->configuration['cache_age'] = $form_state->getValue('cache_age');
    $this->configuration['classes'] = $form_state->getValue('classes');
    $this->configuration['target'] = $form_state->getValue('target');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $machine_names = $this->configuration['machine_names'];
    $machine_names = array_map('trim', explode(',', $machine_names));

    if ($this->configuration['display_type'] == 'table') {
      return $this->generateTable($machine_names);
    }
    else {
      return $this->generateList($machine_names);
    }
  }

  /**
   * Generates table.
   *
   * @param $machine_names
   *
   * @return array
   */
  protected function generateTable($machine_names) {
    $description = $this->configuration['description'];
    $additional_columns = $this->configuration['additional_columns'];
    $sort_by = $this->configuration['sort_by'];
    $cache_age = $this->configuration['cache_age'];
    $classes = $this->configuration['classes'];
    $target = $this->configuration['target'];

    $table_head = [$this->t('Title'), $this->t('Downloads')];
    foreach ($additional_columns as $key => $value) {
      if ($value) {
        $key = str_replace('_', ' ', $key);
        $key = ucfirst($key);
        $key = $this->t($key);
        $table_head[] = $key;
      }
    }

    foreach ($machine_names as $key => $machine_name) {
      if (is_numeric($machine_name)) {
        unset($machine_names[$key]);
        foreach (['project_distribution', 'project_module', 'project_theme'] as $project_type) {
          $machine_names_by_author = $this->slackService->getProjectsByAuthor($project_type, $machine_name);
          $machine_names = array_merge($machine_names, $machine_names_by_author);
        }
      }
    }
    $machine_names = array_unique($machine_names);

    $table_body = [];
    foreach ($machine_names as $machine_name) {
      $stats = $this->getStats(trim($machine_name));
      if (empty($stats['project_type']) || empty($stats['name']) || $stats['download_count'] == NULL) {
        continue;
      }

      $table_body_row = [
        'title' => $stats['name'],
        'url' => $stats['url'],
        'downloads' => number_format($stats['download_count'], 0, '.', ','),
        'downloads_raw' => $stats['download_count'],
      ];

      foreach ($additional_columns as $key => $value) {
        if ($value && isset($stats[$key])) {
          $table_body_row[$key] = $this->flattenValue($stats[$key]);
        }
      }

      $table_body[] = $table_body_row;
    }

    if ($sort_by != 'no') {
      usort($table_body, [$this, 'sortModulesList']);
    }

    return [
      '#theme' => 'projects_stats_table',
      '#classes' => ltrim($classes . ' block-projects-stats'),
      '#description' => $description,
      '#table_head' => $table_head,
      '#table_body' => $table_body,
      '#target' => $target == TRUE ? '_blank' : '_self',
      '#cache' => ['max-age' => $cache_age],
    ];
  }

  /**
   * Generates list.
   *
   * @param $machine_names
   *
   * @return array
   */
  protected function generateList($machine_names) {
    $show_downloads = $this->configuration['show_downloads'];
    $description = $this->configuration['description'];
    $cache_age = $this->configuration['cache_age'];
    $classes = $this->configuration['classes'];
    $target = $this->configuration['target'];

    foreach ($machine_names as $key => $machine_name) {
      if (is_numeric($machine_name)) {
        unset($machine_names[$key]);
        foreach (['project_distribution', 'project_module', 'project_theme'] as $project_type) {
          $machine_names_by_author = $this->slackService->getProjectsByAuthor($project_type, $machine_name);
          $machine_names = array_merge($machine_names, $machine_names_by_author);
        }
      }
    }
    $machine_names = array_unique($machine_names);

    $all_projects = [];
    foreach ($machine_names as $machine_name) {
      $stats = $this->getStats(trim($machine_name));
      if (empty($stats['project_type']) || empty($stats['name']) || $stats['download_count'] == NULL) {
        continue;
      }
      $project_type = str_replace('project_', '', $stats['project_type']) . 's';
      $all_projects[ucfirst($project_type)][] = $stats;
    }

    return [
      '#theme' => 'projects_stats_list',
      '#classes' => ltrim($classes . ' block-projects-stats'),
      '#description' => $description,
      '#all_projects' => $all_projects,
      '#show_downloads' => $show_downloads,
      '#target' => $target == TRUE ? '_blank' : '_self',
      '#cache' => ['max-age' => $cache_age],
      '#attached' => [
        'library' => [
          'projects_stats/projects_stats',
        ],
        'drupalSettings' => [
          'collapsibleList' => $this->configuration['collapsible_list'],
        ],
      ],
    ];
  }

  /**
   * Get data from drupal.org API endpoint.
   */
  protected function getStats($machine_name) {
    $base_url = 'https://www.drupal.org/api-d7/node.json?field_project_machine_name=';
    $client = new Client();
    try {
      $res = $client->get($base_url . $machine_name, ['http_errors' => FALSE]);
      $body = $res->getBody();
      $decoded_body = json_decode($body, TRUE);
      if (!isset($decoded_body['list'][0])) {
        return [
          'project_type' => '',
          'name' => '',
          'url' => '',
          'download_count' => 'n/a',
          'project_usage' => 'n/a',
          'created' => $this->t('n/a'),
          'changed' => $this->t('n/a'),
          'last_version' => $this->t('n/a'),
        ];
      }
      $project_type = $decoded_body['list'][0]['type'];
      $name = $decoded_body['list'][0]['title'];
      $download_count = $decoded_body['list'][0]['field_download_count'];
      $project_usage = $decoded_body['list'][0]['project_usage'];
      $created = $decoded_body['list'][0]['created'];
      $version_data = $this->getLastVersion($machine_name);
      $changed = $version_data['changed'];
      $last_version = $version_data['last_version'];
      $stats = [
        'project_type' => $project_type,
        'name' => $name,
        'url' => Url::fromUri('https://www.drupal.org/project/' . trim($machine_name)),
        'download_count' => $download_count,
        'project_usage' => $project_usage,
        'created' => date('d-m-Y', $created),
        'changed' => $changed,
        'last_version' => $last_version,
      ];
      return $stats;
    }
    catch (RequestException $e) {
      drupal_set_message($e->getMessage());
      $stats = [
        'project_type' => '',
        'name' => '',
        'url' => '',
        'download_count' => 'n/a',
        'project_usage' => 'n/a',
        'created' => $this->t('n/a'),
        'changed' => $this->t('n/a'),
        'last_version' => $this->t('n/a'),
      ];
      return $stats;
    }
  }

  /**
   * Get release data from drupal.org API endpoint.
   */
  protected function getLastVersion($machine_name) {
    $client = new Client();
    try {
      $res = $client->get("https://updates.drupal.org/release-history/$machine_name/all", ['http_errors' => FALSE]);
      $xml = $res->getBody()->getContents();
      $versions = new SimpleXMLElement($xml);
      $last_version = isset($versions->releases->release->version) ? $versions->releases->release->version : 'n/a';
      $changed = isset($versions->releases->release->date) ? date('d-m-Y', $versions->releases->release->date->__toString()) : 'n/a';
      return [
        'last_version' => $last_version,
        'changed' => $changed,
      ];
    }
    catch (RequestException $e) {
      drupal_set_message($e->getMessage());
      return [
        'last_version' => $this->t('n/a'),
        'changed' => $this->t('n/a'),
      ];
    }
  }

  /**
   * Sort projects.
   */
  protected function sortModulesList($a, $b) {
    $sort_by = $this->configuration['sort_by'];
    if ($sort_by == 'count') {
      return $a['downloads_raw'] < $b['downloads_raw'];
    }
    else {
      return strcmp($a['title'][0], $b['title'][0]);
    }
  }

  /**
   * Flattens array.
   */
  protected function flattenValue($data) {
    if (!is_array($data)) {
      return $data;
    }

    $flat = [];

    foreach ($data as $key => $value) {
      $flat[] = $key . ': ' . $value;
    }

    return implode(', ', $flat);
  }

}
