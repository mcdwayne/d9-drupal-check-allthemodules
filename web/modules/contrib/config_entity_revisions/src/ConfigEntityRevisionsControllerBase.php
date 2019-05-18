<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\diff\DiffEntityComparison;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\Serializer\Serializer;
use Drupal\Core\Database\Connection;

/**
 * Controller to make library functions available to various consumers.
 */
abstract class ConfigEntityRevisionsControllerBase extends ControllerBase implements ConfigEntityRevisionsControllerInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Wrapper object for simple configuration from diff.settings.yml.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Wrapper object for simple configuration from diff.settings.yml.
   *
   * @var \Drupal\diff\DiffEntityComparison;
   */
  protected $entityComparison;

  /**
   * Serialiser service.
   *
   * @var Serializer;
   */
  protected $serialiser;

  /**
   * Container instance.
   *
   * @var ContainerInterface
   */
  protected $container;

  /**
   * Date formatter service.
   *
   * @var DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $connection;

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
  public function __construct(
    ContainerInterface $container,
    DateFormatterInterface $date_formatter,
    RendererInterface $renderer,
    ImmutableConfig $config,
    DiffEntityComparison $entity_comparison,
    EntityTypeManager $entity_type_manager,
    AccountProxyInterface $current_user,
    Serializer $serialiser,
    Connection $connection
  ) {
    $this->container = $container;
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->config = $this->config('diff.settings');
    $this->entityComparison = $entity_comparison;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->serialiser = $serialiser;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container,
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('config.factory')->get('diff.settings'),
      $container->get('diff.entity_comparison'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('serializer'),
      $container->get('database')
    );
  }

  /**
   * Create an initial revision record.
   *
   * @param ConfigEntityRevisionsInterface $config_entity
   *   The configuration entity.
   *
   * @return ContentEntityInterface|NULL
   *   The content entity created.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createInitialRevision(ConfigEntityRevisionsInterface $config_entity) {

    $contentID = $config_entity->getContentEntityID();

    // Already created.
    if ($contentID) {
      return NULL;
    }

    /**
     * Make a content revisions entity using either the previous version of
     * the config entity or (failing that) the current version.
     * We're doing this here rather than in the update hook because we want
     * to save the reference to the entity config entity version that is being
     * saved now.
     */

    /* @var $originalEntity ConfigEntityInterface */
    $originalEntity = $config_entity->configEntityStorage()
      ->load($config_entity->id());
    $source = $originalEntity ? $originalEntity : $config_entity;

    $bundle_type = $config_entity->getEntityTypeId() . "_revisions";

    /* @var $contentEntity ContentEntityInterface */
    $contentEntity = $config_entity->contentEntityStorage()->create([
      'form' => $source->get('uuid'),
      'configuration' => $this->serialiser->serialize($source, 'json'),
      'revision_uid' => $this->container->get('current_user')->id(),
      'revision_creation_time' => $this->container->get('datetime.time')
        ->getRequestTime(),
      'revision_log_message' => 'Original revision.',
      'moderation_state' => 'draft',
      'type' => $bundle_type,
    ]);

    $contentEntity->save();
    $contentID = $contentEntity->id();

    $config_entity->setContentEntityID($contentID);
    $config_entity->save();

    return $contentEntity;
  }

  /**
   * Create revision when a new config entity version is saved.
   *
   * @param ConfigEntityRevisionsInterface $config_entity
   *   The configuration entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createUpdateRevision(ConfigEntityRevisionsInterface $config_entity) {

    /* @var $revisionsEntity \Drupal\config_entity_revisions\ConfigEntityRevisionsEntityInterface */
    $revisionsEntity = NULL;
    $previous_state = FALSE;

    if (!empty($config_entity->getRevisionId())) {
      $revisionsEntity = $config_entity->contentEntityStorage()
        ->loadRevision($config_entity->getRevisionID());
      $previous_state = $revisionsEntity->moderation_state->value;
    }
    else {
      $contentID = $config_entity->getContentEntityID();
      if (is_null($contentID)) {
        // No related content entity yet.
        return;
      }
      $revisionsEntity = $config_entity->contentEntityStorage()
        ->load($contentID);
    }

    $revisionsEntity->set('configuration', $this->serialiser->serialize($config_entity, 'json'));
    $revisionsEntity->setRevisionUserId($this->currentUser->id());
    $revisionsEntity->setRevisionCreationTime($this->container->get('datetime.time')
      ->getRequestTime());

    $new_message = $config_entity->get('revision_log_message')[0]['value'];
    $new_revision = $config_entity->get('revision');
    $moderation_state = $config_entity->get('moderation_state')[0]['value'];
    $published = NULL;

    if (!is_null($moderation_state)) {
      $published = ($moderation_state == 'published');
    }

    if (is_null($moderation_state) && is_null($new_revision)) {
      $new_revision = FALSE;
    }

    if (!is_null($new_message)) {
      $revisionsEntity->setRevisionLogMessage($config_entity->get('revision_log_message')[0]['value']);
    }

    $revisionsEntity->setNewRevision($new_revision);

    if (!is_null($moderation_state)) {
      $revisionsEntity->moderation_state = $moderation_state;
    }

    if (!is_null($published)) {
      if ($published) {
        $revisionsEntity->setPublished();
      }
      else {
        $revisionsEntity->setUnpublished();
      }

      $revisionsEntity->isDefaultRevision($published);
    }

    $revisionsEntity->save();

    if (($previous_state == 'published') !== $published) {
      // Modify another revision to be published and default if possible.
      $this->resetDefaultRevision($revisionsEntity);
    }

  }

  /**
   * Make default the most recently published revision or the most recent
   * revision.
   *
   * This is needed because content_moderation has a concept of a default
   * revision, which this module doesn't really care about, but which will
   * cause problems if we attempt to delete a revision that's marked as the
   * default.
   *
   * @param ContentEntityInterface $content_entity
   *   The content (revisions) entity.
   */
  public function resetDefaultRevision(ContentEntityInterface $content_entity) {
    $content_entity_id = $content_entity->id();

    $revisions = $this->connection
      ->select("config_entity_revisions_revision", 'c')
      ->fields('c', ['revision', 'revision_default', 'published'])
      ->condition('id', $content_entity_id)
      ->orderBy('revision', 'DESC')
      ->execute()
      ->fetchAllAssoc('revision');

    $first_published = NULL;
    $first_revision = NULL;
    $remove_default = [];

    foreach ($revisions as $revision) {
      if (!$first_revision) {
        $first_revision = $revision;
      }

      if ($revision->published && !$first_published) {
        $first_published = $revision;
      }

      if ($revision->revision_default) {
        $remove_default[$revision->revision] = 1;
      }
    }

    $default_revision = $first_published ?: $first_revision;

    if ($default_revision) {
      unset($remove_default[$default_revision->revision]);
    }

    if (!empty($remove_default)) {
      $this->connection->update("config_entity_revisions_revision")
        ->condition('revision', array_keys($remove_default), 'IN')
        ->fields(['revision_default' => 0])
        ->execute();
    }

    if ($default_revision) {
      if (!$default_revision->revision_default) {
        $this->connection->update("config_entity_revisions_revision")
          ->condition('revision', $default_revision->revision)
          ->fields(['revision_default' => 1])
          ->execute();
      }

      $this->connection->update("config_entity_revisions")
        ->condition('id', $content_entity_id)
        ->fields(['revision' => $default_revision->revision])
        ->execute();
    }

  }

  /**
   * Get a list of revision IDs for a content entity.
   */
  public function getRevisionIds($content_entity_id) {
    $revisions = $this->connection->select("config_entity_revisions_revision", 'c')
      ->fields('c', ['revision'])
      ->condition('id', $content_entity_id)
      ->execute()
      ->fetchCol();
    return $revisions;
  }

  /**
   * Delete a single revision.
   *
   * @param ContentEntityInterface $revision
   *   The revision to be deleted.
   */
  public function deleteRevision($revision) {
    $was_default = $revision->isDefaultRevision();
    $revisions = $this->getRevisionIds($revision->id());

    if ($was_default) {
      // Change the default to the next newer (if we're deleting the default,
      // there must be no published revisions so it doesn't matter which we
      // choose. Ensure revision_default isn't set on our revision in
      // config_entity_revisions_revision - $was_default can return FALSE
      // even when that value is 1, and that will cause the content moderation
      // module (which does look at that field) to throw an exception.
      $this->connection->update("config_entity_revisions_revision")
        ->condition('id', $revision->id())
        ->fields(['revision_default' => 0])
        ->execute();
      $revision_to_use = ($revisions[0] == $this->revision->getRevisionId()) ?
        $revisions[1] : $revisions[0];
      $new_default = $this->configEntityRevisionsStorage->loadRevision($revision_to_use);
      $new_default->enforceIsNew(FALSE);
      $new_default->isDefaultRevision(TRUE);
      $new_default->save();
    }

    $this->entityTypeManager
      ->getStorage('config_entity_revisions')
      ->deleteRevision($revision->getRevisionId());
  }

  /**
   * Delete revisions when a config entity is deleted.
   *
   * @param ConfigEntityRevisionsInterface $config_entity
   *   The configuration entity being deleted.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteRevisions(ConfigEntityRevisionsInterface $config_entity) {

    $contentEntity = $config_entity->getContentEntity();

    if ($contentEntity) {
      $config_entity->contentEntityStorage()->delete([$contentEntity]);
    }
  }

  /**
   * Load a particular revision of a config entity.
   *
   * @param int $revision
   *   The revision ID to load.
   * @param mixed $entity
   *   The entity type to load.
   *
   * @return mixed
   *   The loaded revision or NULL.
   */
  public function loadConfigEntityRevision($revision = NULL, $entity = '') {
    $config_entity_name = $this->config_entity_name();

    if (!$entity) {
      $match = \Drupal::service('router')->matchRequest(\Drupal::request());
      $entity = $match[$config_entity_name];
    }

    if (is_string($entity)) {
      $entity = $this->entityTypeManager->getStorage($config_entity_name)
        ->load($entity);
    }

    if ($revision) {
      $revisionsEntity = $this->entityTypeManager->getStorage('config_entity_revisions')
        ->loadRevision($revision);

      $entity = \Drupal::getContainer()->get('serializer')->deserialize(
        $revisionsEntity->get('configuration')->value,
        get_class($entity),
        'json');

      // The result of serialising and then deserialising is not an exact
      // copy of the original. This causes problems downstream if we don't fix
      // a few attributes here.
      $entity->set('settingsOriginal', $entity->get('settings'));
      $entity->set('enforceIsNew', FALSE);

      // Record the revision ID in the config entity so we can quickly and
      // easily access the revision record if needed (eg for edit form revision
      // message).
      $entity->updateLoadedRevisionId($revisionsEntity->getRevisionId());
    }

    return $entity;
  }

}
