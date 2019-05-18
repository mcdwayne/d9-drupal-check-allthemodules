<?php

namespace Drupal\entity_pilot\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_pilot\LegacyMessagingTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base confirmation form for deleting a flight entity.
 */
abstract class FlightDeleteFormBase extends ContentEntityConfirmFormBase {

  use LegacyMessagingTrait;

  /**
   * Redirect route name.
   *
   * @var string
   */
  protected $redirectRouteName;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
    parent::__construct($entity_manager);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->redirectRouteName);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $this->setMessage($this->t('@label %label has been deleted.', [
      '%label' => $this->entity->label(),
      '@label' => $this->entity->getEntityType()->getLabel(),
    ]));
    $this->logger->notice('@label %label has been deleted.', [
      '%label' => $this->entity->label(),
      '@label' => $this->entity->getEntityType()->getLabel(),
    ]);
    $form_state->setRedirect($this->redirectRouteName);
  }

}
