<?php

namespace Drupal\cludo;

use Drupal\Core\Url;

/**
 * Attaches the scripts and data required for MyCludo CMS Overlay.
 */
class CludoLibrary {

  /**
   * Loads the Cludo CMS plugin script to the HTML.
   *
   * @param array $build
   *   Hierarchical associative array containing data to be rendered.
   */
  public static function attachCmsOverlayScript(array &$build) {
    $build['#attached']['library'][] = 'cludo/cludo-overlay';

    $build['#attached']['drupalSettings']['cludo'] = [
      'version' => \Drupal::VERSION,
    ];
  }

  /**
   * Adds the data for the current page.
   *
   * @param array $build
   *   Hierarchical associative array containing data to be rendered.
   * @param \Drupal\Core\Url $url
   *   URL object for the current entity.
   */
  public static function attachJsData(array &$build, Url $url) {
    $build['#attached']['drupalSettings']['cludo'] = [
      'pageUrl' => self::getAbsolutePath($url),
    ];
  }

  /**
   * Get absolute path by the internal URL.
   *
   * @param \Drupal\Core\Url $url
   *   Internal URL.
   *
   * @return string
   *   URL aliased, or the front URL if the content is the frontpage.
   */
  protected static function getAbsolutePath(Url $url) {
    $frontpage = \Drupal::configFactory()->get('system.site')
      ->get('page.front');

    if ($url->getRouteName() && '/' . $url->getInternalPath() === $frontpage) {
      return \Drupal::urlGenerator()->generateFromRoute('<front>', [], ['absolute' => TRUE]);
    }

    return $url->setAbsolute(TRUE)->toString();
  }

}
