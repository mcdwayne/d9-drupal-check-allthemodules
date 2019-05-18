<?php

namespace Drupal\cloud\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for the Cloud entity delete confirm forms.
 *
 * @ingroup cloud
 */
class CloudContentDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  protected $manager;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $manager,
                              EntityRepositoryInterface $entity_repository,
                              Messenger $messenger) {
    $this->manager = $manager;
    $this->entityRepository = $entity_repository;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity.repository'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {

    $entity = $this->entity;

    return t('Are you sure you want to delete entity %name?', [
      '%name' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('canonical', ['cloud_context', $this->entity->getCloudContext()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $entity = $this->entity;
    $entity->delete();

    $this->messenger->addMessage(
      $this->t('content @type: deleted "@label".', [
        '@type'  => $entity->bundle(),
        '@label' => $entity->label(),
      ])
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
