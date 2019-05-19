<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * PreprocessHtml.
 */
class PreprocessHtml extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$variables) {
    // No-index:
    $noindex_paths = [
      '/user/login',
      '/user/password',
    ];
    $current_path = \Drupal::service('path.current')->getPath();
    if (in_array($current_path, $noindex_paths)) {
      $noindex = [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'robots',
          'content' => 'none',
        ],
      ];
      $variables['page']['#attached']['html_head'][] = [$noindex, 'indexation'];
    };
    $config = \Drupal::config('synhelper.settings');
    if ($config->get('no-index')) {
      $variables['page']['#attached']['html_head'][] = [
        [
          '#tag' => 'meta',
          '#attributes' => [
            'name'    => 'robots',
            'content' => 'none',
          ],
        ],
        'indexation',
      ];
      if (substr($current_path, 0, 7) == '/admin/') {
        $url = Url::fromUserInput('/admin/config/synapse/synhelper')->toString();
        $message = t('<b>NO-INDEX Attention!</b> The site is not indexed by search engines. <a href="@href">OFF/ON indexation</a>!', ['@href' => $url]);
        drupal_set_message(Markup::create($message), 'error');
      }
    }
  }

}
