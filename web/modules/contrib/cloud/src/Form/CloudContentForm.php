<?php

namespace Drupal\cloud\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Messenger\Messenger;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the Cloud entity edit forms.
 *
 * @ingroup cloud
 */
class CloudContentForm extends ContentEntityForm {

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
   * Override actions()
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $entity = $this->entity;
    foreach ($actions as $key => $action) {
      if (isset($actions[$key]['#url'])
      && method_exists($this->entity, 'cloud_context')) {
        $actions[$key]['#url']->setRouteParameter('cloud_context', $entity->getCloudContext());
      }
    }
    return $actions;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, FormStateInterface $form_state) {
    // Build the entity object from the submitted values.
    $entity = parent::submit($form, $form_state);

    return $entity;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->entity;

    $status = 'error';
    $message = $this->t('The @label "%label" was not saved.', [
      '@label' => $entity->getEntityType()->getLabel(),
      '%label' => $entity->label(),
    ]);
    if ($entity->save()) {

      $status = 'status';
      $message = $this->t('The @label "%label" has been saved.', [
        '@label' => $entity->getEntityType()->getLabel(),
        '%label' => $entity->label(),
      ]);
    }

    $this->messenger->addMessage($message, $status);
  }

}
