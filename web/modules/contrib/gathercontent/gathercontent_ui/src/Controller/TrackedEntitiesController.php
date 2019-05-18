<?php

namespace Drupal\gathercontent_ui\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class TrackedEntitiesController.
 *
 * @package Drupal\gathercontent_ui\Controller
 */
class TrackedEntitiesController extends ControllerBase {

  /**
   * Query factory instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Session service.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Session $session,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config
  ) {
    $this->session = $session;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Return tracked entities. Entities are added during migration.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function listTrackedEntities() {
    $tracked = $this->session->get('gathercontent_tracked_entities', []);
    $this->session->remove('gathercontent_tracked_entities');
    $storages = [];
    $rows = [];

    foreach ($tracked as $gcID => $item) {
      if (!isset($storages[$item['entity_type']])) {
        $storages[$item['entity_type']] = $this->entityTypeManager->getStorage($item['entity_type']);
      }

      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $storages[$item['entity_type']]->load($item['id']);
      $gcStatus = '';

      if (!empty($item['status'])) {
        $gcStatus = [
          'data' => [
            'color' => [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#value' => ' ',
              '#attributes' => [
                'style' => 'width:20px; height: 20px; float: left; margin-right: 5px; background: ' . $item['status']->color,
              ],
            ],
            'label' => [
              '#plain_text' => $item['status']->name,
            ],
          ],
          'class' => ['gc-item', 'status-item'],
        ];
      }

      $status = '';
      $statusKey = $entity->getEntityType()->getKey('published');

      if (
        $statusKey
        && !$entity->get($statusKey)->isEmpty()
        && $entity->get($statusKey)->first()->getValue()['value']
      ) {
        $status = $this->t('Published');
      }

      $rows[] = [
        'gc_status' => $gcStatus,
        'status' => $status,
        'id' => $item['id'],
        'template_name' => $item['template_name'],
        'name' => $entity->label(),
        'drupal_url' => $entity->toLink(),
        'gc_url' => $this->getGcUrl($gcID, $entity->label()),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        'gc_status' => $this->t('GatherContent status'),
        'status' => $this->t('Status'),
        'id' => $this->t('ID'),
        'template_name' => $this->t('Template name'),
        'name' => $this->t('Name'),
        'drupal_url' => $this->t('Drupal link'),
        'gc_url' => $this->t('Gathercontent link'),
      ],
      '#empty' => $this->t('No content available.'),
      '#rows' => $rows,
    ];
  }

  /**
   * Returns the GC URL for the entity.
   */
  protected function getGcUrl(int $gcId, $label) {
    if (is_numeric($gcId)) {
      $base_url = 'https://'
        . $this->config
          ->get('gathercontent.settings')
          ->get('gathercontent_urlkey')
        . '.gathercontent.com/item/';

      $url = Url::fromUri($base_url . $gcId);

      return Link::fromTextAndUrl($label, $url)->toString();
    }
    else {
      return $this->t('Not available');
    }
  }

}
