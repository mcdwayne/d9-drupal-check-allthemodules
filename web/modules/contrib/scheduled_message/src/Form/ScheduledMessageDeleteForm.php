<?php

namespace Drupal\scheduled_message\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Delete a scheduled message confirmation form.
 */
class ScheduledMessageDeleteForm extends ConfirmFormBase {
  /**
   * The parent entity containing the scheduled message to be deleted.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityBase
   */
  protected $baseEntity;

  /**
   * The scheduled message to be deleted.
   *
   * @var \Drupal\scheduled_message\Plugin\ScheduledMessageInterface
   */
  protected $scheduledMessage;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @scheduled_message schedule from the @base_entity entity?', ['@scheduled_message' => $this->scheduledMessage->label(), '@base_entity' => $this->baseEntity->label()]);
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
  public function getCancelUrl() {
    return $this->baseEntity->urlInfo('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scheduled_message_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $entity_id = NULL, $scheduled_message = NULL) {
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $this->baseEntity = $storage->load($entity_id);
    $this->scheduledMessage = $this->baseEntity->getMessage($scheduled_message);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->baseEntity->deleteMessage($this->scheduledMessage);
    drupal_set_message($this->t('The scheduled message %name has been deleted.', ['%name' => $this->scheduledMessage->label()]));
    $form_state->setRedirectUrl($this->baseEntity->urlInfo('edit-form'));
  }

}
