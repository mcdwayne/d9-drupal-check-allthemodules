<?php

namespace Drupal\product_choice;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\product_choice\Entity\ProductChoiceTermInterface;
use Drupal\product_choice\Entity\ProductChoiceListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handler for product_choice.usage_service service.
 */
class ProductChoiceUsageService {

  /**
   * Entity Type Manager Service Object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Entity Field Manager Service Object.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Entity Type Bundle Info Service Object.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a ProductChoiceUsageService object.
   */
  public function __construct(EntityTypeManager $entityTypeManager,
    EntityFieldManager $entityFieldManager,
    EntityTypeBundleInfo $entityTypeBundleInfo) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class. The
   * factory should pass any needed dependencies into the constructor of this
   * class, but not the container itself. Every call to this method must return
   * a new instance of this class; that is, it may not implement a singleton.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Returns array of product ids using the given product choice term.
   *
   * @param Drupal\product_choice\Entity\ProductChoiceTermInterface $product_choice_term
   *   The product choice term.
   *
   * @return array
   *   IDs of commerce products currently using the product choice term.
   */
  public function getProducts(ProductChoiceTermInterface $product_choice_term) {

    $products = [];

    $map = $this->entityFieldManager->getFieldStorageDefinitions('commerce_product');
    $query = $this->entityTypeManager->getStorage('commerce_product')->getQuery();
    $group = $query->orConditionGroup();

    $do_execute_query = 0;
    // Variable $field_info is type FieldStorageDefinitionInterface.
    foreach ($map as $field_name => $field_info) {
      if (($field_info->getType() == 'entity_reference') &&
        ($field_info->getSetting('target_type') == 'product_choice_term')) {
        $group->condition($field_name, $product_choice_term->id());
        $do_execute_query = 1;
      }
    }
    if ($do_execute_query) {
      $query->condition($group);
      $query->sort('title');
      $products = $query->execute();
    }
    return $products;
  }

  /**
   * Returns array of product types using the given product choice list.
   *
   * @param Drupal\product_choice\Entity\ProductChoiceListInterface $product_choice_list
   *   The product choice list.
   *
   * @return array
   *   Commerce product types, fields currently using the product choice list.
   */
  public function getProductTypes(ProductChoiceListInterface $product_choice_list) {

    $types = [];

    $bundles = $this->entityTypeBundleInfo->getBundleInfo('commerce_product');
    foreach ($bundles as $bundle_name => $bundle_info) {
      $map = $this->entityFieldManager->getFieldDefinitions('commerce_product', $bundle_name);
      foreach ($map as $field_name => $field_info) {
        if (($field_info->getType() == 'entity_reference') &&
          ($field_info->getSetting('target_type') == 'product_choice_term')) {
          if (isset($field_info->getSetting('handler_settings')['target_bundles'][$product_choice_list->id()])) {
            $types[] = $bundle_info['label'] . ' (' . $field_name . ')';
          }
        }
      }
    }

    return $types;
  }

}
