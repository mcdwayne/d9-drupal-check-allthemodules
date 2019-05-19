<?php

namespace Drupal\uc_order;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder for ordered products.
 */
class OrderProductViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildContent($entities, $displays, $view_mode, $langcode);

    foreach ($entities as $product) {
      $product->content['qty'] = [
        '#theme' => 'uc_qty',
        '#qty' => $product->qty->value,
        '#cell_attributes' => ['class' => ['qty']],
      ];
      $title = $product->nid->entity->access('view') ? $product->nid->entity->toLink()->toString() : $product->title->value;
      $product->content['product'] = [
        '#markup' => $title . uc_product_get_description($product),
        '#cell_attributes' => ['class' => ['product']],
      ];
      $product->content['model'] = [
        '#markup' => $product->model->value,
        '#cell_attributes' => ['class' => ['sku']],
      ];
      $account = \Drupal::currentUser();
      if ($account->hasPermission('administer products')) {
        $product->content['cost'] = [
          '#theme' => 'uc_price',
          '#price' => $product->cost->value,
          '#cell_attributes' => ['class' => ['cost']],
        ];
      }
      $product->content['price'] = [
        '#theme' => 'uc_price',
        '#price' => $product->price->value,
        '#suffixes' => [],
        '#cell_attributes' => ['class' => ['price']],
      ];
      $product->content['total'] = [
        '#theme' => 'uc_price',
        '#price' => $product->price->value * $product->qty->value,
        '#suffixes' => [],
        '#cell_attributes' => ['class' => ['total']],
      ];
    }
  }

}
