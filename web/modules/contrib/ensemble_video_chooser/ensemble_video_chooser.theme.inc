<?php
/**
 * @file
 * Ensemble Video Chooser theme include.
 */

/**
 * Module preprocess function for hook html.
 */
function ensemble_video_chooser_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  if ($route == 'ensemble_video_chooser.launch' || $route == 'ensemble_video_chooser.return') {
    /*
     * Our launch/return pages are simply running script and immediately
     * redirecting or closing.  We're trying to get to the most basic html page
     * to avoid an unnecessary flash of content to the end user.  Hide page top
     * and bottom here.
     */
    $variables['page_top'] = NULL;
    $variables['page_bottom'] = NULL;
  }
}
