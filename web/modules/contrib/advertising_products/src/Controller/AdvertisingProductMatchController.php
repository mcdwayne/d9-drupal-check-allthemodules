<?php

namespace Drupal\advertising_products\Controller;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\advertising_products\AdvertisingProductMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;


class AdvertisingProductMatchController extends \Drupal\system\Controller\EntityAutocompleteController {

  /**
   * The autocomplete matcher for advertising_product references.
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(AdvertisingProductMatcher $matcher, KeyValueStoreInterface $key_value) {
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('advertising_products.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

}
