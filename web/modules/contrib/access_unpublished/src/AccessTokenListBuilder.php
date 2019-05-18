<?php

namespace Drupal\access_unpublished;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of access token entities.
 */
class AccessTokenListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The Access Token Manager.
   *
   * @var \Drupal\access_unpublished\AccessTokenManager
   */
  protected $accessTokenManager;

  /**
   * Name of the list builder.
   *
   * @var string
   */
  protected $handlerName = 'list_builder';

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\access_unpublished\AccessTokenManager $access_token_manager
   *   The access token manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, AccessTokenManager $access_token_manager) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
    $this->accessTokenManager = $access_token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('access_unpublished.access_token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Enable language column and filter if multiple languages are added.
    $header = [
      'expire_date' => $this->t('Expire date'),
      'host' => $this->t('Parent entity'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\access_unpublished\Entity\AccessToken $entity */

    $row['expire_date']['data'] = [
      '#plain_text' => $entity->get('expire')->value > 0 ? $this->dateFormatter->format($entity->get('expire')->value, 'short') : $this->t('Unlimited'),
    ];
    if ($entity->isExpired()) {
      $row['expire_date']['data'] = [
        '#markup' => 'Expired token',
        '#prefix' => '<div class="access-unpublished-expired">',
        '#suffix' => '</div>',
      ];
    }

    $row['host']['data'] = [
      '#type' => 'link',
      '#title' => $entity->getHost()->label(),
      '#url' => $entity->getHost()->toUrl(),
    ];

    $row['operations']['data'] = $this->buildOperations($entity);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = [
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
      '#attached' => [
        'library' => ['access_unpublished/drupal.access_unpublished.admin'],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $token) {
    /** @var \Drupal\access_unpublished\AccessTokenInterface $token */

    $operations = parent::getDefaultOperations($token);
    if ($token->access('delete') && $token->hasLinkTemplate('delete')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $this->ensureDestination($token->toUrl('delete', ['query' => ['handler' => $this->handlerName]])),
        'attributes' => [
          'class' => ['use-ajax'],
        ],
      ];
    }
    if ($token->access('renew') && $token->isExpired()) {
      $operations['renew'] = [
        'title' => t('Renew'),
        'url' => $token->toUrl('renew'),
        'weight' => 50,
        'attributes' => [
          'class' => ['use-ajax'],
        ],
      ];
    }
    else {
      $url = $this->accessTokenManager->getAccessTokenUrl($token, $token->getHost()->language());
      $operations['copy'] = [
        'title' => t('Copy'),
        'url' => Url::fromUserInput('#'),
        'attributes' => [
          'data-unpublished-access-url' => $url,
          'class' => ['clipboard-button'],
        ],
        'weight' => 50,
      ];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#id'] = Html::getUniqueId('access-token-list');
    $build['table']['#attributes']['data-drupal-selector'] = Html::getId('access-token-list');
    return $build;
  }

}
