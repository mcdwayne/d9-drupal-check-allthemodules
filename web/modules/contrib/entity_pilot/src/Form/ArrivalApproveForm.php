<?php

namespace Drupal\entity_pilot\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_pilot\AirTrafficControlInterface;
use Drupal\entity_pilot\ArrivalStorageInterface;
use Drupal\entity_pilot\FlightInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for moving a arrival from pending to ready.
 */
class ArrivalApproveForm extends ArrivalForm {

  /**
   * The air traffic control service.
   *
   * @var \Drupal\entity_pilot\AirTrafficControlInterface
   */
  protected $airTrafficControl;

  /**
   * Constructs a ArrivalApproveForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $account_storage
   *   The account storage.
   * @param \Drupal\entity_pilot\ArrivalStorageInterface $arrival_storage
   *   The arrival storage.
   * @param \Psr\Log\LoggerInterface $logger
   *   Entity Pilot logger service.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $entity_view_builder
   *   The entity view builder for arrival entities.
   * @param \Drupal\entity_pilot\AirTrafficControlInterface $air_traffic_control
   *   Air Traffic Control service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $account_storage, ArrivalStorageInterface $arrival_storage, LoggerInterface $logger, EntityViewBuilderInterface $entity_view_builder, AirTrafficControlInterface $air_traffic_control) {
    parent::__construct($entity_manager, $account_storage, $arrival_storage, $logger);
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
      $entity_manager->getStorage('ep_arrival'),
      $container->get('logger.factory')->get('entity_pilot'),
      $entity_manager->getViewBuilder('ep_arrival'),
      $container->get('entity_pilot.air_traffic_control')
    );
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
    $actions['land'] = [
      '#type' => 'submit',
      '#value' => $this->t('Approve & Land'),
      '#submit' => [
        '::submitForm',
        '::land',
      ],
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\entity_pilot\ArrivalInterface $arrival */
    $arrival = $this->entity;
    // Mark the transition as a new revision.
    $arrival->setNewRevision()
      ->setRevisionLog($this->t('Approved by @name', [
        '@name' => $this->currentUser()->getUsername(),
      ]))
      ->setStatus(FlightInterface::STATUS_READY);
    return $arrival;
  }

  /**
   * {@inheritdoc}
   */
  public function queue(array $form, FormStateInterface $form_state) {
    $arrival = $this->entity;
    // Mark the transition as a new revision.
    $arrival->setNewRevision()
      ->setRevisionLog($this->t('Approved & queued by @name', [
        '@name' => $this->currentUser()->getUsername(),
      ]))
      ->setStatus(FlightInterface::STATUS_QUEUED);
    return $arrival;
  }

  /**
   * {@inheritdoc}
   */
  public function land(array $form, FormStateInterface $form_state) {
    $arrival = $this->entity;
    // Mark the transition as a new revision.
    $arrival->setNewRevision()
      ->setRevisionLog($this->t('Approved and landed by @name', [
        '@name' => $this->currentUser()->getUsername(),
      ]))
      ->setStatus(FlightInterface::STATUS_QUEUED);
    $batch = [
      'operations' => [],
      'finished' => 'Drupal\entity_pilot\Batch\CustomsOfficer::landingFinished',
      'progress_message' => 'Landed @current of @total',
      'title' => $this->t('Landing approved items...'),
    ];
    foreach ($arrival->getApproved() as $passenger_id) {
      $batch['operations'][] = [
        'Drupal\entity_pilot\Batch\CustomsOfficer::landPassenger', [
          $arrival,
          $passenger_id,
        ],
      ];
    }
    if (!empty($batch['operations'])) {
      if ($form_state->getValue('link_departure') && !$arrival->hasLinkedDeparture()) {
        $batch['operations'][] = [
          'Drupal\entity_pilot\Batch\AirTrafficController::linkDeparture', [
            $arrival,
          ],
        ];
      }
      batch_set($batch);
      $form_state->setRedirect('entity_pilot.arrival_list');
    }
    else {
      $this->setMessage('No items were selected for approval', 'error');
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['link_departure'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create a matching departure'),
      '#description' => $this->t('Create a departure containing the same passengers. This will allow you to easily sync content in multiple directions.'),
      '#default_value' => $this->entity->hasLinkedDeparture(),
    ];
    return $form;
  }

}
