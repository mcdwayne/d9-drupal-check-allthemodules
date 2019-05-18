<?php

namespace Drupal\entity_pilot\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_pilot\ArrivalStorageInterface;
use Drupal\entity_pilot\FlightInterface;
use Drupal\entity_pilot\LegacyMessagingTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the arrival edit forms.
 */
class ArrivalForm extends ContentEntityForm {

  use LegacyMessagingTrait;

  /**
   * The arrival entity.
   *
   * @var \Drupal\entity_pilot\ArrivalInterface
   */
  protected $entity;

  /**
   * The Entity Pilot account storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $accountStorage;

  /**
   * The arrival entity storage.
   *
   * @var \Drupal\entity_pilot\ArrivalStorageInterface
   */
  protected $arrivalStorage;

  /**
   * Constructs a ArrivalForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $account_storage
   *   The account storage.
   * @param \Drupal\entity_pilot\ArrivalStorageInterface $arrival_storage
   *   The arrival storage.
   * @param \Psr\Log\LoggerInterface $logger
   *   Entity Pilot logger service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $account_storage, ArrivalStorageInterface $arrival_storage, LoggerInterface $logger) {
    parent::__construct($entity_manager);
    $this->accountStorage = $account_storage;
    $this->arrivalStorage = $arrival_storage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager,
      $entity_manager->getStorage('ep_account'),
      $entity_manager->getStorage('ep_arrival'),
      $container->get('logger.factory')->get('entity_pilot')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $arrival = $this->entity;
    if (!$arrival->isNew()) {
      $arrival->setRevisionLog(NULL);
    }
    // Always use the default revision setting.
    $arrival->setNewRevision(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $arrival = $this->entity;
    $account = $this->currentUser();

    $form['status'] = [
      '#title' => $this->t('Status'),
      '#type' => 'item',
      '#markup' => $this->arrivalStorage->getAllowedStates()[$arrival->getStatus()],
    ];

    if ($arrival->getRemoteId()) {
      $form['remote_id_display'] = [
        '#title' => $this->t('Remote ID'),
        '#markup' => $arrival->getRemoteId(),
        '#type' => 'item',
      ];
    }

    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    $form['revision_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Revision information'),
      '#open' => FALSE,
      '#attributes' => [
        'class' => ['ep-arrival-form-revision'],
      ],
      '#attached' => [
        'library' => ['entity_pilot/drupal.arrival_form'],
      ],
      '#weight' => 30,
      '#access' => $arrival->isNewRevision() || $account->hasPermission('administer entity_pilot arrivals'),
    ];

    $form['revision_information']['revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $arrival->isNewRevision(),
      '#access' => $account->hasPermission('administer entity_pilot arrivals'),
    ];

    // Check the revision log checkbox when the log textarea is filled in.
    // This must not happen if "Create new revision" is enabled by default,
    // since the state would auto-disable the checkbox otherwise.
    if (!$arrival->isNewRevision()) {
      $form['revision_information']['revision']['#states'] = [
        'checked' => [
          'textarea[name="log"]' => ['empty' => FALSE],
        ],
      ];
    }

    $form['revision_information']['log'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Revision log message'),
      '#rows' => 4,
      '#default_value' => $arrival->getRevisionLog(),
      '#description' => $this->t('Briefly describe the changes you have made.'),
    ];

    return parent::form($form, $form_state, $arrival);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the arrival object from the submitted values.
    parent::submitForm($form, $form_state);
    /** @var \Drupal\entity_pilot\ArrivalInterface $arrival */
    $arrival = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('revision')) {
      $arrival->setNewRevision();
    }
    // Reset status to pending.
    if (!$arrival->isPending()) {
      $arrival->setStatus(FlightInterface::STATUS_PENDING);
      // Resetting status to pending constitutes a new revision.
      $arrival->setNewRevision();
      // If no revision log is set, add one.
      if (!$arrival->getRevisionLog()) {
        $arrival->setRevisionLog($this->t('Status reset to pending by @name', [
          '@name' => $this->currentUser()->getUsername(),
        ]));
      }
    }

    return $arrival;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $arrival = $this->entity;
    $insert = $arrival->isNew();
    $arrival->save();
    $watchdog_args = ['@type' => $arrival->bundle(), '%info' => $arrival->label()];
    $account = $this->accountStorage->load($arrival->bundle());
    $t_args = ['@type' => $account->label(), '%info' => $arrival->label()];

    if ($insert) {
      $this->logger->notice('@type: added %info.', $watchdog_args);
      $this->setMessage($this->t('Arrival for @type account named %info has been created.', $t_args));
    }
    else {
      $this->logger->notice('@type: updated %info.', $watchdog_args);
      $this->setMessage($this->t('Arrival for @type account named %info has been updated.', $t_args));
    }

    if ($arrival->id()) {
      $form_state->setValue('id', $arrival->id());
      $form_state->set('id', $arrival->id());
      $form_state->setRedirect('entity_pilot.arrival_list');
    }
    else {
      // In the unlikely case something went wrong on save, the arrival will
      // be rebuilt and arrival form redisplayed.
      $this->setMessage($this->t('The arrival could not be saved.'), 'error');
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // @todo Remove this when https://www.drupal.org/node/2481731 is resolved.
    $this->_serviceIds = [];
    $vars = get_object_vars($this);
    foreach ($vars as $key => $value) {
      if (is_object($value) && isset($value->_serviceId)) {
        // If a class member was instantiated by the dependency injection
        // container, only store its ID so it can be used to get a fresh object
        // on unserialization.
        $this->_serviceIds[$key] = $value->_serviceId;
        unset($vars[$key]);
      }
      // Special case the container, which might not have a service ID.
      elseif ($value instanceof ContainerInterface) {
        $this->_serviceIds[$key] = 'service_container';
        unset($vars[$key]);
      }
    }

    unset($vars['logger']);
    return array_keys($vars);
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    // @todo Remove this when https://www.drupal.org/node/2481731 is resolved.
    $container = \Drupal::getContainer();
    foreach ($this->_serviceIds as $key => $service_id) {
      $this->$key = $container->get($service_id);
    }
    $this->_serviceIds = [];
    $this->logger = $container->get('logger.factory')->get('entity_pilot');
  }

}
