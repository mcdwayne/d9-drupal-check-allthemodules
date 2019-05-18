<?php

/**
 * @file
 * Theme for jquery_view_ticker.
 */
function template_preprocess_views_view_jquery_view_ticker(&$variables) {
  $view = $variables['view'];
  $handler  = $view->style_plugin;
  
  $variables['items'] = array();
  
  foreach ($view->result as $key => $row) {
    $variables['items'][] = $handler->getFieldValue($key, 'title');
  }

  $options = array(
    'random' => $handler->options['random'],
    'itemSpeed' => $handler->options['itemspeed'],
    'cursorSpeed' => $handler->options['cursorspeed'],
    'pauseOnHover' => $handler->options['pauseonhover'],
    'finishOnHover' => $handler->options['finishonhover'],
    'fade' => $handler->options['fadeeffect'],
    'fadeInSpeed' => $handler->options['fadeinspeed'],
    'fadeOutSpeed' => $handler->options['fadeoutspeed'],
  );
  $variables['#attached']['drupalSettings']['jQueryviewtricker'] = $options;
  $variables['#attached']['library'][] = 'jquery_view_ticker/jquery_view_ticker.plugin';
}
