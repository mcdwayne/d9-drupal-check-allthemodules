<?php

namespace Drupal\content_locker_login\Plugin\ContentLocker;

use Drupal\content_locker\ContentLockerBase;

/**
 * Provides a content locker consent.
 *
 * @ContentLocker(
 *   id = "log_in",
 *   label = "Log in",
 *   description = @Translation("Lock content via log in form.")
 * )
 */
class ContentLockerLogin extends ContentLockerBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form['general'] = [
      '#type' => 'details',
      '#title' => t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['text'] = [
      '#type' => 'text_format',
      '#title' => 'Text to show above the locker.',
      '#format' => $this->getSetting('general.text.format'),
      '#default_value' => $this->getSetting('general.text.value'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultLibrary() {
    return array_merge(['content_locker_login/content_locker_login'], parent::defaultLibrary());
  }

  /**
   * {@inheritdoc}
   */
  public function defaultAccess() {
    return !\Drupal::currentUser()->isAnonymous();
  }

}
