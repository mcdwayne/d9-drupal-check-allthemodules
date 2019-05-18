<?php

namespace Drupal\aws\Controller;

use Drupal\Core\Entity\Controller\EntityListController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller to list AWS profiles.
 */
class ProfileListController extends EntityListController {

  /**
   * Shows the profile administration page.
   *
   * @param string|null $theme
   *   Theme key of block list.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing($theme = NULL, Request $request = NULL) {
    return $this->entityManager()->getListBuilder('aws_profile')->render($theme, $request);
  }

}
