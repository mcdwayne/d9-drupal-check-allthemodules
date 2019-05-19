<?php

use Drupal\Core\Template\Attribute;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @file
 * Theme for WaterFall views.
 */
function template_preprocess_views_view_waterfall_views(&$variables) {
  $view = $variables['view'];
  $rows = $variables['rows'];
  //attached library
  $variables['#attached']['library'][] = 'waterfall_views/waterfall-views';
  // Process WaterFall.
  $variables['waterfall'] = [
    '#theme' => $view->buildThemeFunctions('waterfall_views'),
    '#view' => $view,
    '#rows' => $rows,
  ];
}
