<?php

namespace Drupal\entity_pilot\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_pilot\FlightInterface;
use Drupal\entity_pilot\LegacyMessagingTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for moving a departure from ready to queued.
 */
class DepartureQueueForm extends ContentEntityConfirmFormBase implements ContainerInjectionInterface {

  use LegacyMessagingTrait;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\entity_pilot\DepartureInterface
   */
  protected $entity;

  /**
   * Route name to redirect to.
   *
   * @var string
   */
  protected $redirectRouteName = 'entity_pilot.departure_list';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('logger.factory')->get('entity_pilot')
    );
  }

  /**
   * Constructs a new DepartureDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Entity Pilot logger service.
   */
  public function __construct(EntityManagerInterface $entity_manager, LoggerInterface $logger) {
    $this->entityManager = $entity_manager;
    $this->logger = $logger;
    parent::__construct($entity_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Queue');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $departure = $this->entity;
    $departure->setNewRevision()
      ->setValidationRequired(FALSE)
      ->setRevisionLog($this->t('Queued by @name', [
        '@name' => $this->currentUser()->getUsername(),
      ]))
      ->setStatus(FlightInterface::STATUS_QUEUED)
      ->save();
    $this->setMessage($this->t('@label %label has been queued.', [
      '%label' => $this->entity->label(),
      '@label' => $this->entity->getEntityType()->getLabel(),
    ]));
    $this->logger->notice('@label %label has been queued.', [
      '%label' => $this->entity->label(),
      '@label' => $this->entity->getEntityType()->getLabel(),
    ]);
    $form_state->setRedirect($this->redirectRouteName);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to queue %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Queue the flight for sending to Entity Pilot on next cron-run.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->redirectRouteName);
  }

}
