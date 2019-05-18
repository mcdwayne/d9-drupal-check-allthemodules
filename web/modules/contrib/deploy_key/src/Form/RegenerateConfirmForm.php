<?php

namespace Drupal\deploy_key\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\deploy_key\KeyManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class RegenerateConfirmForm extends ConfirmFormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Key manager.
   *
   * @var \Drupal\deploy_key\KeyManager
   */
  protected $keyManager;

  /**
   * RegenerateConfirmForm constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, CurrentRouteMatch $routeMatch, KeyManager $keyManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $routeMatch;
    $this->keyManager = $keyManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('deploy_key.key_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deploy_key_regenerate_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to regenerate this key?');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_type = $this->routeMatch->getParameter('entity_type');
    $entity_id = $this->routeMatch->getParameter('id');
    if (!$entity_id || !$entity_type) {
      throw new BadRequestHttpException();
    }
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $entity = $storage->load($entity_id);
      if (!$entity->access('view')) {
        throw new AccessDeniedHttpException();
      }
    }
    catch (\Exception $e) {
      throw new NotFoundHttpException();
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $this->routeMatch->getParameter('entity_type');
    $entity_id = $this->routeMatch->getParameter('id');
    if (!$entity_id || !$entity_type) {
      throw new BadRequestHttpException();
    }
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $entity = $storage->load($entity_id);
    if (!$entity->access('view')) {
      throw new AccessDeniedHttpException();
    }
    $this->keyManager->generateKeyForEntity($entity, TRUE);
    $form_state->setRedirect(sprintf('entity.%s.canonical', $entity_type), [
      $entity_type => $entity_id,
    ]);
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    $entity_type = $this->routeMatch->getParameter('entity_type');
    $entity_id = $this->routeMatch->getParameter('id');
    return Url::fromRoute(sprintf('entity.%s.canonical', $entity_type), [
      $entity_type => $entity_id,
    ]);
  }

}
