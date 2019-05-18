<?php

namespace Drupal\bibcite_export\Form;

use Drupal\bibcite\Plugin\BibciteFormatManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Export all reference data to any available export format.
 */
class ExportAllForm extends FormBase {

  /**
   * Private temp store instance.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStorage;

  /**
   * Bibcite format manager service.
   *
   * @var \Drupal\bibcite\Plugin\BibciteFormatManagerInterface
   */
  protected $formatManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, BibciteFormatManagerInterface $format_manager) {
    $this->tempStorage = $temp_store_factory->get('bibcite_export');
    $this->formatManager = $format_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('plugin.manager.bibcite_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_export_all';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['format'] = [
      '#title' => $this->t('Export format'),
      '#type' => 'select',
      '#options' => array_map(function ($format) {
        return $format['label'];
      }, $this->formatManager->getExportDefinitions()),
      '#required' => TRUE,
    ];

    if ($files = $this->tempStorage->get('export_files')) {
      $form['files'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Files'),
        '#items' => $this->createFilesList($files),
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    ];

    return $form;
  }

  /**
   * Create files list based on info from temp storage.
   *
   * @param array $files_info
   *   Files info from temp storage.
   *
   * @return array
   *   Render array of link items.
   */
  protected function createFilesList(array $files_info) {
    $items = [];

    foreach ($files_info as $key => $file_info) {
      if ($file = $this->loadFile($file_info['id'])) {
        $format = $this->formatManager->createInstance($file_info['format']);
        $date = date('m-d-Y H:i:s', $file_info['timestamp']);
        $title = sprintf('%s - %s - %s', $file->label(), $format->getLabel(), $date);

        $items[$key] = [
          '#type' => 'link',
          '#title' => $title,
          '#url' => Url::fromRoute('bibcite_export.download', [
            'file' => $file->id(),
          ]),
        ];
      }
      else {
        $this->deleteFileInfoFromStorage($key);
      }
    }

    return $items;
  }

  /**
   * Load file by fid.
   *
   * If file does not exist in the file system then delete him from database.
   *
   * @param int $fid
   *   File ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|bool
   *   File object or FALSE if file does not exist.
   */
  protected function loadFile($fid) {
    if ($file = File::load($fid)) {
      if (file_exists($file->getFileUri())) {
        return $file;
      }
      else {
        $file->delete();
      }
    }

    return FALSE;
  }

  /**
   * Delete info about file from temp storage.
   *
   * @param int $key
   *   Key of the file element.
   */
  protected function deleteFileInfoFromStorage($key) {
    $files_info = $this->tempStorage->get('export_files');
    unset($files_info[$key]);
    $this->tempStorage->set('export_files', $files_info);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $format = $this->formatManager->hasDefinition($form_state->getValue('format'));
    if (!isset($format)) {
      $form_state->setErrorByName('format', $this->t('Trying export to non-existing format.'));
    }

    $storage = \Drupal::entityTypeManager()->getStorage('bibcite_reference');
    $total = $storage->getQuery()->count()->execute();
    if (!$total) {
      $form_state->setError($form, $this->t('There is no data to export.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $format = $this->formatManager->createInstance($form_state->getValue('format'));

    $batch = [
      'title' => t('Export all reference data'),
      'operations' => [
        [
          'bibcite_export_batch_all', ['bibcite_reference', $format],
        ],
      ],
      'file' => drupal_get_path('module', 'bibcite_export') . '/bibcite_export.batch.inc',
      'finished' => 'bibcite_export_batch_finished',
    ];
    batch_set($batch);
  }

}
