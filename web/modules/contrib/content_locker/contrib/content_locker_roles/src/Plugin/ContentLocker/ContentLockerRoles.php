<?php

namespace Drupal\content_locker_roles\Plugin\ContentLocker;

use Drupal\content_locker\ContentLockerBase;

/**
 * Provides a content locker roles.
 *
 * @ContentLocker(
 *   id = "roles",
 *   label = "Roles",
 *   description = @Translation("Lock content via role permissions.")
 * )
 */
class ContentLockerRoles extends ContentLockerBase {

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
    return array_merge(['content_locker_roles/content_locker_roles'], parent::defaultLibrary());
  }

  /**
   * {@inheritdoc}
   */
  public function defaultAccess() {
    return \Drupal::currentUser()->hasPermission('view locked content');
  }

}
