<?php
/**
 * Created by PhpStorm.
 * User: mkalkbrenner
 * Date: 03.10.14
 * Time: 11:24
 */

namespace Drupal\themekey\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\themekey\EngineInterface;

class ThemeKeyNegotiator implements ThemeNegotiatorInterface {

  /**
   * @var
   */
  protected $themeKeyEngine;

  /**
   * Gets the ThemeKey Engine service.
   *
   * @return \Drupal\themekey\EngineInterface
   *   The string translation service.
   */
  protected function getThemeKeyEngine() {
    if (!$this->themeKeyEngine) {
      $this->themeKeyEngine = \Drupal::service('themekey.engine');
    }

    return $this->themeKeyEngine;
  }

  /**
   * Sets the ThemeKey Engine service to use.
   *
   * @param \Drupal\themekey\EngineInterface $themeKeyEngine
   *   The string translation service.
   *
   * @return $this
   */
  public function setThemeKeyEngine(EngineInterface $themeKeyEngine) {
    $this->themeKeyEngine = $themeKeyEngine;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if (strpos($route_match->getRouteName(), 'themekey_rule.') === 0) {
      return FALSE;
    }
    return TRUE;

    // TODO migrate complete function themekey_is_active() to applies()

    // Don't change theme when ...
    if (
      (in_array('system', variable_get('themekey_compat_modules_enabled', array())) || !(variable_get('admin_theme', '0') && path_is_admin($_GET['q']))) // ... admin area and admin theme set
      && strpos($_GET['q'], 'admin/structure/block/demo') !== 0 // ... block demo runs
      && strpos($_SERVER['SCRIPT_FILENAME'], 'cron.php') === FALSE // ... cron is executed by cron.php
      && strpos($_SERVER['SCRIPT_FILENAME'], 'drush.php') === FALSE // ... cron is executed by drush
      && (!defined('MAINTENANCE_MODE') || (MAINTENANCE_MODE != 'install' && MAINTENANCE_MODE != 'update')) // ... drupal installation or update runs
    ) {
      require_once DRUPAL_ROOT . '/' . drupal_get_path('module', 'themekey') . '/themekey_base.inc';
      $paths = $paths = array_merge_recursive(themekey_invoke_modules('themekey_disabled_paths'), module_invoke_all('themekey_disabled_paths'));
      foreach ($paths as $path) {
        if (strpos($_GET['q'], $path) === 0) {
          return FALSE;
        }
      }
      return TRUE;
    }
    return FALSE;
  }
  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->getThemeKeyEngine()->determineTheme($route_match);
  }
}