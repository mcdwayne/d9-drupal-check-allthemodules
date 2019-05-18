<?php

namespace Drupal\group_taxonomy\Controller;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\group_taxonomy\GroupTermAutocompleteMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\system\Controller\EntityAutocompleteController;

class GroupTermAutocompleteController extends EntityAutocompleteController {

  /**
   * The autocomplete matcher for entity references.
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(GroupTermAutocompleteMatcher $matcher, KeyValueStoreInterface $key_value) {
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group_taxonomy.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

}
