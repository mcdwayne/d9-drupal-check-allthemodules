<?php

namespace Drupal\dea_request\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\dea_request\Entity\AccessRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RequestForm extends ContentEntityForm {

  /**
   * @param $entity_type
   * @param $entity_id
   * @param $operation
   */
  public static function title($entity_type, $entity_id, $operation) {
    $entity = \Drupal::entityManager()->getStorage($entity_type)->load($entity_id);
    $bundles = \Drupal::entityManager()->getBundleInfo($entity_type);
    if (\Drupal::request()->get('js')) {
      return t('Request permission to !operation this !type.', [
        '!operation' => $operation,
        '!type' => $bundles[$entity->bundle()]['label'],
      ]);
    }
    else {
      return t('Request permission to %operation this %type.', [
        '%operation' => $operation,
        '%type' => $bundles[$entity->bundle()]['label'],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    parent::__construct($entity_manager);
  }

  /**
   * @inheritDoc
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $storage = \Drupal::entityTypeManager()->getStorage('dea_request');
    $request = $this->getRequest();
    $path = [];
    if ($destination = $request->get('destination')) {
      $path['request_path'] = substr($destination, strlen($request->getBasePath()) + 1);
    }

    $result = \Drupal::entityQuery('dea_request')
      ->condition('uid', \Drupal::currentUser()->id())
      ->condition('entity_type', $route_match->getParameter('entity_type'))
      ->condition('entity_id', $route_match->getParameter('entity_id'))
      ->condition('operation', $route_match->getParameter('operation'))
      ->execute();
    if ($result) {
      return $storage->load(array_pop($result));
    }
    else {
      return $storage->create([
        'uid' => \Drupal::currentUser()->id(),
        'entity_type' => $route_match->getParameter('entity_type'),
        'entity_id' => $route_match->getParameter('entity_id'),
        'operation' => $route_match->getParameter('operation'),
      ] + $path);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['submit']['#access'] = $this->entity->isNew();
    if ($this->entity->isNew()) {
      $form = parent::buildForm($form, $form_state);
      $form['actions']['submit']['#value'] = $this->t('Submit request');
    }
    else {
      $builder = \Drupal::entityTypeManager()->getViewBuilder('dea_request');
      if ($this->entity->getStatus() == AccessRequest::ACCEPTED) {
        drupal_set_message($this->t('Your access request has already been accepted.'));
        $form['entity'] = $builder->view($this->entity, 'accepted');
        $form['proceed'] = [
          '#type' => 'link',
          '#url' => Url::fromUri('base:' . $this->entity->request_path->value),
          '#title' => $this->t('Proceed'),
          '#options' => [
            'attributes' => ['class' => ['button']],
          ],
        ];
      }
      else if ($this->entity->getStatus() == AccessRequest::OPEN) {
        drupal_set_message($this->t('Your access request is in progress.'), 'warning');
        $form['entity'] = $builder->view($this->entity, 'open');
      }
      else {
        drupal_set_message($this->t('Your access request has been denied.'), 'error');
        $form['entity'] = $builder->view($this->entity, 'denied');
      }
    }
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $request = $this->getRequest();
    $target = \Drupal::entityManager()
      ->getStorage($request->get('entity_type'))
      ->load($request->get('entity_id'));
    $form_state->setRedirectUrl($target->toUrl());
    drupal_set_message($this->t('Your request has been filed. You will be notified by email about any progress.'));
  }


}
