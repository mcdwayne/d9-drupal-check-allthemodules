<?php

namespace Drupal\breakpoints_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Component\Serialization\Yaml;

/**
 * Class BreakpointsUiController.
 *
 * @package Drupal\breakpoints_ui\Controller
 */
class BreakpointsUiController extends ControllerBase {

  /**
   * Breakpointsui.
   *
   * @return string
   *   Information Breakpoints info.
   */
  public function breakpointsui() {
      $class = get_class($this);
      return [
          '#theme' => 'breakpoints_ui',
          '#pre_render' => [
              [$class, 'preRenderMyElement'],
          ],
      ];
  }

    /**
     * {@inheritdoc}
     */
    public static function preRenderMyElement($element) {
        $breakpointInfo = \Drupal::service('breakpoints_ui.default')->getAllBreakpoints();
        $element['breakpoints_groups'] = $breakpointInfo;
        return $element;
    }

}
