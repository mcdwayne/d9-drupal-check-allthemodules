<?php

namespace Drupal\dcat_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\dcat_import\Entity\DcatSource;

/**
 * Configure dcat import settings for this site.
 */
class DcatImportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dcat_import_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dcat_import.settings', 'migrate_plus.migration.' . DcatSource::migrateGlobalThemeId()];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dcat_import.settings');

    $form['global_theme_iri'] = array(
      '#type' => 'url',
      '#title' => $this->t('Global Theme IRI'),
      '#maxlength' => 255,
      '#default_value' => $config->get('global_theme.iri'),
      '#description' => $this->t("The IRI for an alternative theme DCAT file e.g. https://gist.githubusercontent.com/wesleydv/a8bc1cde8c89e1c2b04acdb01843d279/raw/data-theme.ttl"),
    );

    $form['global_theme_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#default_value' => $config->get('global_theme.format'),
      '#description' => $this->t("Format of the global theme source."),
      '#options' => [
        'guess' => $this->t('Guess'),
        'php' => $this->t('RDF/PHP'),
        'json' => $this->t('RDF/JSON Resource-Centric'),
        'jsonld' => $this->t('JSON-LD'),
        'ntriples' => $this->t('N-Triples'),
        'turtle' => $this->t('Turtle Terse RDF Triple Language'),
        'rdfxml' => $this->t('RDF/XML'),
        'rdfa' => $this->t('RDFa'),
      ],
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $iri = $form_state->getValue('global_theme_iri');
    $format = $form_state->getValue('global_theme_format');

    $config = $this->config('dcat_import.settings');
    $config->set('global_theme.iri', $iri);
    $config->set('global_theme.format', $format);
    $config->save();

    if (!empty($iri)) {
      $this->createGlobalThemeMigration($iri, $format);
    }
    else {
      $this->config('migrate_plus.migration.' . DcatSource::migrateGlobalThemeId())->delete();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Create the global theme migration config.
   *
   * @param string $iri
   *   The IRI to use in the global theme migration config.
   */
  public function createGlobalThemeMigration($iri, $format) {
    $id = DcatSource::migrateGlobalThemeId();
    $migration = Migration::load($id);
    if (empty($migration)) {
      $migration = Migration::create(array('id' => $id));
    }

    $migration->set('migration_tags', array('dcat'));
    $migration->set('migration_dependencies', array(
      'required' => array(),
      'optional' => array(),
    ));

    $migration->set('label', t('Global theme'));
    $migration->set('source', array(
      'uri' => $iri,
      'format' => $format,
      'plugin' => 'dcat.global_theme',
    ));

    $migration->set('process', array(
      'external_id' => 'uri',
      'name' => 'name',
      'description' => 'description',
      'mapping' => 'mapping',
      'vid' => array(
        'plugin' => 'default_value',
        'default_value' => 'dataset_theme',
      ),
    ));

    $migration->set('destination', array(
      'plugin' => 'entity:taxonomy_term',
    ));

    $migration->save();
  }

}
