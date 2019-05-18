<?php

namespace Drupal\drupal_content_sync\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\drupal_content_sync\Entity\Flow;
use Drupal\drupal_content_sync\Entity\Pool;
use Drupal\drupal_content_sync\ExportIntent;
use Drupal\drupal_content_sync\ImportIntent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\drupal_content_sync\Plugin\Type\EntityHandlerPluginManager;
use Drupal\drupal_content_sync\Plugin\Type\FieldHandlerPluginManager;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Form handler for the Flow add and edit forms.
 */
class FlowForm extends EntityForm {

  /**
   * @var string DRUPAL_CONTENT_SYNC_PREVIEW_FIELD
   *    The name of the view mode that must be present to allow teaser previews.
   */
  const DRUPAL_CONTENT_SYNC_PREVIEW_FIELD = 'drupal_content_sync_preview';

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfoService;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\drupal_content_sync\Plugin\Type\EntityHandlerPluginManager
   */
  protected $entityPluginManager;

  /**
   * @var \Drupal\drupal_content_sync\Plugin\Type\FieldHandlerPluginManager
   */
  protected $fieldPluginManager;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The config factory to load configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs an object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity query.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info_service
   *   The bundle info service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\drupal_content_sync\Plugin\Type\EntityHandlerPluginManager $entity_plugin_manager
   *   The drupal content sync entity manager.
   * @param \Drupal\drupal_content_sync\Plugin\Type\FieldHandlerPluginManager $field_plugin_manager
   *   The drupal content sync field plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The messenger service.
   * @param \GuzzleHttp\Client $http_client
   *   The http client to connect to API Unify.
   */
  public function __construct(EntityTypeManager $entity_type_manager,
                              EntityTypeBundleInfoInterface $bundle_info_service,
                              EntityFieldManager $entity_field_manager,
                              EntityHandlerPluginManager $entity_plugin_manager,
                              FieldHandlerPluginManager $field_plugin_manager,
                              MessengerInterface $messenger,
                              ConfigFactory $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfoService = $bundle_info_service;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityPluginManager = $entity_plugin_manager;
    $this->fieldPluginManager = $field_plugin_manager;
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.dcs_entity_handler'),
      $container->get('plugin.manager.dcs_field_handler'),
      $container->get('messenger'),
      $container->get('config.factory')
    );
  }

  /**
   * A sync handler has been updated, so the options must be updated as well.
   * We're simply reloading the table in this case.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   *   The new sync_entities table.
   */
  public function updateSyncHandler($form, FormStateInterface $form_state) {
    return $form['sync_entities'];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    // Before a flow can be created, at least one pool must exist.
    // Get all pool entities.
    $pool_entities = Pool::getAll();

    if (empty($pool_entities)) {
      global $base_url;
      $path = Url::fromRoute('drupal_content_sync.dcs_pool.pool_required')->toString();
      $response = new RedirectResponse($base_url . $path);
      $response->send();
    }

    $export_option_labels = [
      ExportIntent::EXPORT_DISABLED => $this->t('Disabled')->render(),
      ExportIntent::EXPORT_AUTOMATICALLY => $this->t('All')->render(),
      ExportIntent::EXPORT_AS_DEPENDENCY => $this->t('Referenced')->render(),
      ExportIntent::EXPORT_MANUALLY => $this->t('Manually')->render(),
    ];
    $export_option_labels_fields = [
      ExportIntent::EXPORT_DISABLED => $this->t('No')->render(),
      ExportIntent::EXPORT_AUTOMATICALLY => $this->t('Yes')->render(),
    ];

    $import_option_labels = [
      ImportIntent::IMPORT_DISABLED => $this->t('Disabled')->render(),
      ImportIntent::IMPORT_AUTOMATICALLY => $this->t('All')->render(),
      ImportIntent::IMPORT_AS_DEPENDENCY => $this->t('Referenced')->render(),
      ImportIntent::IMPORT_MANUALLY => $this->t('Manually')->render(),
    ];

    $import_option_labels_fields = [
      ImportIntent::IMPORT_DISABLED => $this->t('No')->render(),
      ImportIntent::IMPORT_AUTOMATICALLY => $this->t('Yes')->render(),
    ];

    $form = parent::form($form, $form_state);

    $form['#attached']['library'][] = 'drupal_content_sync/flow-form';

    /**
     * @var \Drupal\drupal_content_sync\Entity\Flow $flow
     */
    $flow = $this->entity;

    $def_sync_entities = $flow->{'sync_entities'};

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $flow->label(),
      '#description' => $this->t("An administrative name describing the workflow intended to be achieved with this synchronization."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $flow->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['name'],
      ],
      '#disabled' => !$flow->isNew(),
    ];

    $entity_types = $this->bundleInfoService->getAllBundleInfo();

    // Remove the Drupal Content Sync Meta Info entity type form the array.
    unset($entity_types['dcs_meta_info']);

    $display_modes = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->loadMultiple();

    $display_modes_ids = array_keys($display_modes);

    $field_map = $this->entityFieldManager->getFieldMap();

    $entity_table = [
      '#type' => 'table',
      '#prefix' => '<div id="sync-entities-table">',
      '#suffix' => '</div>',
      '#sticky' => TRUE,
      '#header' => array_merge([
        $this->t('Bundle'),
        $this->t('Handler'),
        $this->t('Handler settings'),
        $this->t('Export'),
        $this->t('Export pool configuration'),
        $this->t('Pool export widget type'),
        $this->t('Export deletion settings'),
        $this->t('Import'),
        $this->t('Import pool configuration'),
        $this->t('Import deletion settings'),
        $this->t('Import updates'),
        $this->t('Preview'),
      ]),
    ];

    $input = $form_state->getValue('sync_entities');

    foreach ($entity_types as $type_key => $entity_type) {
      // This entity type doesn't contain any fields.
      if (!isset($field_map[$type_key])) {
        continue;
      }

      // Add information text for paragraphs that a specific commit is required.
      if ($type_key == 'paragraph') {
        $markup = '<h2>' . str_replace('_', ' ', ucfirst($type_key)) . '</h2><i>In order to make it possible to select pools while exporting Paragraphs (Export Pool Configuration = Allow), Paragraphs version >= <strong>8.x-1.3</strong> is required.';
      }
      else {
        $markup = '<h2>' . str_replace('_', ' ', ucfirst($type_key)) . '</h2>';
      }

      $entity_table[$type_key]['title'] = [
        '#markup' => $markup,
        '#wrapper_attributes' => [
          'colspan' => count($entity_table['#header']),
        ],
      ];

      foreach ($entity_type as $entity_bundle_name => $entity_bundle) {
        $entity_bundle_row = [];

        $version = Flow::getEntityTypeVersion($type_key, $entity_bundle_name);

        $available_preview_modes = [];
        foreach ($display_modes_ids as $id) {
          $length = strlen($type_key) + strlen($entity_bundle_name) + 2;
          if (substr($id, 0, $length) != $type_key . '.' . $entity_bundle_name . '.') {
            continue;
          }
          $label = $display_modes[$id]->label();
          $id    = substr($id, $length);
          if (empty($label)) {
            $label = $id;
          }
          $available_preview_modes[$id] = $label;
        }

        if (!isset($def_sync_entities[$type_key . '-' . $entity_bundle_name])) {
          $row_default_values = [
            'id' => $type_key . '-' . $entity_bundle_name,
            'export' => FALSE,
            'export_deletion_settings' => [
              'export_deletion' => FALSE,
            ],
            'import' => NULL,
            'import_deletion_settings' => [
              'import_deletion' => FALSE,
              'allow_local_deletion_of_import' => FALSE,
            ],
            'handler_settings' => [],
            'import_updates' => ImportIntent::IMPORT_UPDATE_FORCE,
            'preview' => Flow::PREVIEW_DISABLED,
            'display_name' => $this->t('@bundle', [
              '@bundle' => $entity_bundle['label'],
            ])->render(),
            'entity_type' => $type_key,
            'entity_bundle' => $entity_bundle_name,
            'pool_export_widget_type' => 'checkboxes',
          ];
          foreach ($pool_entities as $pool) {
            $row_default_values['export_pools'][$pool->id()] = Pool::POOL_USAGE_FORCE;
            $row_default_values['import_pools'][$pool->id()] = Pool::POOL_USAGE_FORCE;
          }
        }
        else {
          $row_default_values = $def_sync_entities[$type_key . '-' . $entity_bundle_name];
        }
        if (!empty($input[$type_key . '-' . $entity_bundle_name])) {
          $row_default_values = array_merge($row_default_values, $input[$type_key . '-' . $entity_bundle_name]);
        }

        $entity_bundle_row['bundle'] = [
          '#markup' => $this->t('@bundle (@machine_name)', [
            '@bundle' => $entity_bundle['label'],
            '@machine_name' => $entity_bundle_name,
          ]) . '<br><small>version: ' . $version . '</small>' .
          (empty($row_default_values['version'])||$version == $row_default_values['version'] ? '' : '<br><strong>Changed from ' . $row_default_values['version'] . '</strong>'),
        ];

        $entity_handlers = $this->entityPluginManager->getHandlerOptions($type_key, $entity_bundle_name, TRUE);
        if (empty($entity_handlers)) {
          $handler_id = 'ignore';
          $entity_handlers = ['ignore' => $this->t('Not supported')->render()];
        }
        else {
          $entity_handlers = array_merge(['ignore' => $this->t('Ignore')->render()], $entity_handlers);
          $handler_id = empty($row_default_values['handler']) ? 'ignore' : $row_default_values['handler'];
        }

        $entity_bundle_row['handler'] = [
          '#type' => 'select',
          '#title' => $this->t('Handler'),
          '#title_display' => 'invisible',
          '#options' => $entity_handlers,
          '#disabled' => count($entity_handlers) < 2 && isset($entity_handlers['ignore']),
          '#default_value' => $handler_id,
          '#ajax' => [
            'callback' => '::updateSyncHandler',
            'wrapper' => 'sync-entities-table',
            'progress' => [
              'type' => 'throbber',
              'message' => "loading...",
            ],
          ],
        ];

        $handler = NULL;
        if ($handler_id == 'ignore') {
          $export_options = [
            ExportIntent::EXPORT_DISABLED => $this->t('Disabled')->render(),
          ];
        }
        else {
          $handler = $this->entityPluginManager->createInstance($handler_id, [
            'entity_type_name' => $type_key,
            'bundle_name' => $entity_bundle_name,
            'settings' => $row_default_values,
            'sync' => NULL,
          ]);

          $allowed_export_options = $handler->getAllowedExportOptions();
          $export_options = [];
          foreach ($allowed_export_options as $option) {
            $export_options[$option] = $export_option_labels[$option];
          }
        }

        $entity_bundle_row['handler_settings'] = [
          '#markup' => '-',
        ];
        if ($handler_id != 'ignore') {
          $advanced_settings = $handler->getHandlerSettings();
          if (count($advanced_settings)) {
            $entity_bundle_row['handler_settings'] = array_merge([
              '#type' => 'container',
            ], $advanced_settings);
          }
        }

        $entity_bundle_row['export'] = [
          '#type' => 'select',
          '#title' => $this->t('Export'),
          '#title_display' => 'invisible',
          '#options' => $export_options,
          '#default_value' => $row_default_values['export'],
        ];

        if ($handler) {
          $allowed_import_options = $handler->getAllowedImportOptions();
          $import_options = [];
          foreach ($allowed_import_options as $option) {
            $import_options[$option] = $import_option_labels[$option];
          }
        }
        else {
          $import_options = [
            ImportIntent::IMPORT_DISABLED => $this->t('Disabled')->render(),
          ];
        }

        foreach ($pool_entities as $pool) {
          $entity_bundle_row['export_pools'][$pool->id()] = [
            '#type' => 'select',
            '#title' => $this->t($pool->label()),
            '#options' => [
              Pool::POOL_USAGE_FORCE => $this->t('Force'),
              Pool::POOL_USAGE_ALLOW => $this->t('Allow'),
              Pool::POOL_USAGE_FORBID => $this->t('Forbid'),
            ],
            '#default_value' => isset($row_default_values['export_pools'][$pool->id()]) ? $row_default_values['export_pools'][$pool->id()] : Pool::POOL_USAGE_FORBID,
          ];
        }

        $entity_bundle_row['pool_export_widget_type'] = [
          '#type' => 'select',
          '#options' => [
            'checkboxes' => $this->t('Checkboxes'),
            'radios' => $this->t('Radio boxes'),
            'single_select' => $this->t('Single select'),
            'multi_select' => $this->t('Multi select'),
          ],
          '#default_value' => $row_default_values['pool_export_widget_type'],
        ];

        $entity_bundle_row['export_deletion_settings']['export_deletion'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Export deletion'),
          '#default_value' => $row_default_values['export_deletion_settings']['export_deletion'] == 1,
        ];

        $entity_bundle_row['import'] = [
          '#type' => 'select',
          '#title' => $this->t('Synchronized Import'),
          '#title_display' => 'invisible',
          '#options' => $import_options,
          '#default_value' => $row_default_values['import'],
        ];

        foreach ($pool_entities as $pool) {
          $entity_bundle_row['import_pools'][$pool->id()] = [
            '#type' => 'select',
            '#title' => $this->t($pool->label()),
            '#options' => [
              Pool::POOL_USAGE_FORCE => $this->t('Force'),
              Pool::POOL_USAGE_ALLOW => $this->t('Allow'),
              Pool::POOL_USAGE_FORBID => $this->t('Forbid'),
            ],
            '#default_value' => isset($row_default_values['import_pools'][$pool->id()]) ? $row_default_values['import_pools'][$pool->id()] : Pool::POOL_USAGE_FORBID,
          ];
        }

        $entity_bundle_row['import_deletion_settings']['import_deletion'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Import deletion'),
          '#default_value' => $row_default_values['import_deletion_settings']['import_deletion'] == 1,
        ];

        $entity_bundle_row['import_deletion_settings']['allow_local_deletion_of_import'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Allow deletion of imported content'),
          '#default_value' => $row_default_values['import_deletion_settings']['allow_local_deletion_of_import'] == 1,
        ];

        $entity_bundle_row['import_updates'] = [
          '#type' => 'select',
          '#options' => [
            ImportIntent::IMPORT_UPDATE_FORCE => $this->t('Dismiss local changes'),
            ImportIntent::IMPORT_UPDATE_IGNORE => $this->t('Ignore updates completely'),
            ImportIntent::IMPORT_UPDATE_FORCE_AND_FORBID_EDITING => $this->t('Forbid local changes and update'),
            ImportIntent::IMPORT_UPDATE_FORCE_UNLESS_OVERRIDDEN => $this->t('Update unless overwritten locally'),
          ],
          '#default_value' => $row_default_values['import_updates'],
        ];

        $options = array_merge([
          Flow::PREVIEW_DISABLED => $this->t('Disabled')->render(),
        ], $handler_id == 'ignore' ? [] : array_merge([Flow::PREVIEW_TABLE => $this->t('Table')->render()], $available_preview_modes));
        $default = $handler_id == 'ignore' ? Flow::PREVIEW_DISABLED : Flow::PREVIEW_TABLE;
        $entity_bundle_row['preview'] = [
          '#type' => 'select',
          '#title' => $this->t('Preview'),
          '#title_display' => 'invisible',
          '#options' => $options,
          '#default_value' => $row_default_values['preview'] || $handler_id == 'ignore' ? $row_default_values['preview'] : $default,
          '#description' => $this->t('Make sure to go to the general "Settings" and enable preview export to make use of this.'),
        ];

        $entity_table[$type_key . '-' . $entity_bundle_name] = $entity_bundle_row;

        if ($handler_id != 'ignore') {
          $forbidden_fields = array_merge($handler->getForbiddenFields(),
            // These are standard fields defined by the Flow
            // Entity type that entities may not override (otherwise
            // these fields will collide with DCS functionality)
            [
              'source',
              'source_id',
              'source_connection_id',
              'preview',
              'url',
              'apiu_translation',
              'metadata',
              'embed_entities',
              'title',
              'created',
              'changed',
              'uuid',
            ]);

          $entityFieldManager = $this->entityFieldManager;
          /**
           * @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields
           */
          $fields = $entityFieldManager->getFieldDefinitions($type_key, $entity_bundle_name);
          foreach ($fields as $key => $field) {
            $field_id = $type_key . '-' . $entity_bundle_name . '-' . $key;

            $field_row = [];

            $field_row['bundle'] = [
              '#markup' => $key,
            ];

            if (!isset($def_sync_entities[$field_id])) {
              $field_default_values = [
                'id' => $field_id,
                'export' => NULL,
                'import' => NULL,
                'preview' => NULL,
                'entity_type' => $type_key,
                'entity_bundle' => $entity_bundle_name,
              ];
            }
            else {
              $field_default_values = $def_sync_entities[$field_id];
            }
            if (!empty($input[$field_id])) {
              $field_default_values = array_merge($field_default_values, $input[$field_id]);
            }

            if (in_array($key, $forbidden_fields) !== FALSE) {
              $handler_id = 'ignore';
              $field_handlers = [
                'ignore' => $this->t('Not configurable')->render(),
              ];
            }
            else {
              $field_handlers = $this->fieldPluginManager->getHandlerOptions($type_key, $entity_bundle_name, $key, $field, TRUE);
              if (empty($field_handlers)) {
                $handler_id = 'ignore';
              }
              else {
                reset($field_handlers);
                $handler_id = empty($field_default_values['handler']) ? key($field_handlers) : $field_default_values['handler'];
              }
            }

            $field_row['handler'] = [
              '#type' => 'select',
              '#title' => $this->t('Handler'),
              '#title_display' => 'invisible',
              '#options' => count($field_handlers) ? ($field->isRequired() ? $field_handlers : array_merge(['ignore' => $this->t('Ignore')->render()], $field_handlers)) : [
                'ignore' => $this->t('Not supported')->render(),
              ],
              '#disabled' => !count($field_handlers) || (count($field_handlers) == 1 && isset($field_handlers['ignore'])),
              '#default_value' => $handler_id,
              '#ajax' => [
                'callback' => '::updateSyncHandler',
                'wrapper' => 'sync-entities-table',
                'progress' => [
                  'type' => 'throbber',
                  'message' => "loading...",
                ],
              ],
            ];

            if ($handler_id == 'ignore') {
              $export_options = [
                ExportIntent::EXPORT_DISABLED => $this->t('No')->render(),
              ];
            }
            else {
              $handler = $this->fieldPluginManager->createInstance($handler_id, [
                'entity_type_name' => $type_key,
                'bundle_name' => $entity_bundle_name,
                'field_name' => $key,
                'field_definition' => $field,
                'settings' => $field_default_values,
                'sync' => NULL,
              ]);

              $allowed_export_options = $handler->getAllowedExportOptions();
              $export_options = [];
              foreach ($allowed_export_options as $option) {
                $export_options[$option] = $export_option_labels_fields[$option];
              }
            }

            $field_row['handler_settings'] = [
              '#markup' => '-',
            ];

            if ($handler_id != 'ignore') {
              $advanced_settings = $handler->getHandlerSettings($field_default_values);
              if (count($advanced_settings)) {
                $field_row['handler_settings'] = array_merge([
                  '#type' => 'container',
                ], $advanced_settings);
              }
            }

            $field_row['export'] = [
              '#type' => 'select',
              '#title' => $this->t('Export'),
              '#title_display' => 'invisible',
              '#disabled' => count($export_options) < 2,
              '#options' => $export_options,
              '#default_value' => $field_default_values['export'] ? $field_default_values['export'] : (isset($export_options[ExportIntent::EXPORT_AUTOMATICALLY]) ? ExportIntent::EXPORT_AUTOMATICALLY : ExportIntent::EXPORT_DISABLED),
            ];

            $field_row['pool_export_widget_type'] = [
              '#markup' => '',
            ];

            $field_row['export_deletion_settings']['export_deletion'] = [
              '#markup' => '',
            ];

            if ($handler_id == 'ignore') {
              $import_options = [
                ImportIntent::IMPORT_DISABLED => $this->t('No')->render(),
              ];
            }
            else {
              $allowed_import_options = $handler->getAllowedImportOptions();
              $import_options = [];
              foreach ($allowed_import_options as $option) {
                $import_options[$option] = $import_option_labels_fields[$option];
              }
            }
            $field_row['import'] = [
              '#type' => 'select',
              '#title' => $this->t('Import'),
              '#title_display' => 'invisible',
              '#options' => $import_options,
              '#disabled' => count($import_options) < 2,
              '#default_value' => $field_default_values['import'] ? $field_default_values['import'] : (isset($import_options[ImportIntent::IMPORT_AUTOMATICALLY]) ? ImportIntent::IMPORT_AUTOMATICALLY : ImportIntent::IMPORT_DISABLED),
            ];

            $entity_bundle_row['import_deletion_settings']['import_deletion'] = [
              '#markup' => '',
            ];

            $entity_bundle_row['import_deletion_settings']['allow_local_deletion_of_import'] = [
              '#markup' => '',
            ];

            $entity_bundle_row['import_updates'] = [
              '#markup' => '',
            ];

            $field_row['preview'] = [
              '#markup' => '',
            ];

            $entity_table[$field_id] = $field_row;
          }
        }
      }
    }

    $form['sync_entities'] = $entity_table;

    $this->disableOverridenConfigs($form);
    return $form;
  }

  /**
   * Disable form elements which are overridden.
   *
   * @param array $form
   */
  private function disableOverridenConfigs(array &$form) {
    global $config;
    $config_name = 'drupal_content_sync.drupal_content_sync.' . $form['id']['#default_value'];

    // If the default overrides aren't used check if a
    // master / subsite setting is used.
    if (!isset($config[$config_name]) || empty($config[$config_name])) {
      // Is this site a master site? It is a subsite by default.
      $environment = 'subsite';
      if ($this->configFactory->get('config_split.config_split.drupal_content_sync_master')->get('status')) {
        $environment = 'master';
      }
      $config_name = 'drupal_content_sync.sync.' . $environment;
    }
    $fields = Element::children($form);
    foreach ($fields as $field_key) {
      if ($this->configIsOverridden($field_key, $config_name)) {
        $form[$field_key]['#disabled'] = 'disabled';
        $form[$field_key]['#value'] = $this->configFactory->get($config_name)->get($field_key);
        unset($form[$field_key]['#default_value']);
      }
    }
  }

  /**
   * Check if a config is overridden.
   *
   * Right now it only checks if the config is in the $config-array (overridden
   * by the settings.php)
   *
   * @TODO take care of overriding by modules and languages
   *
   * @param string $config_key
   *   The configuration key.
   * @param string $config_name
   *   The configuration name.
   *
   * @return bool
   */
  private function configIsOverridden($config_key, $config_name) {
    global $config;
    return isset($config[$config_name][$config_key]);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $config = $this->entity;

    $sync_entities = &$config->{'sync_entities'};
    foreach ($sync_entities as $key => $bundle_fields) {
      // Ignore field settings.
      if (substr_count($key, '-') != 1) {
        continue;
      }

      preg_match('/^(.+)-(.+)$/', $key, $matches);

      $type_key = $matches[1];
      $bundle_key = $matches[2];

      $sync_entities[$key]['version'] = Flow::getEntityTypeVersion($type_key, $bundle_key);
      $sync_entities[$key]['entity_type_name'] = $type_key;
      $sync_entities[$key]['bundle_name'] = $bundle_key;
    }

    // $is_new = !$this->exists($config->id());
    $status = $config->save();

    if ($status) {
      $this->messenger->addMessage($this->t('Saved the %label Flow.', [
        '%label' => $config->label(),
      ]));
    }
    else {
      $this->messenger->addMessage($this->t('The %label Flow could not be saved.', [
        '%label' => $config->label(),
      ]));
    }

    // Make sure that the export is executed.
    \Drupal::request()->query->remove('destination');

    $form_state->setRedirect('entity.dcs_flow.export', ['dcs_flow' => $config->id()]);
  }

  /**
   * Check if the entity exists.
   *
   * A helper function to check whether an
   * Flow configuration entity exists.
   *
   * @param int $id
   *   An ID of sync.
   *
   * @return bool
   *   Checking on exist an entity.
   */
  public function exists($id) {
    $entity = $this->entityTypeManager
      ->getStorage('dcs_flow')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
