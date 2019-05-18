<?php

namespace Drupal\eform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\eform\Entity\EFormType;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Views;

/**
 * Base class for eForm controllers.
 */
class EFormControllerBase extends ControllerBase {

  /**
   * Get render links to Submission Pages for each display.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   * @param array $displays
   *
   * @return array
   */
  protected function submissionLinks(EFormType $eform_type, array $displays, $route_name) {
    $links_output = array(
      '#theme' => 'links',
      '#links' => [],
      '#attributes' => ['class' => ['tabs', 'secondary']],
    );
    /** @var DisplayPluginBase $display */
    foreach ($displays as $display_id => $display) {
      $route_args = [
        'eform_type' => $eform_type->type,
        'views_display_id' => $display_id,
      ];
      $url = Url::fromRoute($route_name, $route_args);
      $link = array(
        'title' => $display->getOption('title'),
        'url' => $url,
        // @todo This is not working
        '#attributes' => ['class' => ['tabs__tab']],
      );

      $links_output['#links'][] = $link;
    }
    return $links_output;
  }

  /**
   * Get View Displays that are usable for EForm Submission lists.
   *
   * @param $view_name
   *
   * @return array;
   */
  protected function getViewDisplays($view_name) {
    $useable_displays = [];
    if ($view = Views::getView($view_name)) {
      $view->initDisplay();
      $displays = $view->displayHandlers;
      /* @var DisplayPluginBase $display */
      foreach ($displays as $key => $display) {
        if ($this->isUseableDisplay($display)) {
          $useable_displays[$key] = $display;
        }
      }
    }
    return $useable_displays;
  }

  /**
   * Check if a View display is useable to show EForm submissions.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $display
   *
   * @return bool
   */
  protected function isUseableDisplay(DisplayPluginBase $display) {
    // @todo Is there a better way to check the for "embed" rather than checking class?
    $class = get_class($display);
    if ($display->isEnabled() && $class == 'Drupal\views\Plugin\views\display\Embed') {
      // @todo Check to make sure first arguement is EForm type.
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param \Drupal\eform\Entity\EFormType $eform_type
   * @param $views_display_id
   * @param $view_name
   *
   * @return array
   */
  protected function submissionsPage(EFormType $eform_type, $views_display_id, $view_name, $route_name) {
    $output = array();
    if ($usable_displays = $this->getViewDisplays($view_name)) {
      if (count($usable_displays) > 1) {
        $output['submissions_links'] = $this->submissionLinks($eform_type, $usable_displays, $route_name);
      }
      if (empty($views_display_id) && !isset($usable_displays[$views_display_id])) {
        // Default to first display.
        $display_ids = array_keys($usable_displays);
        $views_display_id = array_shift($display_ids);
      }
      $views_output = views_embed_view($view_name, $views_display_id, $eform_type->type);
      $output['submissions_view'] = $views_output;
      return $output;
    }
    else {
      // No useable displays in this View.
    }
    return $output;
  }

  /**
   * Determine if user would have at least 1 submission returned from View.
   *
   * Depending on the View used the user might not have submission returned.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   *
   * @return bool
   */
  function userHasViewSubmissions(EFormType $eform_type) {
    if ($view_name = $eform_type->getUserView()) {
      $display_ids = $this->getViewDisplays($view_name);
      $display_id = array_shift($display_ids);
      $results = views_get_view_result($view_name, $display_id, $eform_type->type);
      return !empty($results);
    }
    return FALSE;
  }

}
