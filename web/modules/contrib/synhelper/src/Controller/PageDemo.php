<?php

namespace Drupal\synhelper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Controller routines for page routes.
 */
class PageDemo extends ControllerBase {

  /**
   * Page Title.
   */
  public function title($lang = FALSE) {
    $config = \Drupal::config('synhelper.settings');
    if (!$lang) {
      $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    $titles = [
      'en' => $this->t('Demo'),
      'ru' => $this->t('Типовая страница'),
    ];
    if (isset($titles[$lang])) {
      $title = $titles[$lang];
    }
    else {
      $title = $titles['en'];
    }
    if (!$config->get('style-page')) {
      $title = '';
    }
    return $title;
  }

  /**
   * Constructs page from template.
   */
  public function page($lang = FALSE) {
    $config = \Drupal::config('synhelper.settings');
    if (!$lang) {
      $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    $path = \Drupal::service('module_handler')->getModule('synhelper')->getPath();
    $templates = [
      'en' => DRUPAL_ROOT . "/$path/content/demo-page-en.yml",
      'ru' => DRUPAL_ROOT . "/$path/content/demo-page-ru.yml",
    ];
    if (isset($templates[$lang])) {
      $page = $templates[$lang];
    }
    else {
      $page = $templates['en'];
    }
    $html = '';
    if ($config->get('style-page')) {
      $array = Yaml::parse(file_get_contents($page));
      $html = $array['body'];
    }
    return [
      'demo-page' => [
        '#type' => 'inline_template',
        '#template' => $html,
        '#context' => [
          'url' => $host = \Drupal::request()->getHost(),
        ],
      ],
    ];
  }

}
