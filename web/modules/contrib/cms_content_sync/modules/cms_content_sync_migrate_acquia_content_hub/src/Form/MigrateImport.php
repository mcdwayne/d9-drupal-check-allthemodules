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
use Drupal\cms_content_sync\ImportIntent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Migrate a Acquia Content Hub Filter to CMS Content Sync.
 */
class MigrateImport extends MigrationBase {

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
    return 'cms_content_sync_migrate_acquia_content_hub.migrate_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $content_hub_filter_id = NULL) {
    // @ToDo: Is it possible to load the argument within the constructer?
    $this->content_hub_filter_id = $content_hub_filter_id;
    $this->migrationType = 'import';

    $content_hub_filter = $this->entityTypeManager
      ->getStorage('contenthub_filter')
      ->load($this->content_hub_filter_id);

    $this->content_hub_filter = $content_hub_filter;

    $publish_setting = $this->content_hub_filter->publish_setting;
    $from_date = $this->content_hub_filter->from_date;
    $to_date = $this->content_hub_filter->to_date;
    $sources = $this->content_hub_filter->source;

    if ($publish_setting != 'none') {
      drupal_set_message($this->t('Be aware that CMS Content Sync does not support the Acquia Content Hub settings for: "Publish Setting" (Sets the Publish setting for this filter.), which is currently configured as "@value"', ['@value' => $publish_setting]), 'warning');
    }
    if ($from_date != '' || $to_date != '') {
      drupal_set_message($this->t('Be aware that CMS Content Sync does not support the Acquia Content Hub settings for: "Date From" (Date starting from) | "Date To" (Date until), which are currently configured as Date From: "@date_from_value" and Date To: "@date_to_value"', [
        '@date_from_value' => $from_date,
        '@date_to_value' => $to_date,
      ]), 'warning');
    }
    if ($sources != '') {
      drupal_set_message($this->t('Be aware that CMS Content Sync does not support the Acquia Content Hub settings for: "Sources" (Source origin site UUIDs, delimited by comma ",".), which is currently configured as @value', ['@value' => $sources]), 'warning');
    }

    $form = parent::buildForm($form, $form_state);

    $url = Url::fromUri('https://edge-box.atlassian.net/wiki/spaces/SUP/pages/137232737/Update+behaviors');
    $link = Link::fromTextAndUrl(t('here'), $url);
    $link = $link->toRenderable();
    $link['#attributes'] = ['class' => ['external']];
    $link = render($link);

    $form['import_updates_behavior'] = [
      '#title' => $this->t('Import updates behavior'),
      '#description' => $this->t('This configuration allows to define the import updates behaviors. Further information could be found @link.', [
        '@link' => $link,
      ]),
      '#type' => 'select',
      '#options' => [
        ImportIntent::IMPORT_UPDATE_FORCE_AND_FORBID_EDITING => $this->t('Forbid local changes and update'),
        ImportIntent::IMPORT_UPDATE_FORCE_UNLESS_OVERRIDDEN  => $this->t('Update unless overridden locally'),
        ImportIntent::IMPORT_UPDATE_FORCE => $this->t('Dismiss local changes'),
        ImportIntent::IMPORT_UPDATE_IGNORE  => $this->t('Ignore updates completely'),
      ],
      '#default_value' => ImportIntent::IMPORT_UPDATE_FORCE_AND_FORBID_EDITING,
    ];

    return $form;
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // @ToDo: If no terms, entity types or bundles are set, we have to ask the sync core if we can reuse an existing configuration.

    // Requirements & Warnings.
    /*$terms_uuid = explode(',', $this->content_hub_filter->tags);
    $entity_types = $this->content_hub_filter->getEntityTypes();
    $bundles = $this->content_hub_filter->getBundles();
    $publish_setting = $this->content_hub_filter->publish_setting;
    $from_date = $this->content_hub_filter->from_date;
    $to_date = $this->content_hub_filter->to_date;
    $sources = $this->content_hub_filter->source;

    if (empty($terms_uuid) || $terms_uuid[0] == "") {
    $form_state->setErrorByName('', $this->t('This filter does not have any associated terms.'));
    }
    if (empty($entity_types) || $entity_types[0] == "") {
    $form_state->setErrorByName('', $this->t('This filter does not have any associated entity types.'));
    }
    if (empty($bundles) || $bundles[0] == "") {
    $form_state->setErrorByName('', $this->t('This filter does not have any associated bundles.'));
    }*/
  }

  /**
   * Create the pools based on the taxonomy terms connected to
   * the content hub filter.
   *
   * @ToDo: If no terms are set for the import filter but a source exists, we've to ask the sync core which pools are required to be created.
   *
   * @param $pools
   * @param $backend_url
   * @param $authentication_type
   * @param $site_id
   *
   * @param string $content_hub_filter
   *
   * @return array
   */
  public static function createPools($pools, $backend_url, $authentication_type, $site_id, $content_hub_filter = '') {
    $content_sync_pools = [];
    $terms_uuid = explode(',', $content_hub_filter->tags);
    $entity_type_manager = \Drupal::entityTypeManager();

    foreach ($terms_uuid as $term_uuid) {
      $term = $entity_type_manager
        ->getStorage('taxonomy_term')
        ->loadByProperties(['uuid' => trim($term_uuid)]);
      $term = reset($term);
      $term_machine_name = strtolower($term->label());
      $term_machine_name = preg_replace('@[^a-z0-9_]+@', '_', $term_machine_name);

      $pool_id = Pool::createPool($term->label(), $term_machine_name, $backend_url, $authentication_type, $site_id);

      $content_sync_pools[$pool_id] = [
        'term_id' => $term->id(),
        'term_machine_name' => $term_machine_name,
        'term_bundle' => $term->bundle(),
      ];
    }

    // @ToDo: For the export we may have the option to create a default content pool if no term has been selected.
    // Do we need something similar for the import?
    return $content_sync_pools;
  }

  /**
   * Create the CMS Content Hub import flow for the content hub filter.
   *
   * @ToDo: If no entity types and bundles are set, we've to ask the sync core which entity types and bundles are required to be imported.
   *
   * @param $pools
   *
   * @param string $node_export_behavior
   * @param string $import_updates_behavior
   * @param string $content_hub_filter
   *
   * @param bool $force_update
   *
   * @return mixed|string
   */
  public static function createFlow($pools, $node_export_behavior, $import_updates_behavior, $content_hub_filter = '', $force_update = FALSE) {
    $configurations = [];

    // Since Acquia does not save the relation between entity types and bundles
    // we need to take care of the mapping.
    $content_hub_entity_types = $content_hub_filter->getEntityTypes();
    $content_hub_bundles = $content_hub_filter->getBundles();

    foreach ($content_hub_entity_types as $content_hub_entity_type) {
      $entity_type_bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($content_hub_entity_type);

      foreach ($content_hub_bundles as $content_hub_bundle_key => $content_hub_bundle) {

        if (array_key_exists($content_hub_bundle, $entity_type_bundles)) {

          // General configurations.
          $configurations[$content_hub_entity_type][$content_hub_bundle]['import_configuration'] = [
            'import_deletion' => TRUE,
            'allow_local_deletion_of_import' => TRUE,
            'import_updates' => $import_updates_behavior,
          ];

          // Pool configuration.
          foreach (Pool::getAll() as $pool_id => $pool) {
            $configurations[$content_hub_entity_type][$content_hub_bundle]['import_configuration']['import_pools'][$pool_id] = empty($pools[$pool_id]) ? Pool::POOL_USAGE_FORBID : Pool::POOL_USAGE_FORCE;
          }

          // Import everything beside nodes as dependencies.
          if ($content_hub_entity_type == 'node') {
            $configurations[$content_hub_entity_type][$content_hub_bundle]['import_configuration']['behavior'] = ImportIntent::IMPORT_AUTOMATICALLY;
          }
          else {
            $configurations[$content_hub_entity_type][$content_hub_bundle]['import_configuration']['behavior'] = ImportIntent::IMPORT_AS_DEPENDENCY;
          }
        }
      }
    }

    drupal_set_message('The import flow has been created, please review your settings.');
    return [
      'flow_id' => Flow::createFlow($content_hub_filter->label(), $content_hub_filter->id() . '_migrated', TRUE, [], $configurations, $force_update),
      'flow_configuration' => $configurations,
      'type' => 'import',
    ];

    /*if (empty($configurations)) {
    drupal_set_message($this->t('CMS Content Sync Export Flow has not been created.'), 'warning');
    return '';
    }*/
  }

}
