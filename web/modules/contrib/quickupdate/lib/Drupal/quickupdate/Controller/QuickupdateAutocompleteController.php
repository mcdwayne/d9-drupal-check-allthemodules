<?php

namespace Drupal\quickupdate\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

class QuickupdateAutocompleteController implements ContainerInjectionInterface {

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quickupdate.autocomplete')
    );
  }

  public function autocompleteSearchProjects(Request $request) {
    $matches = quickupdate_autocomplete_search_projects($request->query->get('q'));
    return new JsonResponse($matches);
  }
}
