<?php

/**
 * @file
 * Contains \Drupal\content_callback_views\Plugin\Derivative\ViewsContentCallback.
 */

namespace Drupal\content_callback_views\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\views\Views;

/**
 * Retrieves content callbacks for each view.
 */
class ViewsContentCallback extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    // Check all Views for content callback displays
    foreach (Views::getAllViews() as $view) {
      // Do not return results for disabled views.
      if (!$view->status()) {
        continue;
      }
      $executable = $view->getExecutable();
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $display) {
        if (isset($display) && $display->definition['id'] == 'content_callback') {
          $delta = $view->id() . '-' . $display->display['id'];

          if ($display->display['display_title'] == $display->definition['title']) {
            $title = t('View: @view', array('@view' => $view->label()));
          }
          else {
            $title = t('View: @view: @display', array('@view' => $view->label(), '@display' => $display->display['display_title']));
          }

          $this->derivatives[$delta] = array(
            'title' => $title,
            'view_name' => $view->id(),
            'view_display' => $display->display['id'],
          );

          $this->derivatives[$delta] += $base_plugin_definition;
        }
      }
    }

    return $this->derivatives;
  }
}
