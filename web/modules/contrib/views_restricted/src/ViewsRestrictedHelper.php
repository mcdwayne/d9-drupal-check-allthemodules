<?php

namespace Drupal\views_restricted;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewEntityInterface;

/**
 * ViewsRestrictedHelper service.
 */
class ViewsRestrictedHelper {

  /**
   * @param \Drupal\views\ViewEntityInterface $view
   * @param \Drupal\views_restricted\ViewsRestrictedInterface $views_restricted
   */
  public static function setViewsRestricted(ViewEntityInterface $view, ViewsRestrictedInterface $views_restricted) {
    $pluginId = $views_restricted->getPluginId();
    self::setViewsRestrictedId($view, $pluginId);
  }

  /**
   * @param \Drupal\views\ViewEntityInterface $view
   * @param string $pluginId
   */
  public static function setViewsRestrictedId(ViewEntityInterface $view, $pluginId) {
    $view->viewsRestricted = $pluginId;
  }

  /**
   * @param \Drupal\views\ViewEntityInterface $view
   *
   * @return \Drupal\views_restricted\ViewsRestrictedInterface|null
   */
  public static function extractViewsRestricted(ViewEntityInterface $view) {
    if ($viewsRestrictedId = self::extractViewsRestrictedId($view)) {
      /** @var \Drupal\views_restricted\ViewsRestrictedPluginManager $viewsRestrictedPluginManager */
      $viewsRestrictedPluginManager = \Drupal::service('plugin.manager.views_restricted');
      /** @var \Drupal\views_restricted\ViewsRestrictedInterface $viewsRestricted */
      $viewsRestricted = $viewsRestrictedPluginManager->createInstance($viewsRestrictedId);
      return $viewsRestricted;
    }
    else {
      return NULL;
    }
  }

  /**
   * @param \Drupal\views\ViewEntityInterface $view
   *
   * @return string|null
   */
  public static function extractViewsRestrictedId(ViewEntityInterface $view) {
    return isset($view->viewsRestricted) ? $view->viewsRestricted : NULL;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\views\ViewEntityInterface
   */
  public static function extractViewsUi(FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityFormInterface $formObject */
    $formObject = $form_state->getFormObject();
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = $formObject->getEntity();
    return $view;
  }

  /**
   * @param array $build
   * @param \Drupal\Core\Access\AccessResult $accessResult
   */
  public static function removeBuildIfNoAccess(array &$build, AccessResult $accessResult) {
    if (!$accessResult->isAllowed()) {
      self::removeBuild($build);
    }
  }

  public static function isDebugMode() {
    return \Drupal::configFactory()->get('views_restricted.settings')->get('debug');
  }

  public static function getBacktraceQuery() {
    return \Drupal::configFactory()->get('views_restricted.settings')->get('backtrace_on_query');
  }

  public static function printableBacktrace($stacktrace, $glue = "\n")  {
    $lines = [];
    $i = 0;
    foreach($stacktrace as $node) {
      $file = isset($node['file']) ? $node['file'] : '';
      $file = basename($file);
      $function = isset($node['function']) ? $node['function'] : '';
      $line = isset($node['line']) ? $node['line'] : '';
      $lines[] = "$i. $file:$function($line)";
      $i++;
    }
    return implode($glue, $lines);
  }

  /**
   * @param \Drupal\views\ViewEntityInterface $view
   * @param $display_id
   * @param $type
   * @param $table
   * @param $field
   * @param $alias
   *
   * @return string
   */
  public static function makeInfoString(ViewEntityInterface $view, $display_id, $type, $table, $field, $alias) {
    $baseTable = $view->get('base_table');
    $viewId = $view->id();
    // NULLs will be converted to empty strings.
    $infoString = "$baseTable/$viewId/$display_id/$type/$table/$field/$alias/";
    return $infoString;
  }


  /**
   * Get routes to alter.
   *
   * Empty items here will get an additional parameter, and their links will be
   * massaged to include this parameter.
   *
   * @return array
   */
  public static function getRouteAlter() {
    // @see views_ui.routing.yml
    // It'sd all routes that have a view parameter.
    return [
      // 'entity.view.collection',
      // 'views_ui.add',
      // 'views_ui.settings_basic',
      // 'views_ui.settings_advanced',
      // 'views_ui.reports_fields',
      // 'views_ui.reports_plugins',
      // 'entity.view.enable',
      // 'entity.view.disable',
      'entity.view.duplicate_form' => [
        'defaults' => ['type' => 'duplicate'],
      ],
      'entity.view.delete_form' => [
        'defaults' => ['type' => 'delete'],
      ],
      // views_ui.autocomplete,
      'entity.view.edit_form' => [
      ],
      'entity.view.edit_display_form' => [
      ],
      'entity.view.preview_form' => [
        'defaults' => ['type' => 'preview'],
      ],
      'entity.view.break_lock_form' => [
        'defaults' => ['type' => 'break_lock'],
      ],
      'views_ui.form_add_handler' => [
        'controller' => '\Drupal\views_restricted\Form\Ajax\AddHandler::getForm',
      ],
      'views_ui.form_edit_details' => [
        'controller' => '\Drupal\views_restricted\Form\Ajax\EditDetails::getForm',
        'defaults' => ['type' => 'edit_details'],
      ],
      'views_ui.form_reorder_displays' => [
        'controller' => '\Drupal\views_restricted\Form\Ajax\ReorderDisplays::getForm',
        'defaults' => ['type' => 'reorder_displays'],
      ],
      'views_ui.form_analyze' => [
        'defaults' => ['type' => 'analyze'],
      ],
      'views_ui.form_rearrange' => [
        'controller' => '\Drupal\views_restricted\Form\Ajax\Rearrange::getForm',
      ],
      'views_ui.form_rearrange_filter' => [
        'controller' => '\Drupal\views_restricted\Form\Ajax\RearrangeFilter::getForm',
        'defaults' => ['type' => 'filter'],
      ],
      'views_ui.form_display' => [
        'controller' => '\Drupal\views_restricted\Form\Ajax\Display::getForm',
      ],
      'views_ui.form_handler' => [
        'controller' => '\Drupal\views_restricted\Form\Ajax\ConfigHandler::getForm',
      ],
      'views_ui.form_handler_extra' => [
        'controller' => '\Drupal\views_restricted\Form\Ajax\ConfigHandlerExtra::getForm',
      ],
      'views_ui.form_handler_group' => [
        'controller' => '\Drupal\views_restricted\Form\Ajax\ConfigHandlerGroup::getForm',
      ],
    ];
  }

  /**
   * @param array $build
   */
  public static function removeBuild(array &$build) {
    $build = [];
  }

}
