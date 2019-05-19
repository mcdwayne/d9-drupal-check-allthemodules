<?php

namespace Drupal\webform_composite\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform_composite\WebformCompositeInterface;

/**
 * Provides route responses for webform options.
 */
class WebformCompositeController extends ControllerBase {

  /**
   * Webform composite edit route title callback.
   *
   * @param \Drupal\webform_composite\WebformCompositeInterface $webform_composite
   *   The webform composite.
   *
   * @return string
   *   The webform composite label as a render array.
   */
  public function editTitle(WebformCompositeInterface $webform_composite) {
    return 'Edit ' . $webform_composite->label();
  }

  /**
   * Webform composite source route title callback.
   *
   * @param \Drupal\webform_composite\WebformCompositeInterface $webform_composite
   *   The webform composite.
   *
   * @return string
   *   The webform composite label as a render array.
   */
  public function sourceTitle(WebformCompositeInterface $webform_composite) {
    return 'Edit ' . $webform_composite->label() . ' Source';
  }

}
