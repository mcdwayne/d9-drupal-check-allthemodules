<?php

namespace Drupal\dcat_import\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dcat_import\DcatImportUiLogMigrateMessage;
use Drupal\dcat_import\Entity\DcatSourceInterface;
use Drupal\migrate_tools\MigrateExecutable;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Configure dcat import settings for this site.
 */
class DcatImportForm extends ConfirmFormBase {

  /**
   * The DCAT source entity.
   *
   * @var \Drupal\dcat_import\Entity\DcatSourceInterface
   */
  protected $dcatSource;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dcat_import';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.dcat_source.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to import the @name DCAT feed?', [
      '@name' => $this->dcatSource->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Import');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, DcatSourceInterface $dcat_source = NULL) {
    $this->dcatSource = $dcat_source;
    $form_state->setStorage(['dcat_source' => $dcat_source]);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $dcat_source \Drupal\dcat_import\Entity\DcatSourceInterface */
    $dcat_source = $form_state->getStorage()['dcat_source'];
    $id = 'dcat_import_' . $dcat_source->id() . '_dataset';

    $manager = \Drupal::service('plugin.manager.migration');
    $plugins = $manager->createInstances([$id]);
    /* @var $migration \Drupal\migrate\Plugin\MigrationInterface */
    $migration = $plugins[$id];
    $ids = $migration->get('requirements');
    $ids[] = $id;

    $batch = [
      'title' => t('Importing'),
      'operations' => [],
      'finished' => 'Drupal\dcat_import\Form\DcatImportForm::batchDone',
    ];

    $start_date = time();
    foreach ($ids as $id) {
      $batch['operations'][] = [
        'Drupal\dcat_import\Form\DcatImportForm::batchImport',
        [$id, $dcat_source->id(), $start_date],
      ];
    }

    batch_set($batch);
  }

  /**
   * Batch callback, run import on migration.
   *
   * @param string $id
   *   The migration id.
   * @param string $source_id
   *   Dcat source id.
   * @param int $start_date
   *   The start date as timestamp.
   * @param array $context
   *   Batch context.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public static function batchImport($id, $source_id, $start_date, array &$context) {
    $manager = \Drupal::service('plugin.manager.migration');
    $plugins = $manager->createInstances([$id]);
    /* @var $migration \Drupal\migrate\Plugin\MigrationInterface */
    $migration = $plugins[$id];

    $log = new DcatImportUiLogMigrateMessage(\Drupal::database(), $start_date, $source_id, $id);
    $executable = new MigrateExecutable($migration, $log);
    $executable->import();
  }

  /**
   * Batch callback finish, redirect when done.
   */
  public static function batchDone($success, $results, $operations) {
    return new RedirectResponse(Url::fromRoute('entity.dcat_source.collection')->toString());
  }

}
