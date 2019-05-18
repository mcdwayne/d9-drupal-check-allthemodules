<?php

namespace Drupal\lunr;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Lunr search entities.
 *
 * @see \Drupal\lunr\Entity\LunrSearch
 */
class LunrSearchListBuilder extends ConfigEntityListBuilder {

  /**
   * The view entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager')->getStorage('view'),
      $container->get('module_handler')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityStorageInterface $view_storage
   *   The view entity storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityStorageInterface $view_storage, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type, $storage);
    $this->viewStorage = $view_storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['path'] = t('Path');
    $header['view'] = t('View');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    if ($entity instanceof LunrSearchInterface) {
      $url = Url::fromRoute('lunr_search.' . $entity->id());
      $row['path'] = new Link($url->toString(), $url);
      if ($this->moduleHandler->moduleExists('views_ui')) {
        $view = $this->viewStorage->load($entity->getViewId());
        $url = Url::fromRoute('entity.view.edit_display_form', [
          'view' => $view->id(),
          'display_id' => $entity->getViewDisplayId(),
        ]);
        $row['view'] = new Link($view->label() . ': ' . $entity->getViewDisplayId(), $url);
      }
      else {
        $row['view'] = $entity->getViewId() . ':' . $entity->getViewDisplayId();
      }
    }
    return $row + parent::buildRow($entity);
  }

}
