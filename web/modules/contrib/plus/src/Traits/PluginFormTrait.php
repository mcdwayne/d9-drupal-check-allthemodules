<?php

namespace Drupal\plus\Traits;

use Drupal\Core\Form\FormStateInterface;
use Drupal\plus\Plugin\Theme\ThemeInterface;

/**
 * Trait FormAutoloadFixTrait.
 */
trait PluginFormTrait {

  /**
   * Adds the autoload fix include file to the form state.
   *
   * This may be necessary if you notice your AJAX callbacks not working.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\plus\Plugin\Theme\ThemeInterface $theme
   *   The theme to retrieve the file to use for the autoload fix.
   */
  public static function formAutoloadFix(FormStateInterface $form_state, ThemeInterface $theme = NULL) {
    if (!isset($theme)) {
      $theme = \Drupal::service('plus')->getActiveTheme();
    }

    $files = $form_state->getBuildInfo()['files'];

    // Only add the include once.
    $file = $theme->autoloadFixInclude();
    $key = array_search($file, $files);
    if ($key === FALSE) {
      array_unshift($files, $file);
      $form_state->addBuildInfo('files', $files);
    }
  }

}
