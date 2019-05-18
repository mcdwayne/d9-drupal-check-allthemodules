<?php

namespace Drupal\entity_pilot\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_pilot\AirTrafficControlInterface;
use Drupal\entity_pilot\DepartureStorageInterface;
use Drupal\entity_pilot\FlightInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for moving a departure from pending to ready.
 */
class DepartureApproveForm extends DepartureForm {

  /**
   * The air traffic control service.
   *
   * @var \Drupal\entity_pilot\AirTrafficControlInterface
   */
  protected $airTrafficControl;

  /**
   * Constructs a DepartureApproveForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $account_storage
   *   The account storage.
   * @param \Drupal\entity_pilot\DepartureStorageInterface $departure_storage
   *   The departure storage.
   * @param \Psr\Log\LoggerInterface $logger
   *   Entity Pilot logger service.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $entity_view_builder
   *   The entity view builder for departure entities.
   * @param \Drupal\entity_pilot\AirTrafficControlInterface $air_traffic_control
   *   The air traffic control service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $account_storage, DepartureStorageInterface $departure_storage, LoggerInterface $logger, EntityViewBuilderInterface $entity_view_builder, AirTrafficControlInterface $air_traffic_control) {
    parent::__construct($entity_manager, $account_storage, $departure_storage, $logger);
    $this->viewBuilder = $entity_view_builder;
    $this->airTrafficControl = $air_traffic_control;
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
      $container->get('logger.factory')->get('entity_pilot'),
      $entity_manager->getViewBuilder('ep_departure'),
      $container->get('entity_pilot.air_traffic_control')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $departure = $this->entity;
    $dependants = $this->departureStorage->getDependencies($departure);
    $form['title'] = [
      '#markup' => $this->t('This departure will send the following content to Entity Pilot:'),
    ];
    $form['contents'] = [
      $this->viewBuilder->view($departure, 'default'),
    ];
    if (count($dependants)) {
      $items = [];
      foreach ($dependants as $dependant) {
        $items[] = $this->t('@entity (@type ID @id)', [
          '@entity' => $dependant->access('view') ? $dependant->label() : $this->t('<Redacted>'),
          '@type' => $dependant->getEntityType()->getLabel(),
          '@id' => $dependant->id(),
        ]);
      }
      $form['dependants'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Baggage:'),
        '#items' => $items,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    $actions['submit']['#value'] = $this->t('Approve');
    $actions['queue'] = [
      '#type' => 'submit',
      '#value' => $this->t('Approve & Queue'),
      '#submit' => [
        '::submitForm',
        '::queue',
        '::save',
      ],
    ];
    $actions['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Approve & Send'),
      '#submit' => [
        '::submitForm',
        '::send',
      ],
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\entity_pilot\DepartureInterface $departure */
    $departure = $this->entity;
    // Mark the transition as a new revision.
    $departure->setNewRevision()
      ->setRevisionLog($this->t('Approved by @name', [
        '@name' => $this->currentUser()->getUsername(),
      ]))
      ->setStatus(FlightInterface::STATUS_READY);
    return $departure;
  }

  /**
   * {@inheritdoc}
   */
  public function queue(array $form, FormStateInterface $form_state) {
    $departure = $this->entity;
    // Mark the transition as a new revision.
    $departure->setNewRevision()
      ->setRevisionLog($this->t('Approved & queued by @name', [
        '@name' => $this->currentUser()->getUsername(),
      ]))
      ->setStatus(FlightInterface::STATUS_QUEUED);
    return $departure;
  }

  /**
   * {@inheritdoc}
   */
  public function send(array $form, FormStateInterface $form_state) {
    $departure = $this->entity;
    // Mark the transition as a new revision.
    $departure->setNewRevision()
      ->setRevisionLog($this->t('Approved and sent by @name', [
        '@name' => $this->currentUser()->getUsername(),
      ]))
      ->setStatus(FlightInterface::STATUS_QUEUED);
    $batch = [
      'operations' => [
        ['Drupal\entity_pilot\Batch\AirTrafficController::takeoff', [$departure]],
      ],
      'finished' => 'Drupal\entity_pilot\Batch\AirTrafficController::sent',
      'progress_message' => 'Sending...',
      'title' => $this->t('Sending approved items...'),
      'init_message' => $this->t('Please wait while we encrypt and upload your content to Entity Pilot...'),
    ];
    $form_state->setRedirect('entity_pilot.departure_list');
    batch_set($batch);
  }

}
