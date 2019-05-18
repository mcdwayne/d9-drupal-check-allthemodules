<?php

namespace Drupal\dcat_import\Entity;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\migrate_plus\Entity\MigrationGroup;
use Drupal\migrate_plus\Entity\Migration;

/**
 * Defines the DCAT source entity.
 *
 * @ConfigEntityType(
 *   id = "dcat_source",
 *   label = @Translation("DCAT source"),
 *   handlers = {
 *     "list_builder" = "Drupal\dcat_import\DcatSourceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dcat_import\Form\DcatSourceForm",
 *       "edit" = "Drupal\dcat_import\Form\DcatSourceForm",
 *       "delete" = "Drupal\dcat_import\Form\DcatSourceDeleteForm"
 *     },
 *    "access" = "Drupal\dcat_import\DcatSourceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dcat_import\DcatSourceHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "dcat_source",
 *   admin_permission = "administer dcat sources",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/dcat/settings/dcat_source/{dcat_source}",
 *     "add-form" = "/admin/structure/dcat/settings/dcat_source/add",
 *     "edit-form" = "/admin/structure/dcat/settings/dcat_source/{dcat_source}/edit",
 *     "delete-form" = "/admin/structure/dcat/settings/dcat_source/{dcat_source}/delete",
 *     "collection" = "/admin/structure/dcat/settings/dcat_source"
 *   }
 * )
 */
class DcatSource extends ConfigEntityBase implements DcatSourceInterface {

  /**
   * The DCAT source ID.
   *
   * @var string
   */
  public $id;

  /**
   * The DCAT source label.
   *
   * @var string
   */
  public $label;

  /**
   * The DCAT source iri.
   *
   * @var string
   */
  public $iri;

  /**
   * The DCAT source format.
   *
   * @var string
   */
  public $format;

  /**
   * The DCAT source global theme boolean.
   *
   * @var bool
   */
  public $global_theme = FALSE;

  /**
   * The lowercase taxonomy terms boolean.
   *
   * @var bool
   */
  public $lowercase_taxonomy_terms = FALSE;

  /**
   * Import language.
   *
   * @var string
   */
  public $import_langcode;

  /**
   * The DCAT source description.
   *
   * @var string
   */
  public $description;

  /**
   * Return the global theme migrate id.
   *
   * @return string
   *   The global theme migrate id.
   */
  public static function migrateGlobalThemeId() {
    return 'dcat_import_theme_global';
  }

  /**
   * Return the migrate group id.
   *
   * @return string
   *   The migrate group id.
   */
  public function migrateGroupId() {
    return 'dcat_import_' . $this->id();
  }

  /**
   * Return the dataset migrate id.
   *
   * @return string
   *   The dataset migrate id.
   */
  public function datasetMigrateId() {
    return 'dcat_import_' . $this->id() . '_dataset';
  }

  /**
   * Return the distribution migrate id.
   *
   * @return string
   *   The distribution migrate id.
   */
  public function distributionMigrateId() {
    return 'dcat_import_' . $this->id() . '_distribution';
  }

  /**
   * Return the dataset keyword migrate id.
   *
   * @return string
   *   The dataset keyword migrate id.
   */
  public function datasetKeywordMigrateId() {
    return 'dcat_import_' . $this->id() . '_dataset_keyword';
  }

  /**
   * Return the agent migrate id.
   *
   * @return string
   *   The agent migrate id.
   */
  public function agentMigrateId() {
    return 'dcat_import_' . $this->id() . '_agent';
  }

  /**
   * Return the vCard migrate id.
   *
   * @return string
   *   The vCard migrate id.
   */
  public function vCardMigrateId() {
    return 'dcat_import_' . $this->id() . '_vcard';
  }

  /**
   * Return the theme migrate id.
   *
   * @return string
   *   The theme migrate id.
   */
  public function themeMigrateId() {
    return 'dcat_import_' . $this->id() . '_theme';
  }

  /**
   * Create or get a migration config with the given $id.
   *
   * @param string $id
   *   The id of the migration config.
   *
   * @return Migration
   *   The migration config.
   */
  private function getMigrateConfig($id) {
    $migration = Migration::load($id);
    if (empty($migration)) {
      $migration = Migration::create(array('id' => $id));
    }

    $migration->set('migration_group', $this->migrateGroupId());
    $migration->set('migration_tags', array('dcat'));

    $migration->set('migration_dependencies', array(
      'required' => array(),
      'optional' => array(),
    ));

    return $migration;
  }

  /**
   * Save the migration group config.
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   */
  public function saveMigrateGroup() {
    $group_id = $this->migrateGroupId();

    if ($this->isNew()) {
      $group = MigrationGroup::create(array('id' => $group_id));
    }
    else {
      /** @var Config $group */
      $group = \Drupal::service('config.factory')->getEditable('migrate_plus.migration_group.' . $group_id);
    }

    $group->set('label', $this->label());
    $group->set('description', $this->description);
    $group->set('source_type', t('DCAT feed'));
    $group->set('module', 'dcat_import');

    $langcode = !empty($this->import_langcode) ? $this->import_langcode : \Drupal::languageManager()->getDefaultLanguage()->getId();

    $group->set('shared_configuration', array(
      'source' => array(
        'uri' => $this->iri,
        'format' => $this->format,
        'global_theme' => (bool) $this->global_theme,
        'lowercase_taxonomy_terms' => (bool) $this->lowercase_taxonomy_terms,
      ),
      'process' => array(
        'langcode' => array(
          'plugin' => 'default_value',
          'default_value' => $langcode,
        ),
      ),
    ));
    $group->save();
  }

  /**
   * Save the dataset migration config.
   */
  public function saveMigrateDataset() {
    $dataset = $this->getMigrateConfig($this->datasetMigrateId());

    $dataset->set('label', t('Datasets'));
    $dataset->set('source', array(
      'plugin' => 'dcat.dataset',
      'track_changes' => TRUE,
    ));

    $migrate_theme_id = $this->global_theme ? $this->migrateGlobalThemeId() : $this->themeMigrateId();

    $dataset->set('process', array(
      'external_id' => 'uri',
      'name' => 'title',
      'description' => 'description',
      'issued' => 'issued',
      'landing_page' => 'landing_page',
      'spatial_geographical' => 'spatial_geographical',
      'accrual_periodicity' => 'accrual_periodicity',
      'status' => 'status',
      'distribution' => array(
        'plugin' => 'migration',
        'migration' => $this->distributionMigrateId(),
        'source' => 'distribution',
      ),
      'keyword' => array(
        'plugin' => 'migration',
        'migration' => $this->datasetKeywordMigrateId(),
        'source' => 'keyword',
      ),
      'source' => array(
        'plugin' => 'default_value',
        'default_value' => $this->id(),
      ),
      'publisher' => array(
        'plugin' => 'migration',
        'migration' => $this->agentMigrateId(),
        'source' => 'publisher',
      ),
      'contact_point' => array(
        'plugin' => 'migration',
        'migration' => $this->vCardMigrateId(),
        'source' => 'contact_point',
      ),
      'theme' => array(
        'plugin' => 'migration',
        'migration' => $migrate_theme_id,
        'source' => 'theme',
      ),
    ));

    $dataset->set('destination', array(
      'plugin' => 'entity:dcat_dataset',
    ));

    $dataset->set('migration_dependencies', array(
      'required' => array(
        $this->distributionMigrateId(),
        $this->datasetKeywordMigrateId(),
        $this->agentMigrateId(),
        $this->vCardMigrateId(),
        $migrate_theme_id,
      ),
      'optional' => array(),
    ));

    $dataset->save();
  }

  /**
   * Save the distribution migration config.
   */
  public function saveMigrateDistribution() {
    $distribution = $this->getMigrateConfig($this->distributionMigrateId());

    $distribution->set('label', t('Distribution'));
    $distribution->set('source', array(
      'plugin' => 'dcat.distribution',
      'track_changes' => TRUE,
    ));

    $distribution->set('process', array(
      'external_id' => 'uri',
      'name' => 'title',
      'description' => 'description',
      'issued' => 'issued',
      'access_url' => 'access_url',
      'download_url' => 'download_url',
      'byte_size' => 'byte_size',
      'format' => 'format',
      'license' => 'license',
      'media_type' => 'media_type',
      'rights' => 'rights',
      'dcat_status' => 'dcat_status',
      'status' => 'status',
    ));

    $distribution->set('destination', array(
      'plugin' => 'entity:dcat_distribution',
    ));

    $distribution->save();
  }

  /**
   * Save the dataset keyword migration config.
   */
  public function saveMigrateDatasetKeyword() {
    $dataset_keyword = $this->getMigrateConfig($this->datasetKeywordMigrateId());

    $dataset_keyword->set('label', t('Dataset keywords'));
    $dataset_keyword->set('source', array(
      'plugin' => 'dcat.dataset_keyword',
      'track_changes' => TRUE,
    ));

    $dataset_keyword->set('process', array(
      'name' => 'name',
      'vid' => array(
        'plugin' => 'default_value',
        'default_value' => 'dataset_keyword',
      ),
    ));

    $dataset_keyword->set('destination', array(
      'plugin' => 'entity:taxonomy_term',
    ));

    $dataset_keyword->save();
  }

  /**
   * Save the agent migration config.
   */
  public function saveMigrateAgent() {
    $agent = $this->getMigrateConfig($this->agentMigrateId());

    $agent->set('label', t('Agent'));
    $agent->set('source', array(
      'plugin' => 'dcat.agent',
      'track_changes' => TRUE,
    ));

    $agent->set('process', array(
      'external_id' => 'uri',
      'name' => 'name',
      'type' => 'agent_type',
      'status' => [
        'plugin' => 'default_value',
        'default_value' => TRUE,
      ]
    ));

    $agent->set('destination', array(
      'plugin' => 'entity:dcat_agent',
    ));

    $agent->save();
  }

  /**
   * Save the agent migration config.
   */
  public function saveMigrateVcard() {
    $vcard = $this->getMigrateConfig($this->vCardMigrateId());

    $vcard->set('label', t('vCard'));
    $vcard->set('source', array(
      'plugin' => 'dcat.vcard',
      'track_changes' => TRUE,
    ));

    $vcard->set('process', array(
      'external_id' => 'uri',
      'name' => 'name',
      'email' => 'email',
      'telephone' => 'telephone',
      'country' => 'country',
      'locality' => 'locality',
      'postal_code' => 'postal_code',
      'region' => 'region',
      'street_address' => 'street_address',
      'nickname' => 'nickname',
      'type' => 'type',
      'status' => 'status',
    ));

    $vcard->set('destination', array(
      'plugin' => 'entity:dcat_vcard',
    ));

    $vcard->save();
  }

  /**
   * Save the theme migration config.
   */
  public function saveMigrateTheme() {
    $theme = $this->getMigrateConfig($this->themeMigrateId());

    $theme->set('label', t('Theme'));
    $theme->set('source', array(
      'plugin' => 'dcat.theme',
      'track_changes' => TRUE,
    ));

    $theme->set('process', array(
      'external_id' => 'uri',
      'name' => 'name',
      'vid' => array(
        'plugin' => 'default_value',
        'default_value' => 'dataset_theme',
      ),
    ));

    $theme->set('destination', array(
      'plugin' => 'entity:taxonomy_term',
    ));

    $theme->save();
  }

  /**
   * Create/update the different migrate configurations.
   */
  public function saveMigrations() {
    // Save the different migrate configs.
    $this->saveMigrateGroup();
    $this->saveMigrateDataset();
    $this->saveMigrateDistribution();
    $this->saveMigrateDatasetKeyword();
    $this->saveMigrateAgent();
    $this->saveMigrateVcard();

    if (!$this->global_theme) {
      $this->saveMigrateTheme();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Delete the different migrate configurations.
   */
  public function delete() {
    parent::delete();

    $config_factory = \Drupal::service('config.factory');
    $config_factory->getEditable('migrate_plus.migration_group.' . $this->migrateGroupId())->delete();
    $config_factory->getEditable('migrate_plus.migration.' . $this->datasetMigrateId())->delete();
    $config_factory->getEditable('migrate_plus.migration.' . $this->distributionMigrateId())->delete();
    $config_factory->getEditable('migrate_plus.migration.' . $this->datasetKeywordMigrateId())->delete();
    $config_factory->getEditable('migrate_plus.migration.' . $this->agentMigrateId())->delete();
    $config_factory->getEditable('migrate_plus.migration.' . $this->vCardMigrateId())->delete();
    if (!$this->global_theme) {
      $config_factory->getEditable('migrate_plus.migration.' . $this->themeMigrateId())->delete();
    }
  }

}
