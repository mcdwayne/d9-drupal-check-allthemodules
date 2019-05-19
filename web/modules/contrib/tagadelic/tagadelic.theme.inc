<?php

use Drupal\tagadelic\TagadelicTag;
use Drupal\tagadelic\TagadelicCloudView;
use Drupal\Core\Template\Attribute;

/**
 * @file
 * Theme for tagadelic views.
 */

/**
 * Prepares variables for tagadelic view template.
 *
 * Default template: tagadelic-view-tagdelic-list.html.twig.
 *
 * Create a tagdelic tag for each view result and make an array of tags available in the template
 *
 * @param array $variables
 *   An associative array
 */
function template_preprocess_tagadelic_view_tagadelic_list(&$variables) {
  $view = $variables['view'];
  $rows = $variables['rows'];
  $style = $view->style_plugin;
  $options = $style->options;

  $count_field = $options['count_field'];
  if(empty($count_field)) {
    drupal_set_message(t("Please set which field you are using for counting results in the tagadelic display."), "error");
    return "";
  }

  $params = array('view' =>  $view, 'count_field' => $count_field, 'override_sort' => $options['override_sort']);
  $cloud = \Drupal::service('tagadelic.tagadelic_view');
  $tags = $cloud->getTags($params);

  $variables['default_row_class'] = !empty($options['default_row_class']);
  foreach ($rows as $id => $row) {
    $row['weight'] = $tags[$id]->getWeight();
    $variables['rows'][$id] = array();
    $variables['rows'][$id]['content'] = $row;
  }
}
