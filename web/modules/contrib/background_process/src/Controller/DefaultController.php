<?php

namespace Drupal\background_process\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Access\AccessResult;

/**
 * Implements Default controller for the background_process module.
 */
class DefaultController extends ControllerBase {

  /**
   * Implements Background Process Service Access.
   */
  public function backgroundProcessServiceAccess($handle, $token, AccountInterface $account) {
    // Setup service.
    ignore_user_abort(TRUE);
    $handle = rawurldecode($handle);
    $token = rawurldecode($token);

    // Ensure no session!
    unset($_SESSION);

    $process = background_process_get_process($handle);

    if (!$process) {
      \Drupal::logger('bg_process')->notice('Unknown process: %handle', [
        '%handle' => $handle,
      ]);
      return FALSE;
    }

    if ($token !== $process->token) {
      \Drupal::logger('bg_process')->notice('Invalid token: %token for handle: %handle', [
        '%token' => $token,
        '%handle' => $handle,
      ]);
      return FALSE;
    }

    // Login as the user that requested the call.
    $user = \Drupal::currentUser();
    if ($process->uid) {
      $load_user = \Drupal::entityManager()->getStorage('user')->load($process->uid);
      if (!$load_user) {
        // Invalid user!
        \Drupal::logger('bg_process')->notice('Invalid user: %uid for handle: %handle', [
          '%uid' => $process->uid,
          '%handle' => $handle,
        ]);
        return FALSE;
      }
      $user = $load_user;
    }
    else {
      $user = new AnonymousUserSession();
    }

    return AccessResult::allowedIf($account->hasPermission(TRUE));
  }

  /**
   * Implements Background Process Service Start.
   */
  public function backgroundProcessServiceStart($handle, $return = FALSE) {
    header("Content-Type: text/plain");

    // Clean up the mess the menu-router system leaves behind.
    $cid = 'menu_item:' . hash('sha256', $_GET['q']);
    drupal_register_shutdown_function('_background_process_cleanup_menu', $cid);

    // Setup service.
    ignore_user_abort(TRUE);
    @set_time_limit(\Drupal::config('background_process.settings')->get('background_process_service_timeout'));

    $handle = rawurldecode($handle);
    return background_process_service_execute($handle, $return);
  }

  /**
   * Implements Background Process Service Unlock.
   */
  public function backgroundProcessServiceUnlock($handle) {
    $handle = rawurldecode($handle);

    if (background_process_unlock($handle)) {
      drupal_set_message($this->t('Process %handle unlocked', ['%handle' => $handle]));
    }
    else {
      drupal_set_message($this->t('Process %handle could not be unlocked', [
        '%handle' => $handle,
      ]), 'error');
    }
    return new RedirectResponse('/admin/config/system/background-process/overview');
  }

  /**
   * Implements to Check Token.
   */
  public function backgroundProcessCheckToken() {
    header("Content-Type: text/plain");
    print \Drupal::config('background_process.settings')->get('background_process_token');
    exit;
  }

  /**
   * Implements Background Process Overview Page.
   */
  public function backgroundProcessOverviewPage() {
    $processes = background_process_get_processes();
    $data = [];
    global $base_url;

    foreach ($processes as $process) {
      $progress = progress_get_progress($process->handle);
      $url = Url::fromUri($base_url . '/background-process/unlock/' . rawurlencode($process->handle));
      $external_link = \Drupal::l($this->t('Unlock'), $url);

      if ($process->callback[1] != '') {
        $process->callback = $process->callback[1];
      }

      $data[] = [
        $process->handle,
        _background_process_callback_name($process->callback),
        $process->uid,
        $process->service_host,
        \Drupal::service('date.formatter')->format((int) $process->start, 'custom', 'Y-m-d H:i:s'),
        $progress ? sprintf("%.02f%%", $progress->progress * 100) : $this->t('N/A'),
        $external_link,
              ['attributes' => ['class' => 'button-unlock'], 'query' => \Drupal::service('redirect.destination')->getAsArray()],
      ];
    }

    $header = [
      'Handle',
      'Callback',
      'User',
      'Host',
      'Start time',
      'Progress',
      'Unlock',
    ];

    // The table description.
    $markup = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $data,
    ];

    return $markup;
  }

}
