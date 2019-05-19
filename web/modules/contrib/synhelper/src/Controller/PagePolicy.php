<?php

namespace Drupal\synhelper\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page routes.
 */
class PagePolicy extends ControllerBase {

  /**
   * Page Title.
   */
  public function title($lang = FALSE) {
    if (!$lang) {
      $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    $titles = [
      'en' => $this->t('Privacy and Cookie policy'),
      'ru' => $this->t('Соглашение об использовании персональных данных'),
    ];
    if (isset($titles[$lang])) {
      $title = $titles[$lang];
    }
    else {
      $title = $titles['en'];
    }
    return $title;
  }

  /**
   * Constructs page from template.
   */
  public function page($lang = FALSE) {
    if (!$lang) {
      $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    $path = \Drupal::service('module_handler')->getModule('synhelper')->getPath();
    $templates = [
      'en' => DRUPAL_ROOT . "/$path/assets/policy-en.html",
      'ru' => DRUPAL_ROOT . "/$path/assets/policy-ru.html",
    ];
    if (isset($templates[$lang])) {
      $policy = $templates[$lang];
    }
    else {
      $policy = $templates['en'];
    }
    $html = file_get_contents($policy);
    return [
      'policy' => [
        '#type' => 'inline_template',
        '#template' => $html,
        '#context' => [
          'url' => $host = \Drupal::request()->getHost(),
        ],
      ],
    ];
  }

}
