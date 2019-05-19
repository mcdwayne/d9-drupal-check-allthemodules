<?php

namespace Drupal\simple_megamenu;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Simple mega menu entities.
 *
 * @ingroup simple_megamenu
 */
class SimpleMegaMenuListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle info interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   The bundle info service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $bundleInfo) {
    parent::__construct($entity_type, $storage);
    $this->entityTypeManager = $entityTypeManager;
    $this->bundleInfo = $bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Simple mega menu ID');
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    $header['target_menu'] = $this->t('Target Menu');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\simple_megamenu\Entity\SimpleMegaMenu */
    $row['id'] = $entity->id();
    $row['name'] = Link::fromTextAndUrl(
      $entity->label(),
      new Url(
        'entity.simple_mega_menu.edit_form', [
          'simple_mega_menu' => $entity->id(),
        ]
      )
    );

    $bundle = $entity->bundle();
    /* @var \Drupal\simple_megamenu\Entity\SimpleMegaMenuTypeInterface  $simple_mega_menu_type */
    $simple_mega_menu_type = $this->entityTypeManager->getStorage('simple_mega_menu_type')->load($bundle);

    $row['type'] = $simple_mega_menu_type->label();

    $target_menus = $simple_mega_menu_type->getTargetMenu();
    $target_menus = array_filter($target_menus);
    $menu_labels = [];
    foreach ($target_menus as $menu_name) {
      $menu_labels[] = $this->entityTypeManager->getStorage('menu')->load($menu_name)->label();
    }
    $labels = implode(', ', $menu_labels);
    $row['target_menu'] = new FormattableMarkup('@labels', [
      '@labels' => $labels,
    ]);

    return $row + parent::buildRow($entity);
  }

}
