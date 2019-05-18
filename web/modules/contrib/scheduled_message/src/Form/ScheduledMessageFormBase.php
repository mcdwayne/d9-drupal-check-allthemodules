<?php

namespace Drupal\scheduled_message\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\scheduled_message\Plugin\ScheduledMessageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class ScheduledMessageFormBase.
 *
 * @package Drupal\scheduled_message\Form
 */
abstract class ScheduledMessageFormBase extends FormBase {

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
   * The ScheduledMessageManager Service.
   *
   * @var \Drupal\scheduled_message\Plugin\ScheduledMessageManager
   */
  protected $scheduledMessageManager;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(
      EntityTypeManager $entity_type_manager,
      ScheduledMessageManager $scheduledMessageManager
    ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->scheduledMessageManager = $scheduledMessageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.scheduled_message')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scheduled_message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $entity_id = NULL, $scheduled_message = NULL) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $this->baseEntity = $storage->load($entity_id);
    $this->scheduledMessage = $this->getMessagePlugin($scheduled_message);

    $form['uuid'] = [
      '#type' => 'value',
      '#value' => $this->scheduledMessage->getUuid(),
    ];
    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->scheduledMessage->getPluginId(),
    ];

    $form['data'] = [
      'entity_type' => [
        '#type' => 'value',
        '#value' => $this->baseEntity->getEntityType()->get('bundle_of'),
      ],
      'entity_id' => [
        '#type' => 'value',
        '#value' => $entity_id,
      ],
    ];
    $subform_state = SubformState::createForSubform($form['data'], $form, $form_state);
    $form['data'] = $this->scheduledMessage->buildConfigurationForm($form['data'], $subform_state);
    $form['data']['#tree'] = TRUE;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->baseEntity->urlInfo('edit-form'),
      '#attributes' => ['class' => ['button']],
    ];
    return $form;
  }

  /**
   * Retrieve the message plugin based on its id.
   *
   * @param string $scheduled_message
   *   The message machine_id.
   *
   * @return \Drupal\scheduled_message\Plugin\ScheduledMessageInterface
   *   The message plugin.
   */
  abstract protected function getMessagePlugin($scheduled_message);

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->scheduledMessage->validateConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The scheduled message configuration is stored in the 'data' key in the
    // form, pass that through for submission.
    $this->scheduledMessage->submitConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));

    if (!$this->scheduledMessage->getUuid()) {
      $this->baseEntity->addMessage($this->scheduledMessage->getConfiguration());
    }
    $this->baseEntity->save();

    drupal_set_message($this->t('The scheduled message successfully applied. All related entities will be queued for message generation.'));
    $form_state->setRedirectUrl($this->baseEntity->urlInfo('edit-form'));
  }

}
