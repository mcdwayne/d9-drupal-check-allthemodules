<?php

namespace Drupal\courier_ui\Controller;

use Drupal\Core\Entity\EntityListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;

/**
 * Defines a form to list template collections.
 */
class ListTemplateCollection extends EntityListBuilder {

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('renderer')
    );
  }

  /**
   * Constructs an instance.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    RendererInterface $renderer
  ) {
    parent::__construct($entity_type, $storage);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = [
      'edit' => [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $this->ensureDestination($entity->toUrl('edit')),
      ],
      'templates' => [
        'title' => $this->t('Templates'),
        'weight' => 50,
        'url' => $this->ensureDestination($entity->toUrl('templates')),
      ],
      'delete' => [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $this->ensureDestination($entity->toUrl('delete')),
      ],
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'title' => $this->t('Title'),
      'description' => $this->t('Description'),
      'owner' => $this->t('Owner'),
      'referenceable_bundles' => $this->t('Token bundles'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $bundles_renderable = $entity->referenceable_bundles->view();
    $bundles_renderable['#theme'] = 'item_list';
    $row = [
      'title' => $entity->label(),
      'description' => $entity->description->value,
      'owner' => $entity->owner->value,
      'referenceable_bundles' => $this->renderer->renderRoot($bundles_renderable),
    ];
    return $row + parent::buildRow($entity);
  }

}
