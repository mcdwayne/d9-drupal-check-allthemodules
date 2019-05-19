<?php

namespace Drupal\white_label_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\Session\AccountProxy;

/**
 * Defines a class to build a listing of while entities.
 *
 * @ingroup while
 */
class WhileEntityListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter, QueryFactory $query_factory, AccessManager $access_manager, AccountProxy $current_user) {
    parent::__construct($entity_type, $storage);
    $this->configFactory = $config_factory;
    $this->dateFormatter = $date_formatter;
    $this->queryFactory = $query_factory;
    $this->accessManager = $access_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    // Instantiates this class.
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('entity.query'),
      $container->get('access_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->queryFactory->get('while_entity');
    $entity_query->pager(50);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ids = $entity_query->execute();
    return $this->storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['created'] = [
      'field' => 'created',
      'specifier' => 'created',
      'sort' => 'desc',
      'data' => $this->t('Created'),
    ];
    $header['name'] = $this->t('Title');
    $header['author'] = $this->t('Author');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\white_label_entity\Entity\WhileEntity */
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'short');

    $access_manager = $this->accessManager;
    if ($access_manager->checkNamedRoute('entity.while_entity.canonical', ['while_entity' => $entity->id()], $this->currentUser)) {
      $row['name'] = $this->l(
        $entity->label(),
        new Url(
          'entity.while_entity.canonical', [
            'while_entity' => $entity->id(),
          ]
        )
      );
    }
    else {
      $row['name'] = $entity->label();
    }

    $row['author']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];

    return $row + parent::buildRow($entity);
  }

}
