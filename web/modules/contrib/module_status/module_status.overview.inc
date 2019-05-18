<?php

use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Prepares variables for module status overview templates.
 *
 * Default template: module-status-overview.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - data: An array of data about each module's status.
 */
function template_preprocess_module_status_overview(&$variables) {
  /** @var \Drupal\Core\Render\RendererInterface $renderer */
  $renderer = \Drupal::service('renderer');

  $refresh_link = Link::createFromRoute(
    $variables['refresh_link']['text'],
    $variables['refresh_link']['path']
  )->toRenderable();
  $variables['refresh_link'] = $renderer->render($refresh_link);

  $variables['module_status_overview_table']['#rows'] = [];
  foreach ($variables['module_status_overview_data'] as $module_name => $module_data) {
    if ($module_data['callSuccessful']) {
      $url = Url::fromUri($module_data['link'], ['attributes' => ['target' => '_blank']]);
      $link = Link::fromTextAndUrl(t('Link'), $url);
      $count = $module_data['count'];
    }
    else {
      $link = '-';
      $count = t('- (could not retrieve Issues)');
    }
    $variables['module_status_overview_table']['#rows'][] = [
      $module_name,
      $count,
      $link,
    ];

  }

  $variables['module_status_overview_table'] = $renderer->render(
    $variables['module_status_overview_table']
  );
}
