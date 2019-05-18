<?php

namespace Drupal\erf_commerce\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Implementation of the VariationSelection Entity Reference Selection plugin.
 *
 * @EntityReferenceSelection(
 *   id = "erf_commerce_variation",
 *   label = @Translation("ERF Commerce: Product Variations for Product from URL"),
 *   group = "erf_commerce_variation",
 *   weight = 0
 * )
 */
class VariationSelection extends SelectionPluginBase implements ContainerFactoryPluginInterface {

  use SelectionTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['erf_commerce_variation']['help'] = [
      '#markup' => '<p>' . $this->t('Available variations will be loaded for a product detected from the URL, either via a commerce product parameter (e.g. /product/{commerce_product}) or from the source entity of a registration.') . '</p>',
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    return $this->getProductVariationsForRoute();
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
  }

  /**
   * Get available variations for a product from the current route.
   *
   * The product can be loaded from a commerce route parameter (e.g.
   * /product/{commerce_product}) or from the source entity of a registration,
   * if that source entity is a product.
   *
   * @param boolean $filter
   *   TRUE to filter the variations or FALSE to return them all.
   *
   * @return array A nested array of entities, the first level is keyed by the
   *   variation bundle (e.g. 'default'), which contains an array of entity
   *   labels keyed by the entity ID.
   */
  private function getProductVariationsForRoute($filter = TRUE) {
    $current_route = \Drupal::routeMatch();

    $product = FALSE;
    if ($product = $current_route->getParameter('commerce_product')) {
      // The $product entity has been loaded from a {commerce_product}
      // parameter.
    }
    elseif ($registration = $current_route->getParameter('registration')) {
      $source_entity = $registration->getSourceEntity();
      if ($source_entity instanceof ProductInterface) {
        $product = $source_entity;
      }
    }

    if (!$product) {
      return [];
    }

    $return = [];
    $variation_storage = $this->entityManager->getStorage('commerce_product_variation');

    // ProductVariationStorage::loadEnabled() is a bit better than
    // $product->getVariations() because it runs the
    // `ProductEvents::FILTER_VARIATIONS` event, which can be used to filter out
    // ineligable variations.
    $filtered_variations = $variation_storage->loadEnabled($product);

    foreach ($filtered_variations as $product_id => $variation) {
      $return[$variation->bundle()][$product_id] = $variation->label();
    }

    return $return;
  }

}
