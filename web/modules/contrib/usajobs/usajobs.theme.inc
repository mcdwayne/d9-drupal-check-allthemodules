<?php

/**
 * @file
 * Preprocessors and theme functions of USAJobs module.
 */

use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

/**
 * Prepares variables for usajob item templates.
 *
 * Default template: usajobs-item.html.twig.
 *
 * @param array $variables
 *
 */
function template_preprocess_usajobs_item(&$variables) {
  $item = $variables['elements']['#items'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $variables['job_title'] = HTML::escape($item->position_title);
  $variables['job_url'] = UrlHelper::stripDangerousProtocols($item->url);
  $variables['job_organization'] = HTML::escape($item->organization_name);
  $variables['job_location'] = HTML::escape($item->locations[0]);
  $variables['job_salary_min'] = number_format($item->minimum, 2);
  $variables['job_salary_max'] = number_format($item->maximum, 2);

  $variables['job_start_date'] = '';
  if (isset($item->start_date)) {
    $variables['job_start_date'] = format_date(strtotime($item->start_date), 'custom', 'F d, Y');
  }

  $variables['job_end_date'] = '';
  if (isset($item->end_date)) {
    $variables['job_end_date'] = format_date(strtotime($item->end_date), 'custom', 'F d, Y');
  }

}
