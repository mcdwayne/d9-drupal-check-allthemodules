<?php

namespace Drupal\mail_entity_queue\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\mail_entity_queue\Plugin\MailEntityQueueProcessorPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to add/edit a mail queues.
 */
class MailEntityQueueForm extends BundleEntityFormBase {

  /**
   * The mail queue processor plugin manager.
   *
   * @var \Drupal\mail_entity_queue\Plugin\MailEntityQueueProcessorPluginManagerInterface
   */
  protected $mailEntityQueueProcessorPluginManager;

  /**
   * Constructs a MailEntityQueueForm object.
   *
   * @param \Drupal\mail_entity_queue\Plugin\MailEntityQueueProcessorPluginManagerInterface $processor_manager
   *   The mail queue processor plugin manager.
   */
  public function __construct(MailEntityQueueProcessorPluginManagerInterface $processor_manager) {
    $this->mailEntityQueueProcessorPluginManager = $processor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail_entity_queue.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $processors = [];
    foreach ($this->mailEntityQueueProcessorPluginManager->getDefinitions() as $processor_name => $processor) {
      $processors[$processor_name] = $processor['label']->__toString();
    }

    /** @var \Drupal\mail_entity_queue\Entity\MailEntityQueueInterface $mail_entity_queue */
    $mail_entity_queue = $this->entity;

    if ($mail_entity_queue->isNew()) {
      $form['#title'] = $this->t('Add mail queue');
    }
    else {
      $form['#title'] = $this->t('Edit %label mail queue', ['%label' => $mail_entity_queue->label()]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $mail_entity_queue->label(),
      '#description' => $this->t('Label for the mail queue.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $mail_entity_queue->id(),
      '#machine_name' => ['exists' => '\Drupal\mail_entity_queue\Entity\MailEntityQueue::load'],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $mail_entity_queue->getDescription(),
    ];
    $form['cron_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items to process in each cron run'),
      '#default_value' => $mail_entity_queue->getCronItems(),
      '#min' => '1',
      '#step' => '1',
      '#required' => TRUE,
    ];
    $form['cron_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay'),
      '#description' => $this->t('Pause between execution of queue elements, in milliseconds.'),
      '#default_value' => $mail_entity_queue->getCronDelay(),
      '#min' => '500',
      '#step' => '500',
      '#required' => TRUE,
    ];
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#description' => $this->t('Select whether this queue sends plain or HTML formatted emails.'),
      '#default_value' => $mail_entity_queue->getFormat(),
      '#empty_option' => $this->t('- Select -'),
      '#options'=> [
        'text/plain' => 'text/plain',
        'text/html' => 'text/html',
      ],
      '#required' => TRUE,
    ];
    $form['queue_processor'] = [
      '#type' => 'select',
      '#title' => $this->t('Mail entity queue processor'),
      '#default_value' => $mail_entity_queue->getQueueProcessorId(),
      '#options' => $processors,
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('Saved the %label mail entity queue.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.mail_entity_queue.collection');
  }

}
