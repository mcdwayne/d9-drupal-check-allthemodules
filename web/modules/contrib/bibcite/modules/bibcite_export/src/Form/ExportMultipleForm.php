<?php

namespace Drupal\bibcite_export\Form;

use Drupal\bibcite\Plugin\BibciteFormatManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Export multiple reference entities.
 */
class ExportMultipleForm extends ConfirmFormBase {

  /**
   * The array of entities to export.
   *
   * @var array
   */
  protected $entityInfo = [];

  /**
   * The tempstore object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Bibcite format manager service.
   *
   * @var \Drupal\bibcite\Plugin\BibciteFormatManagerInterface
   */
  protected $formatManager;

  /**
   * Construct new ExportMultipleForm object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\bibcite\Plugin\BibciteFormatManagerInterface $format_manager
   *   The bibcite format manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user object.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, BibciteFormatManagerInterface $format_manager, AccountInterface $current_user) {
    $this->tempStore = $temp_store_factory->get('bibcite_export_multiple');
    $this->formatManager = $format_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('plugin.manager.bibcite_format'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_export_multiple';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Select the format to export these references.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.bibcite_reference.collection');
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
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->entityInfo = $this->tempStore->get($this->currentUser->id());

    $form['entities'] = [
      '#theme' => 'item_list',
      '#items' => $this->entityInfo,
    ];

    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Export format'),
      '#options' => array_map(function ($format) {
        return $format['label'];
      }, $this->formatManager->getExportDefinitions()),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $format = $this->formatManager->createInstance($form_state->getValue('format'));
    $entity_type = 'bibcite_reference';

    $ids = array_keys($this->entityInfo);
    $chunks = array_chunk($ids, 100);

    $operations = [];
    foreach ($chunks as $chunk) {
      $operations[] = [
        'bibcite_export_batch_list', [$chunk, $entity_type, $format],
      ];
    }

    $batch = [
      'title' => t('Export references'),
      'operations' => $operations,
      'file' => drupal_get_path('module', 'bibcite_export') . '/bibcite_export.batch.inc',
      'finished' => 'bibcite_export_batch_finished',
    ];

    batch_set($batch);
  }

}
