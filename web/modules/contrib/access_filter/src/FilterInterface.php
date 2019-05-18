<?php

namespace Drupal\access_filter;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an interface defining a Example entity.
 */
interface FilterInterface extends ConfigEntityInterface {

  /**
   * Parses YAML serialized properties.
   */
  public function parse();

  /**
   * Checks the current access is allowed using the filter.
   *
   * @param Request $request
   *   A request instance.
   *
   * @return bool
   *   Boolean TRUE if allowed, FALSE otherwise.
   */
  public function isAllowed(Request $request);

}
