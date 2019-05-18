<?php

/**
 * Preprocess for the template nivo-slider-wrapper.html.twig
 */
function template_preprocess_nivo_slider_wrapper(&$variables) {
  $config = \Drupal::config('nivo_slider.settings');

  $variables['banners'] = nivo_slider_slider();
  $variables['themes'] = $config->get('options.theme');
  $variables['html_captions'] = $config->get('html_captions');
}