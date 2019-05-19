<?php

namespace Drupal\transcoding\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\transcoding\Entity\TranscodingService;

/**
 * Form controller for Transcoding job edit forms.
 *
 * @ingroup transcoding
 */
class TranscodingJobForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\transcoding\Entity\TranscodingJob */
    $form = parent::buildForm($form, $form_state);
    $form['#tree'] = TRUE;
    $form['service']['widget']['#ajax'] = [
      'callback' => [$this, 'serviceConfigAjax'],
      'wrapper' => 'service-config-wrapper',
    ];
    $form['media_bundle']['widget']['#ajax'] = [
      'callback' => [$this, 'bundleConfigAjax'],
      'wrapper' => 'bundle-config-wrapper',
    ];
    $form['field_config_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bundle-config-wrapper'],
      '#weight' => $form['media_bundle']['#weight'] + 0.1,
    ];
    $form['service_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Transcoding job configuration'),
      '#attributes' => ['id' => 'service-config-wrapper'],
      '#open' => TRUE,
      '#weight' => 100,
    ];
    $form['service_config']['form'] = [];
    if (!empty($form_state->getValues()['service'][0]['target_id'])) {
      $service = TranscodingService::load($form_state->getValues()['service'][0]['target_id']);
      $subFormState = SubformState::createForSubform($form['service_config']['form'], $form, $form_state);
      $form['service_config']['form'] =  $service->getPlugin()->buildJobForm($form['service_config']['form'], $subFormState);
    }
    else {
      $form['service_config']['form'] = ['#markup' => $this->t('Select a transcoding service.')];
    }
    $form['actions']['#weight'] = 101;

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $subFormState = SubformState::createForSubform($form['service_config']['form'], $form, $form_state);
    if (!empty($form_state->getValues()['service'][0]['target_id'])) {
      $service = TranscodingService::load($form_state->getValues()['service'][0]['target_id']);
      $service->getPlugin()
        ->validateJobForm($form['service_config']['form'], $subFormState);
    }
    return parent::validateForm($form, $form_state);
  }

  public function serviceConfigAjax(array &$form, FormStateInterface $form_state) {
    return $form['service_config'];
  }

  public function bundleConfigAjax(array &$form, FormStateInterface $form_state) {
    return $form['field_config_wrapper'];
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $subFormState = SubformState::createForSubform($form['service_config']['form'], $form, $form_state);
    $service = TranscodingService::load($form_state->getValues()['service'][0]['target_id']);
    $this->entity->set('service_data', $service->getPlugin()->submitJobForm($form['service_config']['form'], $subFormState));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addStatus($this->t('Created the %label Transcoding job.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Transcoding job.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.transcoding_job.canonical', ['transcoding_job' => $entity->id()]);
  }

}
