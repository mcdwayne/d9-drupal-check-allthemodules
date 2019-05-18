<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Server;

use Drupal\Core\Form\FormStateInterface;
use Drupal\prod_check\Plugin\ProdCheck\ProdCheckBase;

/**
 * Release notes check
 *
 * @ProdCheck(
 *   id = "release_notes",
 *   title = @Translation("Release notes & help files"),
 *   category = "server",
 * )
 */
class ReleaseNotes extends ProdCheckBase {

  /**
   * Remaining files
   */
  public $remaining_files;

  /**
   * {@inheritdoc}
   */
  public function init() {
    $this->remaining_files = array();
    foreach ($this->configuration['files'] as $file) {
      if (file_exists(DRUPAL_ROOT . '/' . $file)) {
        array_push($this->remaining_files, $file);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function state() {
    return empty($this->remaining_files);
  }

  /**
   * {@inheritdoc}
   */
  public function successMessages() {
    return [
      'value' => $this->t('Release note & help files have been removed.'),
      'description' => $this->t('Status is OK for production use.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function failMessages() {
    return [
      'value' => $this->t('Release note & help files still present on your server!'),
      'description' => $this->t('Leaving the "@files" files present on the webserver is a minor security risk. These files are useless on production anyway and they simply should not be there.',
        array(
          '@files' => implode(', ', $this->remaining_files),
        )),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['files'] = [
      'core/CHANGELOG.txt',
      'core/COPYRIGHT.txt',
      'core/INSTALL.mysql.txt',
      'core/INSTALL.pgsql.txt',
      'core/INSTALL.sqlite.txt',
      'core/INSTALL.txt',
      'core/LICENSE.txt',
      'core/MAINTAINERS.txt',
      'README.txt',
      'core/UPGRADE.txt',
      'themes/README.txt',
      'modules/README.txt',
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['files'] = array(
      '#type' => 'textarea',
      '#title' => t('Files to check'),
      '#default_value' => implode("\n", $this->configuration['files']),
      '#rows' => 20,
      '#required' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $files = $form_state->getValue('files');

    $files = explode("\n", $files);
    // Ensure that all strings are trimmed, eg. don't have extra spaces,
    // \r chars etc.
    foreach ($files as $k => $v) {
      $files[$k] = trim($v);
    }

    $this->configuration['files'] = $files;
  }

}
