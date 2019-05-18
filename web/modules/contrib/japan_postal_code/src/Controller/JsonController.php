<?php

namespace Drupal\japan_postal_code\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for japan postal code.
 */
class JsonController extends ControllerBase {

  const PARAM_KEY = 'japan_postal_code_postal_code';

  /**
   * Returns all addresses which match the specified postal code.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A response object.
   */
  public function fetchAll(Request $request, RouteMatchInterface $route_match) {
    $postal_code = $route_match->getParameters()->get(self::PARAM_KEY);
    $matched_addresses = japan_postal_code_get_addresses_by_postal_code($postal_code);

    return JsonResponse::create(['data' => $matched_addresses]);
  }

  /**
   * Returns one address which matches the specified postal code.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A response object.
   */
  public function fetchOne(Request $request, RouteMatchInterface $route_match) {
    $postal_code = $route_match->getParameters()->get(self::PARAM_KEY);
    $matched_address = japan_postal_code_get_address_by_postal_code($postal_code);

    return JsonResponse::create(['data' => $matched_address]);
  }

}
