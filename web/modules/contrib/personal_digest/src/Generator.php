<?php

namespace Drupal\personal_digest;

use Drupal\user\UserInterface;
use Drupal\views\Views;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Provides a user password reset form.
 */
class Generator {

  protected $accountSwitcher;
  protected $renderer;
  protected $user;
  /**
   *
   * @var TimeInterface
   */
  protected $dateTime;

  /**
   *
   * @param AccountSwitcherInterface $account_switcher
   */
  function __construct(AccountSwitcherInterface $account_switcher, RendererInterface $renderer, TimeInterface $date_time) {
    $this->accountSwitcher = $account_switcher;
    $this->renderer = $renderer;
    $this->dateTime = $date_time;
  }

  function setAccount(UserInterface $user) {
    $this->user = $user;
    return $this;
  }


  /**
   * Build the main content of the digest email.
   *
   * @param array $views_displays
   *   A list of the views displays to render in the form viewname:displayname
   * @param int $since
   *   The date of the last message
   *
   * @return array
   *   A renderable array
   */
  function body(array $views_displays, $since) {
    $digest_content = [];
    $this->accountSwitcher->switchTo($this->user);
    foreach (array_keys($views_displays) as $combo) {
      list($view_name, $display_name) = explode(':', $combo);
      $view = Views::getView($view_name);
      if (!$view || !$view->access($display_name)) {
        continue;
      }
      $view->setArguments([date('Y-m-d', $since)]);
      $view->setDisplay($display_name);
      $result = $view->preview();

      if (count($view->result)) {
        $source = $this->renderer->renderPlain($result);
        $digest_content[$combo] = render($source);
      }
    }

    $this->accountSwitcher->switchBack();
    return $digest_content;
  }

  /**
   * @return Url
   */
  function loginLink($text) {
    $url = Url::fromRoute('personal_digest.remote.login',
      [
        'uid' => $this->user->id(),
        'timestamp' => $this->dateTime->getRequestTime(),
        'hash' => user_pass_rehash($this->user, $this->dateTime->getRequestTime()),
      ],
      ['absolute' => TRUE]
    );
    return Link::fromTextAndUrl($text, $url)->toString();
  }

  /**
   * Authenticate incoming hashes.
   *
   * @param int $timestamp
   * @param string $hash
   * @param int $time_limit
   *
   * @return boolean
   *   TRUE if access should be granted
   */
  function validate($timestamp, $hash, $time_limit) {
    $now = $this->dateTime->getRequestTime();
    // Verify that the user exists and is active.
    if ($this->user->isActive() and $this->user->isAuthenticated()) {
      if ($timestamp >= $this->user->getLastLoginTime()) {
        if ($now - $timestamp < $time_limit) {
          if ($timestamp <= $now) {
            if (Crypt::hashEquals($hash, user_pass_rehash($this->user, $timestamp))) {
              return TRUE;
            }
          }
        }
      }
    }
  }


}
