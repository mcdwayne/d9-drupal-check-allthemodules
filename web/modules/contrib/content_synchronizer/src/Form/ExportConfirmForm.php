<?php

namespace Drupal\content_synchronizer\Form;

use Drupal\content_synchronizer\Entity\ExportEntity;
use Drupal\content_synchronizer\Processors\BatchExportProcessor;
use Drupal\content_synchronizer\Processors\ExportEntityWriter;
use Drupal\content_synchronizer\Service\ArchiveDownloader;
use Drupal\content_synchronizer\Service\ExportManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExportConfirmForm.
 *
 * @package Drupal\content_synchronizer\Form
 */
class ExportConfirmForm extends ConfirmFormBase {

  const FORM_ID = 'content_synchronizer.export_confirm';
  const ARCHIVE_PARAMS = 'archive';

  /**
   * The array of nodes to delete.
   *
   * @var string[][]
   */
  protected $nodeInfo = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * The export manager.
   *
   * @var \Drupal\content_synchronizer\Service\ExportManager
   */
  protected $exportManager;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('node');

    $this->exportManager = \Drupal::service(ExportManager::SERVICE_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $this->t('Export entity');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Export');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return static::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['content_synchronizer'] = [
      '#type'  => 'fieldset',
      '#title' => t('Export'),
    ];

    $form['content_synchronizer']['quick_export'] = [
      '#type'        => 'submit',
      '#value'       => t('Export entity'),
      '#description' => t('Download the entity .zip file with dependencies'),
      '#button_type' => 'primary',
      '#submit'      => [[$this, 'onQuickExport']],
    ];

    $exportsListOptions = $this->exportManager->getExportsListOptions();
    if (!empty($exportsListOptions)) {
      $form['content_synchronizer']['exports_list'] = [
        '#type'    => 'checkboxes',
        '#title'   => t('Or add the entity to an existing export'),
        '#options' => $exportsListOptions,
      ];

      $form['content_synchronizer']['add_to_export'] = [
        '#type'   => 'submit',
        '#value'  => t('Add to the choosen export'),
        '#submit' => [[$this, 'onAddToExport']],
      ];
    }

    return $form;
  }

  /**
   * Action on quick export submit action.
   *
   * @param array $form
   *   The form build array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public function onQuickExport(array &$form, FormStateInterface $formState) {

    $entities = $this->getEntities();

    $writer = new ExportEntityWriter();
    $writer->initFromId(time());

    $batchExportProcessor = new BatchExportProcessor($writer);
    $batchExportProcessor->exportEntities($entities, [$this, 'onBatchEnd']);
  }

  /**
   * On batch end redirect to the form url.
   *
   * @param string $archiveUri
   *   THe archive to download.
   */
  public function onBatchEnd($archiveUri) {
    $redirectUrl = $this->getTmpStoredData('url');
    \Drupal::service(ArchiveDownloader::SERVICE_NAME)
      ->redirectWithArchivePath($redirectUrl, $archiveUri);
  }

  /**
   * Add entity to an existing entity export.
   *
   * @param array $form
   *   The form build array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public function onAddToExport(array &$form, FormStateInterface $formState) {
    $exportsList = ExportEntity::loadMultiple($formState->getValue('exports_list'));

    foreach ($this->getEntities() as $entity) {
      /** @var \Drupal\content_synchronizer\Entity\ExportEntity $export */
      foreach ($exportsList as $export) {
        $export->addEntity($entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Return the list of entities to export.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   The entities.
   */
  protected function getEntities() {
    $entities = [];

    foreach ($this->getTmpStoredData('entities') as $entityTypeId => $entitiesIds) {
      $entities += \Drupal::entityTypeManager()
        ->getStorage($entityTypeId)
        ->loadMultiple($entitiesIds);
    }

    return $entities;
  }

  /**
   * Return the stored element by key.
   *
   * @param string $key
   *   The key.
   *
   * @return mixed
   *   The value.
   */
  protected function getTmpStoredData($key) {
    return $this->tempStoreFactory->get(static::FORM_ID)
      ->get(\Drupal::currentUser()->id())[$key];
  }

}
