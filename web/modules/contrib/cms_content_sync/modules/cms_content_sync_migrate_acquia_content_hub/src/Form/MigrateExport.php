<?php

namespace Drupal\cms_content_sync_migrate_acquia_content_hub\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\acquia_contenthub\EntityManager;
use Drupal\cms_content_sync\Entity\Flow;
use Drupal\cms_content_sync\Entity\Pool;
use Drupal\cms_content_sync\ExportIntent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * CMS Content Sync advanced debug form.
 */
class MigrateExport extends MigrationBase {

  /**
   *
   */
  public function __construct(EntityManager $acquia_entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, FieldTypePluginManagerInterface $field_type_plugin_manager, ConfigFactoryInterface $config_factory, ModuleHandler $moduleHandler, EntityTypeManager $entity_type_manager) {
    parent::__construct($acquia_entity_manager, $entity_type_bundle_info, $field_type_plugin_manager, $config_factory, $moduleHandler, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cms_content_sync_migrate_acquia_content_hub.migrate_export';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->migrationType = 'export';
    $form = parent::buildForm($form, $form_state);

    $url = Url::fromUri('https://edge-box.atlassian.net/wiki/spaces/SUP/pages/137232742/Export+and+Import+settings');
    $link = Link::fromTextAndUrl(t('here'), $url);
    $link = $link->toRenderable();
    $link['#attributes'] = ['class' => ['external']];
    $link = render($link);

    $form['node_export_behavior'] = [
      '#title' => $this->t('Node export behavior'),
      '#description' => $this->t('This configuration allows to define if Nodes should be exported automatically ("All") or manually ("Manually"). Further information about export behaviors could be found @link.', [
        '@link' => $link,
      ]),
      '#type' => 'select',
      '#options' => [
        ExportIntent::EXPORT_AUTOMATICALLY => $this->t('Automatically'),
        ExportIntent::EXPORT_MANUALLY => $this->t('Manually'),
      ],
      '#default_value' => ExportIntent::EXPORT_AUTOMATICALLY,
    ];

    // @Todo: Add descriptions texts.
    $taxonomy_bundles = $this->entityTypeBundleInfo->getBundleInfo('taxonomy_term');
    $pool_bases = [];
    foreach ($taxonomy_bundles as $bundle_key => $taxonomy_bundle) {
      $pool_bases[$bundle_key] = $taxonomy_bundle['label'];
    }

    $form['pool_base_description'] = [
      '#type' => 'item',
      '#title' => $this->t('Pool setup'),
      '#markup' => $this->t('You have to select at least one term.'),
    ];

    foreach ($pool_bases as $key => $pool_base) {
      $form['pool_base'][$key] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $pool_base,
        '#attributes' => [
          'class' => [
            'tag-pool-selection',
          ],
        ],
      ];

      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($key);
      $term_data = [];
      foreach ($terms as $term) {
        $term_data[$term->tid] = $term->name;
      }

      $form['pool_base'][$key]['pools'] = [
        '#type' => 'checkboxes',
        '#options' => $term_data,
        '#validated' => TRUE,
      ];
    }

    $form['#attached']['library'][] = 'cms_content_sync_migrate_acquia_content_hub/migrate-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $pool_selected = FALSE;
    $pools = $form_state->getValue('pools');
    foreach ($pools as $pool) {
      if ($pool) {
        $pool_selected = TRUE;
      }
    }
    /*if (!$pool_selected) {
    $form_state->setErrorByName('pool_base_description', $this->t('You have to select at least one term.'));
    }*/
  }

  /**
   * Create the pools based on the user selected terms.
   *
   * @ToDo: Better variable names?
   *
   * @param $pools
   * @param $backend_url
   * @param $authentication_type
   * @param $site_id
   *
   * @return array
   */
  public static function createPools($pools, $backend_url, $authentication_type, $site_id) {
    // Create Pools.
    $content_sync_pools = [];

    foreach ($pools as $pool) {
      if ($pool) {
        $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($pool);
        $term_machine_name = strtolower($term->label());
        $term_machine_name = preg_replace('@[^a-z0-9_]+@', '_', $term_machine_name);
        $pool_id = Pool::createPool($term->label(), $term_machine_name, $backend_url, $authentication_type, $site_id);

        $content_sync_pools[$pool_id] = [
          'term_id' => $term->id(),
          'term_machine_name' => $term_machine_name,
          'term_bundle' => $term->bundle(),
        ];
      }
    }

    // If the user does not select a taxonomy term, we create a default content pool.
    // @ToDo: Do we want this?
    /**
     * if (empty($content_sync_pools)) {
     * Pool::createPool('Content', 'content', $form_state->getValue('backend_url'), $form_state->getValue('authentication_type'), $form_state->getValue('site_id'));
     * $content_sync_pools['content'] = 'Content';
     * }
    **/

    drupal_set_message('CMS Content Sync Pools have been created.');

    return $content_sync_pools;
  }

  /**
   * @param $pools
   *
   * @param string $node_export_behavior
   * @param string $import_updates_behavior
   *
   * @param bool $force_update
   *
   * @return array|string
   */
  public static function createFlow($pools, $node_export_behavior, $import_updates_behavior, $force_update = FALSE) {
    // Get Acquia Content Hub configurations.
    $content_hub_configrations = MigrateExport::getAcquiaContentHubConfigrations();

    // Create a new flow based on the given Acquia Content Hub configurations.
    foreach ($content_hub_configrations as $entity_type_key => $content_hub_configration) {

      // If no bundles are configured, the entity type can be skipped.
      if (!in_array(TRUE, $content_hub_configration)) {
        continue;
      }

      foreach ($content_hub_configration as $bundle_key => $bundle) {
        if ($bundle) {

          // @Todo: More Handler options?
          // General configurations.
          $configurations[$entity_type_key][$bundle_key]['export_configuration'] = [
            'export_deletion_settings' => TRUE,
          ];

          $configurations[$entity_type_key][$bundle_key]['export_configuration']['export_pools'] = [];

          $usage = $entity_type_key == 'node' ? Pool::POOL_USAGE_ALLOW : Pool::POOL_USAGE_FORCE;
          foreach (Pool::getAll() as $pool_id => $pool) {
            $configurations[$entity_type_key][$bundle_key]['export_configuration']['export_pools'][$pool_id] = empty($pools[$pool_id]) ? Pool::POOL_USAGE_FORBID : $usage;
          }

          // Export everything beside nodes as dependencies.
          if ($entity_type_key == 'node') {
            $configurations[$entity_type_key][$bundle_key]['export_configuration']['behavior'] = $node_export_behavior;
          }
          else {
            $configurations[$entity_type_key][$bundle_key]['export_configuration']['behavior'] = ExportIntent::EXPORT_AS_DEPENDENCY;
          }
        }
      }
    }

    if (!empty($configurations)) {
      drupal_set_message('The export flow has been created, please review your settings.');
      return [
        'flow_id' => Flow::createFlow('Export', 'export_migrated', TRUE, $pools, $configurations, $force_update),
        'flow_configuration' => $configurations,
        'type' => 'export',
      ];
    }
    else {
      drupal_set_message('CMS Content Sync Export Flow has not been created.', 'warning');
      return '';
    }
  }

  /**
   * Get Entity Type configurations of the Acquia Content Hub.
   *
   * @return array
   */
  public static function getAcquiaContentHubConfigrations() {
    $entity_types = \Drupal::service('acquia_contenthub.entity_manager')->getAllowedEntityTypes();
    $content_hub_configurations = [];
    foreach ($entity_types as $entity_type_key => $entity_type) {
      $contenthub_entity_config_id = \Drupal::service('acquia_contenthub.entity_manager')->getContentHubEntityTypeConfigurationEntity($entity_type_key);
      foreach ($entity_type as $bundle_key => $bundle) {
        $content_hub_configurations[$entity_type_key][$bundle_key] = $contenthub_entity_config_id->isEnableIndex($bundle_key);
      }

    }
    return $content_hub_configurations;
  }

}
