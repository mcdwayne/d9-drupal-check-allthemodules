<?php

namespace Drupal\rules_scheduler\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\rules_scheduler\Entity\TaskInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation form for deleting single tasks.
 */
class DeleteTaskConfirmForm extends ConfirmFormBase {

  /**
   * The task to delete.
   *
   * @var \Drupal\rules_scheduler\Entity\TaskInterface
   */
  protected $task;

  /**
   * The rules configuration.
   *
   * @var
   */
  protected $config;

  /**
   * The date.formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The rules_config entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $configStorage;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rules_scheduler_delete_task';
  }

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date.formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The rules_config entity storage class.
   */
  public function __construct(DateFormatterInterface $date_formatter, EntityStorageInterface $storage) {
    $this->dateFormatter = $date_formatter;
    $this->configStorage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('rules_component')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the scheduled task %id?', ['%id' => $this->task->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('rules_scheduler.schedule');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (!empty($this->task->getIdentifier())) {
      $msg = $this->t('This task with custom identifier %id is scheduled to execute component %label on %date. Deleting this task cannot be undone.', [
        // @todo fix label.
        //'%label' => $this->config->id(),
        '%label' => 'temporary-label',
        '%id' => $this->task->getIdentifier(),
        '%date' => $this->dateFormatter->format($this->task->getDate()),
      ]);
    }
    else {
      $msg = $this->t('This task is scheduled to execute component %label on %date. Deleting this task cannot be undone.', [
        // @todo fix label.
        //'%label' => $this->config->id(),
        '%label' => 'temporary-label',
        '%date' => $this->dateFormatter->format($this->task->getDate()),
      ]);
    }
    return $msg;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete scheduled task');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, TaskInterface $task = NULL) {
    $this->task = $task;
    $this->config = $this->configStorage->load($task->getConfig())->getComponent();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->task->delete();
    $this->messenger()->addMessage($this->t('Task %tid has been deleted.', ['%tid' => $this->task->id()]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
