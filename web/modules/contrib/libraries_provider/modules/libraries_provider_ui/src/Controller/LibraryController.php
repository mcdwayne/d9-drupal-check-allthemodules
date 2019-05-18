<?php

namespace Drupal\libraries_provider_ui\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Returns responses for libraries_provider_ui routes.
 */
class LibraryController extends ControllerBase {

  /**
   * Route title callback.
   *
   * @param \Drupal\Core\Entity\EntityInterface $library
   *   The library entity.
   *
   * @return array
   *   The library label as a render array.
   */
  public function libraryTitle(EntityInterface $library) {
    return ['#markup' => $library->label(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

}
