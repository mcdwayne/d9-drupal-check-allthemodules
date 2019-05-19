<?php

namespace Drupal\syncart\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class FavoriteController.
 */
class FavoriteController extends ControllerBase {

  /**
   * Constructs a new FavoriteController object.
   */
  public function __construct() {
    $this->renderer = \Drupal::service('renderer');
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->productStorage = $this->entityTypeManager->getStorage('commerce_product');
    $this->viewBuilder = $this->entityTypeManager->getViewBuilder('commerce_product');
    $this->products = [];
    $this->output = [
      'products' => [],
      'information' => [],
    ];
  }

  /**
   * Favorites page.
   */
  public function favoritesPage() {
    $this->getFavoritesProducts();
    $this->getFavoritesInformation();
    return [
      '#theme' => 'syncart-favorites',
      '#data' => $this->output,
    ];
  }

  /**
   * Get favorites products.
   */
  private function getFavoritesProducts() {
    $favorites = $this->getFavoritesIds();
    if ($favorites) {
      $this->products = $this->productStorage->loadMultiple($favorites);
      $this->output['products'] = $this->makeProductsTeaser();
    }
  }

  /**
   * Get favorites information.
   */
  private function getFavoritesInformation() {
    $this->output['information'] = [
      'count' => count($this->output['products']),
      'price' => 0,
      'ids' => [],
    ];
    $this->getVariationsInformation();
  }

  /**
   * Get product ids.
   */
  private function getFavoritesIds() {
    $favorites = [];

    if (isset($_COOKIE['favorites'])) {
      $cookieFavorites = $_COOKIE['favorites'];
      $favorites = Json::decode($cookieFavorites);
    }

    if (is_array($favorites)) {
      $favorites = array_keys($favorites);
    }

    return $favorites;
  }


  /**
   * Get total price.
   */
  private function getVariationsInformation() {
    $price = 0;
    $ids = [];

    foreach ($this->products as $product) {
      $field = $product->variations;
      if ($field->isEmpty()) {
        continue;
      }
      $variation = $field->first()->entity;
      if (!is_object($variation)) {
        continue;
      }
      $price += (float) $variation->getPrice()->getNumber();
      $ids[] = $variation->id();
    }
    $this->output['information']['price'] = number_format($price, 0, ' ', ' ');
    $this->output['information']['ids'] = Json::encode($ids);
  }

  /**
   * Make products teaser.
   */
  private function makeProductsTeaser() {
    $output = [];
    foreach ($this->products as $product) {
      $build = $this->viewBuilder->view($product, 'teaser');
      $output[] = $this->renderer->render($build);
    }
    return $output;
  }

}
