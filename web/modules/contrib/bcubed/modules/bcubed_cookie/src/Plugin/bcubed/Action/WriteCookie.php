<?php

namespace Drupal\bcubed_cookie\Plugin\bcubed\Action;

use Drupal\bcubed\ActionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Inserts a replacement ad server via proxy from the bcubed network.
 *
 * @Action(
 *   id = "write_cookie",
 *   label = @Translation("Write Cookie"),
 *   description = @Translation("Creates or replaces a cookie"),
 *   instances = true,
 *   settings = {
 *     "cookiename" = "",
 *     "cookievalue" = "",
 *     "expires" = 0,
 *   }
 * )
 */
class WriteCookie extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed_cookie/writecookie';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['cookiename'] = [
      '#type' => 'textfield',
      '#title' => 'Cookie Name',
      '#default_value' => $this->settings['cookiename'],
      '#required' => TRUE,
    ];

    $form['cookievalue'] = [
      '#type' => 'textfield',
      '#title' => 'Value',
      '#default_value' => $this->settings['cookievalue'],
      '#required' => TRUE,
    ];

    $form['expires'] = [
      '#type' => 'number',
      '#title' => 'Expires',
      '#description' => 'Number of days until cookie expires. A value of zero means the cookie will expire at the end of the session.',
      '#default_value' => $this->settings['expires'],
    ];

    return $form;
  }

}
