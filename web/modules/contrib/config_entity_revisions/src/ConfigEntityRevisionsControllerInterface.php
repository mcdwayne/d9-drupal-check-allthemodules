<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\diff\DiffEntityComparison;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\Serializer\Serializer;
use Drupal\Core\Database\Connection;

/**
 * ConfigEntityRevisionsController interface.
 */
interface ConfigEntityRevisionsControllerInterface extends ContainerInjectionInterface {

  /**
   * Constructs a ConfigEntityRevisionsController object.
   *
   * @param ContainerInterface $container
   *   The container interface object.
   * @param DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param RendererInterface $renderer
   *   The renderer service.
   * @param ImmutableConfig $config
   *   The configuration service.
   * @param DiffEntityComparison $entity_comparison
   *   The diff entity comparison service.
   * @param EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param AccountProxyInterface $current_user
   *   The current user.
   * @param Serializer $serialiser
   *   The serialiser service.
   * @param Connection $connection
   *   The database connection.
   */
  public function __construct(ContainerInterface $container, DateFormatterInterface $date_formatter, RendererInterface $renderer, ImmutableConfig $config, DiffEntityComparison $entity_comparison, EntityTypeManager $entity_type_manager, AccountProxyInterface $current_user, Serializer $serialiser, Connection $connection);

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container);

  /**
   * Create an initial revision record.
   *
   * @param ConfigEntityRevisionsInterface $config_entity
   *   The configuration entity.
   *
   * @return ContentEntityInterface
   *   The content entity created.
   */
  public function createInitialRevision(ConfigEntityRevisionsInterface $config_entity);

  /**
   * Create revision when a new config entity version is saved.
   *
   * @param ConfigEntityRevisionsInterface $config_entity
   *   The configuration entity.
   */
  public function createUpdateRevision(ConfigEntityRevisionsInterface $config_entity);

  /**
   * Delete revisions when a config entity is deleted.
   *
   * @param ConfigEntityRevisionsInterface $config_entity
   *   The configuration entity being deleted.
   */
  public function deleteRevisions(ConfigEntityRevisionsInterface $config_entity);

  /**
   * Default implementation providing a title for a rendered revision.
   *
   * @param ConfigEntityInterface $config_entity
   *   The configuration entity being displayed.
   *
   * @return string
   *   The resulting title.
   */
  public function revisionShowTitle(ConfigEntityInterface $config_entity);

}
