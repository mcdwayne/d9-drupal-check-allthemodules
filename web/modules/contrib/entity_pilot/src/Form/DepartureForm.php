<?php

namespace Drupal\entity_pilot\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_pilot\DepartureStorageInterface;
use Drupal\entity_pilot\FlightInterface;
use Drupal\entity_pilot\LegacyMessagingTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the departure edit forms.
 */
class DepartureForm extends ContentEntityForm {

  use LegacyMessagingTrait;

  /**
   * The departure entity.
   *
   * @var \Drupal\entity_pilot\DepartureInterface
   */
  protected $entity;

  /**
   * The Entity Pilot account storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $accountStorage;

  /**
   * The departure entity storage.
   *
   * @var \Drupal\entity_pilot\DepartureStorageInterface
   */
  protected $departureStorage;

  /**
   * Constructs a DepartureForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $account_storage
   *   The account storage.
   * @param \Drupal\entity_pilot\DepartureStorageInterface $departure_storage
   *   The departure storage.
   * @param \Psr\Log\LoggerInterface $logger
   *   Entity Pilot logger service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $account_storage, DepartureStorageInterface $departure_storage, LoggerInterface $logger) {
    parent::__construct($entity_manager);
    $this->accountStorage = $account_storage;
    $this->departureStorage = $departure_storage;
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
      $entity_manager->getStorage('ep_departure'),
      $container->get('logger.factory')->get('entity_pilot')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $departure = $this->entity;
    if (!$departure->isNew()) {
      $departure->setRevisionLog(NULL);
    }
    // Always use the default revision setting.
    $departure->setNewRevision(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $departure = $this->entity;
    $account = $this->currentUser();

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit departure %label', ['%label' => $departure->label()]);
    }

    $form['status'] = [
      '#title' => $this->t('Status'),
      '#type' => 'item',
      '#markup' => $this->departureStorage->getAllowedStates()[$departure->getStatus()],
    ];

    if ($remote_id = $departure->getRemoteId()) {
      $form['remote_id_show'] = [
        '#title' => $this->t('Remote ID'),
        '#markup' => $remote_id,
        '#type' => 'item',
      ];
      $form['remote_id'] = [
        '#type' => 'value',
        '#value' => $remote_id,
      ];
    }

    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    $form['revision_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Revision information'),
      '#open' => FALSE,
      '#attributes' => [
        'class' => ['ep-departure-form-revision'],
      ],
      '#attached' => [
        'library' => ['entity_pilot/drupal.departure_form'],
      ],
      '#weight' => 30,
      '#access' => $departure->isNewRevision() || $account->hasPermission('administer entity_pilot departures'),
    ];

    $form['revision_information']['revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $departure->isNewRevision(),
      '#access' => $account->hasPermission('administer entity_pilot departures'),
    ];

    // Check the revision log checkbox when the log textarea is filled in.
    // This must not happen if "Create new revision" is enabled by default,
    // since the state would auto-disable the checkbox otherwise.
    if (!$departure->isNewRevision()) {
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
      '#default_value' => $departure->getRevisionLog(),
      '#description' => $this->t('Briefly describe the changes you have made.'),
    ];

    return parent::form($form, $form_state, $departure);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the departure object from the submitted values.
    parent::submitForm($form, $form_state);

    /** @var \Drupal\entity_pilot\DepartureInterface $departure */
    $departure = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('revision')) {
      $departure->setNewRevision();
    }
    // Reset status to pending.
    if (!$departure->isPending()) {
      $departure->setStatus(FlightInterface::STATUS_PENDING);
      // Resetting status to pending constitutes a new revision.
      $departure->setNewRevision();
      // If no revision log is set, add one.
      if (!$departure->getRevisionLog()) {
        $departure->setRevisionLog($this->t('Status reset to pending by @name', [
          '@name' => $this->currentUser()->getUsername(),
        ]));
      }
    }

    return $departure;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $departure = $this->entity;
    $insert = $departure->isNew();
    $departure->save();
    $watchdog_args = ['@type' => $departure->bundle(), '%info' => $departure->label()];
    $account = $this->accountStorage->load($departure->bundle());
    $t_args = ['@type' => $account->label(), '%info' => $departure->label()];

    if ($insert) {
      $this->logger->notice('@type: added %info.', $watchdog_args);
      $this->setMessage($this->t('Departure for @type account named %info has been created.', $t_args));
    }
    else {
      $this->logger->notice('@type: updated %info.', $watchdog_args);
      $this->setMessage($this->t('Departure for @type account named %info has been updated.', $t_args));
    }

    if ($departure->id()) {
      $form_state->setValue('id', $departure->id());
      $form_state->set('id', $departure->id());
      $form_state->setRedirect('entity_pilot.departure_list');
      if ($insert) {
        $form_state->setRedirect('entity.ep_departure.approve_form', ['ep_departure' => $departure->id()]);
      }
    }
    else {
      // In the unlikely case something went wrong on save, the arrival will
      // be rebuilt and arrival form redisplayed.
      $this->setMessage($this->t('The departure could not be saved.'), 'error');
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
