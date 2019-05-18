<?php

namespace Drupal\shopify;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\file\Entity\File;
use Drupal\shopify\Entity\ShopifyProductVariant;

/**
 * Class ShopifyProductViewBuilder.
 */
class ShopifyProductViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    // Include our custom css.
    $build['#attached']['library'][] = 'shopify/shopify.product.css';

    if ($variant_id = \Drupal::request()->get('variant_id')) {
      $active_variant = ShopifyProductVariant::loadByVariantId($variant_id);
    }
    else {
      $active_variant = ShopifyProductVariant::load($entity->variants->get(0)->target_id);
    }

    if ($display->getComponent('dynamic_product_image')) {
      // Setup the image from the active variant.
      if ($active_variant instanceof ShopifyProductVariant) {
        if ($active_variant->image->target_id) {
          $file = File::load($active_variant->image->target_id);
        }
        elseif ($entity->image->target_id) {
          $file = File::load($entity->image->target_id);
        }
        if ($file instanceof File) {
          $build['dynamic_product_image'] = [
            '#theme' => 'image',
            '#uri' => $file->uri->value,
          ];
        }
      }
    }

    if ($display->getComponent('active_variant')) {

      // Display the active variant.
      if ($active_variant instanceof ShopifyProductVariant) {
        $build['active_variant'] = [
          '#prefix' => '<div class="product-active-variant variant-display variant-display--view-' . $view_mode . '">',
          'variant' => \Drupal::entityTypeManager()
            ->getViewBuilder('shopify_product_variant')
            ->view($active_variant, $view_mode),
          '#suffix' => '</div>',
        ];
      }

    }

    $form = $display->getComponent('add_to_cart_form');
    if ($form) {
      // Need to display the add to cart form.
      $build['add_to_cart_form']['variant_options'] = \Drupal::formBuilder()
        ->getForm('Drupal\shopify\Form\ShopifyVariantOptionsForm', $entity);

      $build['add_to_cart_form']['add_to_cart'] = \Drupal::formBuilder()
        ->getForm('Drupal\shopify\Form\ShopifyAddToCartForm', $entity);

      $build['add_to_cart_form']['#weight'] = $form['weight'];
    }
  }

}
