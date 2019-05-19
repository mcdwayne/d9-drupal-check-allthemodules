<?php

namespace Drupal\trash\Controller;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\trash\TrashModerationState;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for trash module routes.
 */
class TrashController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The Content Moderation moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Constructs an TrashController object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The Content Moderation moderation information service.
   */
  public function __construct(Connection $connection, ModerationInformationInterface $moderation_information) {
    $this->connection = $connection;
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * Builds a listing of content entities.
   *
   * @return array
   *   A render array.
   */
  public function entityList() {
    $entities = $this->loadTrashedEntities();
    $rows = [];

    foreach ($entities as $entity) {
      if ($entity instanceof ContentEntityInterface) {
        $route_parameters = [
          'entity_type_id' => $entity->getEntityTypeId(),
          'entity_id' => $entity->id(),
        ];
        $links = [
          'restore' => [
            'title' => $this->t('Restore'),
            'url' => Url::fromRoute('trash.restore_form', $route_parameters),
          ],
          'purge' => [
            'title' => $this->t('Purge'),
            'url' => Url::fromRoute('trash.purge_form', $route_parameters),
          ],
        ];
        $id = $entity->id();
        $rows[$id] = [];
        $rows[$id]['type'] = $entity->getEntityType()->getLabel();
        $rows[$id]['label'] = [
          'data' => [
            '#type' => 'link',
            '#title' => $entity->label(),
            '#access' => $entity->access('view'),
            '#url' => $entity->toUrl(),
          ],
        ];

        $rows[$id]['operations'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ];
      }
    }

    return [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $rows,
      '#empty' => $this->t('The trash bin is empty.'),
    ];
  }

  /**
   * Loads one or more trashed entities of a given type.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects indexed by their IDs. Returns an empty array
   *   if no matching entities are found.
   */
  protected function loadTrashedEntities() {
    $content_moderation_state = $this->entityTypeManager()
      ->getDefinition('content_moderation_state');
    $data_table = $content_moderation_state->getDataTable();
    $query = $this->connection
      ->select($data_table, 'data_table')
      ->fields('data_table', ['content_entity_type_id', 'content_entity_id'])
      ->condition('moderation_state', TrashModerationState::TRASHED);

    $entity_data = $query->execute()
      ->fetchAllAssoc('content_entity_id', \PDO::FETCH_ASSOC);
    $entities = [];
    foreach ($entity_data as $entity_id => $item) {
      $entities[] = $this->entityTypeManager()
        ->getStorage($item['content_entity_type_id'])
        ->load($entity_id);
    }
    return $entities;
  }

  /**
   * Returns the table header render array for entity types.
   *
   * @return array
   *   A render array.
   */
  protected function buildHeader() {
    $header = [];
    $header['type'] = [
      'data' => $this->t('Type'),
    ];
    $header['label'] = [
      'data' => $this->t('Name'),
    ];
    $header['operations'] = $this->t('Operations');
    return $header;
  }

}
