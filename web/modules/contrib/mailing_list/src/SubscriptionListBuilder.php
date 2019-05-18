<?php

namespace Drupal\mailing_list;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a class to build a listing of subscriptions.
 *
 * @see \Drupal\mailing_list\Entity\Subscription
 */
class SubscriptionListBuilder extends EntityListBuilder {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Contruct a new SubscriptionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The action storage.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, AccountInterface $current_user, FormBuilderInterface $form_builder, RequestStack $request_stack) {
    parent::__construct($entity_type, $storage);
    $this->currentUser = $current_user;
    $this->formBuilder = $form_builder;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_user'),
      $container->get('form_builder'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'title' => [
        'data' => $this->t('Title'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'list' => $this->t('Mailing list'),
      'email' => $this->t('Email'),
    ];

    if ($this->currentUser->hasPermission('administer mailing list subscriptions')) {
      $header += [
        'author' => [
          'data' => $this->t('Author'),
          'class' => [RESPONSIVE_PRIORITY_LOW],
        ],
        'status' => $this->t('Status'),
        'changed' => [
          'data' => $this->t('Updated'),
          'class' => [RESPONSIVE_PRIORITY_LOW],
        ],
      ];
    }

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    /** @var \Drupal\mailing_list\SubscriptionInterface $entity */
    if (!$entity->access('view')) {
      return $row;
    }

    $uri = $entity->urlInfo();
    $options = $uri->getOptions();
    $langcode = $entity->language()->getId();
    $languages = \Drupal::languageManager()->getLanguages();
    $options += ($langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? ['language' => $languages[$langcode]] : []);
    $uri->setOptions($options);

    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $uri,
    ];
    $row['list'] = $entity->getList()->label();
    $row['email']['data'] = ['#markup' => $entity->getEmail()];

    if ($this->currentUser->hasPermission('administer mailing list subscriptions')) {
      $row['author']['data'] = [
        '#theme' => 'username',
        '#account' => $entity->getOwner(),
      ];
      $row['status'] = $entity->isActive() ? $this->t('Active') : $this->t('Inactive');
      $row['changed'] = \Drupal::service('date.formatter')->format($entity->getChangedTime(), 'short');
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    // Anonymous users with no session started has no subscription access. We
    // return the anonymous subscription access form.
    if (!count($build['table']['#rows']) && $this->currentUser->isAnonymous()) {
      return $this->formBuilder->getForm('\Drupal\mailing_list\Form\AnonymousSubscriptionAccessForm');
    }

    $build['table']['#empty'] = $this->t('No subscriptions found.');

    // Prevent search engines from indexing this subscriptions list and pages.
    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'robots',
          'content' => 'noindex,nofollow',
        ],
      ],
      'mailing_list',
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));

    // Filter by email address for anonymous users when come from a hashed
    // access link.
    if ($this->currentUser->isAnonymous()
      && ($sid = $this->requestStack->getMasterRequest()->get('mailing_list_subscription'))
      && $subscription = $this->getStorage()->load($sid)) {
      $query->condition('email', $subscription->getEmail());
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
