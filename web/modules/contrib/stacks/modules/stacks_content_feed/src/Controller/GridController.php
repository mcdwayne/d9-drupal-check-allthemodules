<?php
/**
 * @file
 * Contains \Drupal\stacks_content_feed\Controller\GridController.
 */

namespace Drupal\stacks_content_feed\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\stacks\Widget\WidgetData;
use Drupal\stacks\Entity\WidgetEntity;
use Drupal\stacks_content_feed\Plugin\WidgetType\ContentFeed;

/**
 * Class GridController.
 *
 * @package Drupal\stacks_content_feed\Controller
 */
class GridController extends ControllerBase {

  /**
   * All grid ajax calls hit this method.
   *
   * This is called from this route: /ajax/grid.
   *
   * This should return an AJAX response.
   *
   * @See js/grid.ajax.js for an example in how this is used in the JS.
   *
   * @See GridDynamicNodeContent->modifyRenderArray()
   */
  public function gridAjax() {
    // Require options.
    if (!isset($_POST['widgetid']) || !isset($_POST['typeofgrid']) || !isset($_POST['theme']) || !isset($_POST['isentity'])) {
      return ContentFeed::postAjaxErrorMessage('<p>' . t('GridController: AJAX call is missing required options.') . '</p>');
    }

    // Define variables from the post. These are attached to .ajax_info as
    // attributes.
    $options = [
      'widget_id' => (int) $_POST['widgetid'],
      'type_of_grid' => $_POST['typeofgrid'],
      'theme_template' => $_POST['theme'],
      'isentity' => (int) $_POST['isentity'],
      'notentity' => (string) $_POST['notentity'],
    ];

    // Grab the correct grid object.
    if ($options['isentity'] == 1) {
      // This is a stacks entity.
      $widget_entity = WidgetEntity::load($options['widget_id']);

      // Require a valid entity object.
      if (!$widget_entity) {
        return ContentFeed::postAjaxErrorMessage('<p>' . t('GridController: Widget entity not defined.') . '</p>');
      }

      // Require a valid widget type object.
      $widget_type_object = WidgetData::getWidgetTypeObject($widget_entity);
      if (!is_object($widget_type_object)) {
        return ContentFeed::postAjaxErrorMessage('<p>' . t('GridController: No widget type object defined.') . '</p>');
      }
    }
    else {
      // This is not a stacks entity.
      // Require notentity.
      if (!isset($_POST['notentity'])) {
        return ContentFeed::postAjaxErrorMessage('<p>' . t('GridController: AJAX call is missing notentity.') . '</p>');
      }

      // Load the correct grid type class.
      $class_path = $options['notentity'];
      if (!class_exists($class_path)) {
        // The class doesn't exist. Do nothing.
        drupal_set_message(t("The class @class_path can't be found.", ['@class_path' => $class_path]), 'error');
        return FALSE;
      }
      else {
        // If the class exists, then we check this is valid class (Must inherit from the ContentFeed class)
        $reflector = new \ReflectionClass($class_path);
        $is_child = $reflector->isSubclassOf('Drupal\stacks_content_feed\Plugin\WidgetType\ContentFeed');
        if (!$is_child) {
          return ContentFeed::postAjaxErrorMessage('<p>' . t('GridController: Invalid AJAX parameter.') . '</p>');
        }
      }

      $widget_type_object = new $class_path($options['widget_id']);
    }

    // This should point to the ajax template.
    // Example: ajax__widget_contentfeed__default.
    $render_array = ['#theme' => $options['theme_template']];

    // Return the ajax response for the grid object.
    $active_filters = isset($_POST['filters']) ? $_POST['filters'] : [];
    return $widget_type_object->doAjax($render_array, $active_filters);
  }

}
