<?php

/**
 * @file
 * Contains cloudconvert_task.page.inc.
 *
 * Page callback for CloudConvert Task entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for CloudConvert Task templates.
 *
 * Default template: cloudconvert_task.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_cloudconvert_task(array &$variables) {
  /** @var \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask */
  $cloudConvertTask = $variables['elements']['#cloudconvert_task'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  /** @var \Drupal\cloudconvert\CloudConvertProcessor $cloudConvertProcessor */
  $cloudConvertProcessor = Drupal::service('cloudconvert.processor');
  $callbackUrl = $cloudConvertProcessor->getCallbackUrl($cloudConvertTask);
  $processInfo = $cloudConvertTask->getProcessInfo();
  $url = isset($processInfo['url']) ? $processInfo['url'] : '';
  $callbackUrl->setOption('query', ['step' => 'finished', 'url' => $url, 'id' => $cloudConvertTask->getProcessId()]);
  $variables['content']['callback'] = [
    '#type' => 'link',
    '#url' => $callbackUrl,
    '#title' => $callbackUrl->toString(),
  ];
}
