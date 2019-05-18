<?php

namespace Drupal\hidden_tab\Form\Base;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class EntityFormBase extends ContentEntityForm {

  protected $targetEntityType = 'node';

  protected $prefix = '';

  protected $type;

  /**
   * To get some default entity properties from uri params, if any.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $params;

  /**
   * To find entities already existing.
   *
   * Used by validation so no more than one exists.
   *
   * @var \Drupal\hidden_tab\Service\CreditCharging;
   */
  protected $creditService;

  /**
   * To load user on form validation.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * To load target entity
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityRepository;

  /**
   * To generate a default secret key.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository = NULL,
                              EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
                              TimeInterface $time = NULL,
                              EntityStorageInterface $user_storage = NULL,
                              MessengerInterface $messenger = NULL,
                              ParameterBag $params = NULL,
                              Php $uuid = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    if ($user_storage === NULL
      || $params === NULL
      || $messenger === NULL
      || $uuid === NULL
      || $entity_repository === NULL) {
      throw new \LogicException('illegal state');
    }
    $this->userStorage = $user_storage;
    $this->params = $params;
    $this->messenger = $messenger;
    $this->uuid = $uuid;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('messenger'),
      $container->get('request_stack')->getCurrentRequest()->query,
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected final function prepareEntity() {
    parent::prepareEntity();
    if ($this->entity->isNew()) {
      $this->entity->set('target_entity_type', $this->targetEntityType);
      foreach ([
                 'target-user' => 'target_user',
                 'target-entity' => 'target_entity',
                 'target-entity-type' => 'target_entity_type',
                 'target-entity-bundle' => 'target_entity_bundle',
                 'page' => 'target_hidden_tab_page',
                 'target-hidden-tab-page' => 'target_hidden_tab_page',
               ] as $query => $property) {
        if ($this->params->has($query) && $this->params->get($query)) {
          $this->entity->set($property, $this->params->get($query));
        }
      }
    }
    $this->prepareEntity0();
  }

  /**
   * {@inheritdoc}
   */
  public final function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabCreditInterface $entity */
    $entity = $this->getEntity();
    $result = $entity->save();
    try {
      $l = $entity->toLink($this->t('View'));
      $ren = $l->toRenderable();
      $link = render($ren);
    }
    catch (\Throwable $error) {
      Utility::renderLog($error, 'hidden_tab_credit', 'view_link', $entity->id(), 'EntityFormBase::save');
      $link = Utility::WARNING;
    }

    $message_arguments = [
      '%label' => $this->entity->label(),
      '@type' => $entity->getEntityTypeId(),
    ];
    $logger_arguments = $message_arguments + [
        'link' => $link,
        'type' => $entity->getEntityTypeId(),
      ];

    if ($result == SAVED_NEW) {
      $this->messenger->addStatus($this->t(
        'New @type %label has been created.', $message_arguments));
      $this->logger('hidden_tab')
        ->notice('Created new @type: %label', $logger_arguments);
    }
    else {
      $this->messenger->addStatus($this->t(
        'The @type %label has been updated.', $message_arguments));
      $this->logger('hidden_tab')
        ->notice('Created new @type: %label.', $logger_arguments);
    }

    if (Utility::checkRedirect()) {
      $form_state->setRedirectUrl(Utility::checkRedirect());
    }
    else {
      $form_state->setRedirect('entity.' . $this->type . '.canonical',
        [$this->type => $entity->id()]);
    }
  }

  /**
   * Give a chance to sub-class to prepare entity.
   */
  protected abstract function prepareEntity0();

}
