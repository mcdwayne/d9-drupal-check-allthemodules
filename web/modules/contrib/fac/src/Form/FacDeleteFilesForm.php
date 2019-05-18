<?php

namespace Drupal\fac\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class FacDeleteFilesForm.
 *
 * @package Drupal\fac\Form
 */
class FacDeleteFilesForm extends ConfirmFormBase {

  /**
   * The fac config storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facConfigStorage;

  /**
   * The fac config entity.
   *
   * @var \Drupal\fac\Entity\FacConfig
   */
  protected $facConfig;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManagerInterface service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->facConfigStorage = $entity_type_manager->getStorage('fac_config');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fac_delete_files_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete all Fast Autocomplete json files for %label?', [
      '%label' => $this->facConfig->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.fac_config.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $fac_config_id
   *   The system_name of the FacConfig to delete the json files of.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $fac_config_id = '') {
    $this->facConfig = $this->facConfigStorage->load($fac_config_id);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->facConfig->deleteFiles();

    $this->messenger()->addStatus($this->t('Fast Autocomplete json files for %label have been deleted.', [
      '%label' => $this->facConfig->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
