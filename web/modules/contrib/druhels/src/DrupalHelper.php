<?php

namespace Drupal\druhels;

use Drupal\Component\Utility\Timer;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

class DrupalHelper {

  /**
   * Return current page title.
   *
   * @return string
   */
  public static function getCurrentPageTitle() {
    $request = \Drupal::request();
    $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    $page_title = \Drupal::service('title_resolver')->getTitle($request, $route);

    if (is_array($page_title)) {
      return render($page_title);
    }
    return $page_title;
  }

  /**
   * Set page title.
   */
  public static function setCurrentPageTitle($title) {
    \Drupal::request()->attributes->get(RouteObjectInterface::ROUTE_OBJECT)->setDefault('_title', $title);
  }

  /**
   * Return current system path without trailing slashes.
   *
   * @return string
   */
  public static function getCurrentSystemPath() {
    $current_system_path = \Drupal::service('path.current')->getPath();
    return ltrim($current_system_path, '/');
  }

  /**
   * Return current path alias without trailing slashes.
   *
   * @return string
   */
  public static function getCurrentPathAlias() {
    $current_system_path = self::getCurrentSystemPath();
    $current_path_alias = \Drupal::service('path.alias_manager')->getAliasByPath('/' . $current_system_path);
    return ltrim($current_path_alias, '/');
  }

  /**
   * Start timer.
   */
  public static function timerStart($name = 'druhels') {
    Timer::start($name);
  }

  /**
   * Stop timer.
   */
  public static function timerStop($name = 'druhels', $show_result = TRUE) {
    Timer::stop($name);

    if ($show_result) {
      $duration = Timer::read($name);
      \Drupal::messenger()->addMessage($duration . ' ms / ' . ($duration / 1000) . ' sec');
    }
  }

}
