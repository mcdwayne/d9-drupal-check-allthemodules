<?php

namespace Drupal\autocomplete_field_match\Controller;

use Drupal\system\Controller\EntityAutocompleteController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class AutocompleteFieldMatchController extends EntityAutocompleteController {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.autocomplete_matcher'),
      $container->get('keyvalue')->get('autocomplete_field_match'),
      $container->get('keyvalue')->get('afm_operator_and_or'),
      $container->get('keyvalue')->get('afm_operator_where'),
      $container->get('keyvalue')->get('afm_operator_langcode')
    );
  }

}
