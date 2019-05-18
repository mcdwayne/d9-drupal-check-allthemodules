<?php

namespace Drupal\prefetcher\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ImportPrefetcherUrisForm.
 *
 * @package Drupal\prefetcher\Form
 */
class ImportSitemapForm extends FormBase {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prefetcher_import_sitemap_form';
  }

  public static function create(ContainerInterface $container) {
    /** @var \Drupal\prefetcher\Form\ImportSitemapForm $object */
    $object = parent::create($container);
    $object->setModuleHandler($container->get('module_handler'));
    $object->setDatabase($container->get('database'));
    return $object;
  }

  /**
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $handler
   */
  public function setModuleHandler(ModuleHandlerInterface $handler) {
    $this->moduleHandler = $handler;
  }

  /**
   * @param \Drupal\Core\Database\Connection $database
   */
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->moduleHandler->moduleExists('simple_sitemap')) {
      $form['description'] = [
        '#markup' => $this->t("You're about to import any entry from the generated sitemap. This might take some time."),
      ];
      $form['actions']['import'] = [
        '#type' => 'submit',
        '#value' => $this->t('Start import'),
      ];
    }
    else {
      $form['description'] = [
        '#markup' => $this->t('No sitemap module has been found.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = ['operations' => [], 'finished' => ['\Drupal\prefetcher\PrefetcherImporterService', 'finish']];
    $chunk_ids = $this->database->query('SELECT id FROM {simple_sitemap}')->fetchCol();
    if (!empty($chunk_ids)) {
      foreach ($chunk_ids as $id) {
        $batch['operations'][] = [['\Drupal\prefetcher\PrefetcherImporterService', 'importSimpleSitemap'], [$id]];
      }
      batch_set($batch);
    }
    else {
      drupal_set_message($this->t('Cannot start batch operation - the sitemap table appears to be empty.'), 'error');
    }
  }

}
