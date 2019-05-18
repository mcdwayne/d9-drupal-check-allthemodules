<?php

namespace Drupal\entity_pilot\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\Error;
use Drupal\entity_pilot\ArrivalStorageInterface;
use Drupal\entity_pilot\Exception\TransportException;
use Drupal\entity_pilot\SiteUrlTrait;
use Drupal\entity_pilot\TransportInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the arrival edit forms.
 */
class ArrivalAddForm extends ArrivalForm {

  use SiteUrlTrait;

  /**
   * Entity Pilot transport service.
   *
   * @var \Drupal\entity_pilot\TransportInterface
   */
  protected $transport;

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
   * @param \Drupal\entity_pilot\TransportInterface $transport
   *   The Entity Pilot transport service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   URL Generator service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $account_storage, ArrivalStorageInterface $arrival_storage, LoggerInterface $logger, TransportInterface $transport, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_manager, $account_storage, $arrival_storage, $logger);
    $this->accountStorage = $account_storage;
    $this->arrivalStorage = $arrival_storage;
    $this->logger = $logger;
    $this->transport = $transport;
    $this->urlGenerator = $url_generator;
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
      $container->get('entity_pilot.transport'),
      $container->get('url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $arrival = $this->entity;
    $form += parent::form($form, $form_state);
    // This is auto-created from the incoming flight.
    $form['info']['#access'] = FALSE;

    unset($form['status']);

    $form['search_container'] = [
      '#type' => 'container',
      '#weight' => -4,
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];
    $form['search_container']['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#attributes' => [
        'placeholder' => $this->t('Title or Flight ID'),
      ],
      '#default_value' => $form_state->hasValue('search') ? $form_state->getValue('search') : NULL,
    ];
    $form['search_container']['go'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#limit_validation_errors' => [[]],
      '#submit' => ['::search'],
    ];

    try {
      $flights = $this->transport->queryFlights($arrival->getAccount(), $form_state->hasValue('search') ? $form_state->getValue('search') : '');
      $storage = $form_state->get('storage');
      $storage['flights'] = $flights;
      $form_state->set('storage', $storage);
    }
    catch (TransportException $e) {
      $this->setMessage($this->t('An error occurred connecting to the Entity Pilot backend, please try again later or visit the <a href="https://entitypilot.com/status">Entity Pilot status page</a>.'), 'error');
      $this->setMessage($this->t('The message was: @message (@code)', [
        '@message' => $e->getMessage(),
        '@code' => $e->getCode(),
      ]), 'error');
      $variables = Error::decodeException($e);
      $this->logger->error($e->getMessage(), $variables);
      $form_state->set('entity_pilot_exception', $e);
      return [];
    }

    $options = [];
    $options['-1'] = [
      // Add an empty row to allow searching without selection.
      'id' => '-1',
      'info' => '',
      'contents' => '',
      'created' => '',
      '#attributes' => ['class' => ['visually-hidden']],
    ];
    foreach ($flights as $flight) {
      $date = new \DateTime($flight->getChanged());
      $options[$flight->getRemoteId()] = [
        'id' => $flight->getRemoteId(),
        'info' => $flight->getInfo(),
        'contents' => $this->formatPlural($flight->getCount(), '1 item', '@count items'),
        'created' => $date->format('d/m/Y'),
      ];
    }
    $header = [
      'id' => $this->t('ID'),
      'info' => $this->t('Info'),
      'contents' => $this->t('Contents'),
      'created' => $this->t('Created'),
    ];
    $form['remote_id'] = [
      '#type' => 'tableselect',
      '#weight' => -3,
      '#multiple' => FALSE,
      '#header' => $header,
      '#required' => TRUE,
      '#options' => $options,
      '#empty' => $this->t('No matching items'),
      '#caption' => $this->t('Select remote flight'),
      '#default_value' => -1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    if ($form_state->get('entity_pilot_exception')) {
      return [];
    }
    return parent::actions($form, $form_state);
  }

  /**
   * Form submission handler for search button.
   */
  public function search($form, FormStateInterface &$form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $arrival = $this->entity;
    $values = $form_state->getValues();
    $storage = $form_state->get('storage');
    if (!isset($values['remote_id']) ||
      !isset($storage['flights'][$values['remote_id']])) {
      $this->setMessage($this->t('Please select a valid flight'));
      $form_state->setRebuild();
    }
    else {
      /* @var \Drupal\entity_pilot\Data\FlightManifestInterface $flight */
      $flight = $storage['flights'][$values['remote_id']];
      $form_state->setTemporaryValue('flight', $flight);
      unset($storage['flights']);
      $arrival->setInfo($flight->getInfo())
        ->setRemoteId($flight->getRemoteId())
        ->setRevisionLog($this->t('Fetched from Entity Pilot.'));
      parent::save($form, $form_state);
      $batch = [
        'operations' => [],
        'finished' => 'Drupal\entity_pilot\Batch\AirTrafficController::landed',
        'progress_message' => 'Fetching contents',
        'title' => $this->t('Fetching flight contents...'),
        'init_message' => $this->t('Please wait while we fetch and decrypt your content from Entity Pilot for you to review and approve...'),
      ];
      $batch['operations'][] = [
        'Drupal\entity_pilot\Batch\AirTrafficController::land',
        [$arrival, $flight, $this->getSite()],
      ];
      batch_set($batch);
      $form_state->setRedirect('entity.ep_arrival.approve_form', ['ep_arrival' => $arrival->id()]);
      $form_state->set('storage', $storage);
    }
  }

}
