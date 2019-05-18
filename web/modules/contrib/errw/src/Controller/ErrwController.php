<?php

namespace Drupal\errw\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Tags;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Site\Settings;
use Drupal\errw\ErrwEntityAutocompleteMatcher;
use Drupal\system\Controller\EntityAutocompleteController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Like EntityAutocompleteController, but just that so it uses
 * ErrwEntityAutocompleteMatcher instead.
 */
class ErrwController extends EntityAutocompleteController {

  /**
   * Constructs a EntityAutocompleteController object.
   *
   * @param \Drupal\Core\Entity\EntityAutocompleteMatcher $matcher
   *   The autocomplete matcher for entity references.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value
   *   The key value factory.
   */
  public function __construct(ErrwEntityAutocompleteMatcher $matcher, KeyValueStoreInterface $key_value) {
    parent::__construct($matcher, $key_value);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('errw.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

}
