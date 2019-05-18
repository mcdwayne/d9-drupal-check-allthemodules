<?php

namespace Drupal\content_locker_consent\Plugin\ContentLocker;

use Drupal\content_locker\ContentLockerBase;

/**
 * Provides a content locker consent.
 *
 * @ContentLocker(
 *   id = "consent",
 *   label = "Consent",
 *   description = @Translation("Lock content via consent form.")
 * )
 */
class ContentLockerConsent extends ContentLockerBase {

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

    $form['general']['error'] = [
      '#type' => 'textarea',
      '#title' => 'Text to show, if unlock requirements did not meet.',
      '#default_value' => $this->getSetting('general.error'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultLibrary() {
    return array_merge(['content_locker_consent/content_locker_consent'], parent::defaultLibrary());
  }

}
