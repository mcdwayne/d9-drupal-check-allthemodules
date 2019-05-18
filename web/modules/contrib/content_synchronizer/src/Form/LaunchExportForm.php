<?php

namespace Drupal\content_synchronizer\Form;

use Drupal\content_synchronizer\Processors\BatchExportProcessor;
use Drupal\content_synchronizer\Processors\ExportEntityWriter;
use Drupal\content_synchronizer\Service\ArchiveDownloader;
use Drupal\content_synchronizer\Service\EntityExportFormBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Launch Export Form.
 */
class LaunchExportForm extends FormBase {

  /**
   * The export entity.
   *
   * @var \Drupal\content_synchronizer\Entity\ExportEntity
   */
  protected $export;

  protected $currentUrl;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_synchronizer.export.launch';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\content_synchronizer\Entity\ImportEntity $import */
    $this->export = $form_state->getBuildInfo()['export'];

    // Entity list :
    $this->initRootEntitiesList($form);

    if (array_key_exists('entities_list', $form)) {
      $form['launch'] = [
        '#type'        => 'submit',
        '#value'       => t('Launch export'),
        '#button_type' => 'primary'
      ];

      $form['deleteEntities'] = [
        '#type'   => 'submit',
        '#value'  => t('Remove selected entities from export list'),
        '#submit' => ['::removeSelectedEntities'],
      ];
    }

    return $form;
  }

  /**
   * Remove selected entities of the import.
   */
  public function removeSelectedEntities(array &$form, FormStateInterface $form_state) {
    $entitiesToRemove = $form_state->getUserInput()['entities_to_export'];
    $entitiesToRemove = array_intersect_key($this->export->getEntitiesList(), array_flip($entitiesToRemove));

    foreach ($entitiesToRemove as $entity) {
      $this->export->removeEntity($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entitiesToExport = $form_state->getUserInput()['entities_to_export'];
    $entitiesToExport = array_intersect_key($this->export->getEntitiesList(), array_flip($entitiesToExport));

    if (!empty($entitiesToExport)) {
      $this->currentUrl = \Drupal::request()->getRequestUri();

      $writer = new ExportEntityWriter();
      $writer->initFromId($this->export->label());

      $batchExportProcessor = new BatchExportProcessor($writer);
      $batchExportProcessor->exportEntities($entitiesToExport, [
        $this,
        'onBatchEnd'
      ]);
    }
  }

  /**
   * Callback after batch.
   */
  public function onBatchEnd($archiveUri) {
    $redirectUrl = $this->currentUrl;
    \Drupal::service(ArchiveDownloader::SERVICE_NAME)
      ->redirectWithArchivePath($redirectUrl, $archiveUri);
  }

  /**
   * Init Root entities lists for display.
   */
  protected function initRootEntitiesList(array &$form) {
    $rootEntities = $this->getRootsEntities();

    if (!empty($rootEntities)) {
      $build = [
        '#theme'            => 'entities_list_table',
        '#entities'         => $rootEntities,
        '#status_or_bundle' => 'bundle',
        '#checkbox_name'    => 'entities_to_export[]',
        '#title'            => t('Entities to export'),
        '#attached'         => [
          'library' => ['content_synchronizer/list']
        ]
      ];

      $form['entities_list'] = $build;
    }

  }

  /**
   * Return the roots entities.
   */
  public function getRootsEntities() {
    $data = [];

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($this->export->getEntitiesList() as $key => $entity) {
      $data[$key] = [
        "entity_type_id" => $entity->getEntityTypeId(),
        "entity_id"      => $entity->id(),
        "status"         => $entity->bundle(),
        "label"          => ExportEntityWriter::getEntityLabel($entity),
        'edit_url'       => Url::fromRoute('entity.' . $entity->getEntityTypeId() . '.edit_form', [$entity->getEntityTypeId() => $entity->id()]),
        'view_url'       => $entity->toUrl(),
      ];
    }

    return $data;
  }

  /**
   * Add a donwload hidden iframe.
   *
   * @param array $form
   *   The build form array.
   */
  protected function addDownloadIframe(array &$form) {
    if ($archiveUri = \Drupal::request()
      ->get(EntityExportFormBuilder::ARCHIVE_PARAMS)
    ) {
      if (file_exists($archiveUri)) {
        $form['archive'] = [
          '#type'       => 'html_tag',
          '#tag'        => 'iframe',
          '#attributes' => [
            'style' => ['display:none'],
            'src'   => file_create_url($archiveUri),
          ],
        ];
      }
    }
  }

}
