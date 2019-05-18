<?php

namespace Drupal\locker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locker bridge controller.
 */
class LockCont extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    global $base_url;

    $content = [];

    $form = \Drupal::formBuilder()->getForm('Drupal\locker\Form\UnlockForm');
    unset($form['actions']['submit']);

    $content['form'] = \Drupal::service('renderer')->renderRoot($form);
    $content['css'] = '<link type="text/css" rel="stylesheet" href="'
      . base_path() . drupal_get_path('module', 'locker') . '/css/locker.css'
      . '" media="all" />';

    $content['js'] =
      '<s' . 'cript type="text/javascript" src="' . base_path() . drupal_get_path('core', 'libraries') . '/assets/vendor/jquery/jquery.min.js' . '"></s' . 'cript>' .
      '<s' . 'cript src="' . base_path() . drupal_get_path('module', 'locker') . '/js/locker.js' . '"></s' . 'cript>';

    $config = \Drupal::config('locker.settings');
    $content['locker_access_options'] = $config->get('locker_access_options');
    $unlock_time = $config->get('unlock_datetime');
    if (!empty($unlock_time)) {
      $refresh_after_sec = (strtotime($unlock_time) - strtotime('now'));
      if ($refresh_after_sec >= 0) {
        $content['refresh_content'] = $refresh_after_sec . '; url=' . $base_url . base_path();
        $content['unlock_time'] = new DrupalDateTime($unlock_time);
        $content['refresh_duration'] = \Drupal::service('date.formatter')->formatTimeDiffUntil(strtotime($config->get('unlock_datetime')), ['granularity' => 2]);
      }
    }

    if (\Drupal::state()->get('locker_login_error') == 'yes') {
      $content['error_message'] = 'Bad credentials!';
      \Drupal::state()->delete('locker_login_error');
    }

    $elements = [
      '#theme' => 'locker',
      '#vars' => $content,
    ];

    $html = \Drupal::service('renderer')->render($elements);
    $response = new Response($html, Response::HTTP_OK, ['content-type' => 'text/html']);
    return $response;
  }

}
