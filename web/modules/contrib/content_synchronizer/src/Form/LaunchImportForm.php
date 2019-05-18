<?php

namespace Drupal\content_synchronizer\Form;

use Drupal\content_synchronizer\Entity\ImportEntity;
use Drupal\content_synchronizer\Processors\BatchImportProcessor;
use Drupal\content_synchronizer\Processors\ImportProcessor;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Launch Import Form.
 */
class LaunchImportForm extends FormBase {


  const CREATION_ACTION_LABEL = 'Action on entity creation';
  const CREATION_ACTION_PUBLISH_LABEL = 'Publish created content';
  const CREATION_ACTION_UNPUBLISH_LABEL = 'Do not publish created content';

  const UPDATE_ACTION_LABEL = 'Action on entity update';
  const UPDATE_ACTION_SYSTEMATIC_LABEL = 'Always update existing entity with importing content';
  const UPDATE_ACTION_IF_RECENT_LABEL = 'Update existing entity with importing content only if the last change date of importing content is more recent than the last change date of the corresponding existing entity';
  const UPDATE_ACTION_NO_UPDATE_LABEL = 'Do not update existing content';

  /**
   * The import entity.
   *
   * @var \Drupal\content_synchronizer\Entity\ImportEntity
   */
  protected $import;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_synchronizer.import.launch';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\content_synchronizer\Entity\ImportEntity $import */
    $this->import = $form_state->getBuildInfo()['import'];

    if ($this->import->getProcessingStatus() === ImportEntity::STATUS_NOT_STARTED) {
      // Settings.
      $form['settings'] = [
        '#type'  => 'fieldset',
        '#title' => t('Settings'),
      ];

      $form['settings']['creationType'] = [
        '#type'          => 'radios',
        '#title'         => t(static::CREATION_ACTION_LABEL),
        '#options'       => static::getCreateOptions(),
        '#default_value' => ImportProcessor::PUBLICATION_PUBLISH
      ];

      $form['settings']['updateType'] = [
        '#type'          => 'radios',
        '#title'         => t(static::UPDATE_ACTION_LABEL),
        '#options'       => static::getUpdateOptions(),
        '#default_value' => ImportProcessor::UPDATE_IF_RECENT
      ];
    }

    // Entity list.
    $this->initRootEntitiesList($form);
    if ($this->import->getProcessingStatus() === ImportEntity::STATUS_NOT_STARTED) {

      $form['launch'] = [
        '#type'        => 'submit',
        '#value'       => t('Import selected entities'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * Return create Options.
   *
   * @return array
   *   The create options.
   */
  public static function getCreateOptions() {
    return [
      ImportProcessor::PUBLICATION_PUBLISH   => t(static::CREATION_ACTION_PUBLISH_LABEL),
      ImportProcessor::PUBLICATION_UNPUBLISH => t(static::CREATION_ACTION_UNPUBLISH_LABEL),
    ];
  }

  /**
   * Return update options.
   *
   * @return array
   *   The update options.
   */
  public static function getUpdateOptions() {
    return [
      ImportProcessor::UPDATE_SYSTEMATIC => t(static::UPDATE_ACTION_SYSTEMATIC_LABEL),
      ImportProcessor::UPDATE_IF_RECENT  => t(static::UPDATE_ACTION_IF_RECENT_LABEL),
      ImportProcessor::UPDATE_NO_UPDATE  => t(static::UPDATE_ACTION_NO_UPDATE_LABEL),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batchImport = new BatchImportProcessor();

    $batchImport->import(
      $this->import,
      array_intersect_key($this->import->getRootsEntities(), array_flip($form_state->getUserInput()['entities_to_import'])),
      [
        $this,
      'onBatchEnd'
    ], $form_state->getValue('creationType'), $form_state->getValue('updateType'));
  }

  /**
   * The callback after batch process.
   */
  public function onBatchEnd($data) {
    $this->import->removeArchive();
  }

  /**
   * Init the root entities list for display.
   */
  protected function initRootEntitiesList(array &$form) {
    $rootEntities = $this->import->getRootsEntities();
    $build = [
      '#theme'         => 'entities_list_table',
      '#entities'      => $rootEntities,
      '#status_or_bundle' => t('status'),
      '#checkbox_name' => 'entities_to_import[]',
      '#title'         => t('Entities to import'),
      '#attached'      => [
        'library' => ['content_synchronizer/list']
      ]
    ];

    $form['entities_list'] = $build;
  }

}
