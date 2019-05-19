<?php

namespace Drupal\synapse\Hook;

/**
 * PreprocessHtml.
 */
class PageAttachments {

  /**
   * Hook.
   */
  public static function hook(array &$page) {
    $config = \Drupal::config('synapse.settings');
    self::yandexWebmaster($page, $config);
    self::googleWebmaster($page, $config);
    self::googleTagManager($page, $config);
  }

  /**
   * Google Tag Manager.
   */
  public static function googleTagManager(&$page, $config) {
    $path = \Drupal::service('path.current')->getPath();
    $admin = FALSE;
    if (\Drupal::currentUser()->id() == 1 && $config->get('gtm-admin-disable')) {
      $admin = TRUE;
    }
    if ($config->get('gtm-id') && substr($path, 0, 7) != '/admin/' && !$admin) {
      $script = "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        '//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','" . $config->get('gtm-id') . "');\n";
      $page['#attached']['html_head'][] = [
        [
          '#tag' => 'script',
          '#value' => $script,
        ],
        'gtm',
      ];
    }
  }

  /**
   * Google webmaster.
   */
  public static function googleWebmaster(&$page, $config) {
    if ($config->get('wm-google')) {
      $page['#attached']['html_head'][] = [
        [
          '#tag' => 'meta',
          '#attributes' => [
            'name'    => 'google-site-verification',
            'content' => $config->get('wm-google'),
          ],
        ],
        'google',
      ];
    }
  }

  /**
   * Yandex webmaster.
   */
  public static function yandexWebmaster(&$page, $config) {
    if ($config->get('wm-yandex')) {
      $page['#attached']['html_head'][] = [
        [
          '#tag' => 'meta',
          '#attributes' => [
            'name'    => 'yandex-verification',
            'content' => $config->get('wm-yandex'),
          ],
        ],
        'yandex',
      ];
    }
  }

}
