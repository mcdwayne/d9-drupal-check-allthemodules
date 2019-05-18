<?php

namespace Drupal\bibcite;

/**
 * Class HelpService.
 *
 * @package Drupal\bibcite
 */
class HelpService implements HelpServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function getHelpMarkup($links, $route, $module) {
    $module_path = drupal_get_path('module', $module);
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $def = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $path = $module_path . '/help/' . $lang . '/' . $route . 'html';
    if ($def === $lang || !file_exists($path)) {
      $path = $module_path . '/help/default/' . $route . '.html';
    }
    if (file_exists($path)) {
      $output = file_get_contents($path);
      return sprintf($output, $links);
    }
    return NULL;
  }

}
