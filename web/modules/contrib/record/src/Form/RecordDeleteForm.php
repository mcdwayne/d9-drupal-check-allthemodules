<?php

namespace Drupal\record\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a record.
 */
class RecordDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * The ingredient logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * Constructs a new RecordDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger_channel
   *   The logger service.
   */
  public function __construct(EntityManagerInterface $entity_manager, LoggerChannelInterface $logger_channel) {
    parent::__construct($entity_manager);
    $this->loggerChannel = $logger_channel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('logger.factory')->get('record')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    // $this->entity->getUuid();
    return $this->t('Are you sure you want to delete record %uuid?', ['%uuid' => 'todo-uuid']);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the record list.
   */
  public function getCancelUrl() {
    return new Url('record.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the record and log the event.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    $this->loggerChannel->notice('@type: deleted %title.',
      [
        '@type' => $this->entity->bundle(),
        '%title' => $this->entity->label(),
      ]);
    $form_state->setRedirect('record.admin');
  }

}
