<?php

namespace Drupal\text2image\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Class Text2ImageController.
 */
class Text2ImageController extends ControllerBase {

  /**
   * Home.
   *
   * @return string
   *   Return markup.
   */
  public function home() {
    $readme = file_get_contents(drupal_get_path('module', 'text2image') . '/README.md');
    $readme = preg_replace('/###(.*)\n/m', '<em>$1:</em><br />', $readme);
    $readme = preg_replace('/##(.*)\n/m', '<strong>$1</strong><br />', $readme);
    $readme = preg_replace('/#(.*)\n/m', '<strong>$1</strong><br />', $readme);
    $readme = preg_replace('/\[([\w\s]+)\]\((https:\/\/[\/\.\-\w\s]+)\)\n/m', '<a href="$2" target="_blank">$1</a><br />', $readme);
    $readme = preg_replace('/```([\w\d\s\W]+)```/mU', '<pre>$1</pre>', $readme);
    $markup = nl2br($readme);
    return [
      '#type' => 'markup',
      '#markup' => $markup,
    ];
  }

  /**
   * Set config to default values.
   *
   * @return \Drupal\text2image\Controller\RedirectResponse
   *   Redirect to fonts config.
   */
  public function reset() {
    \Drupal::service('text2image.fonts')->restoreDefaults();
    drupal_set_message('Text2Image font settings restored to default');
    return new RedirectResponse(Url::fromRoute('text2image.config_fonts')->toString());
  }

}
