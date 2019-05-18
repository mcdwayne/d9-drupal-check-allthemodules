<?php

namespace Drupal\drd\Crypt\Method;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Crypt\BaseMethod;

/**
 * Provides security over TLS without additional encryption.
 *
 * @ingroup drd
 */
class Tls extends BaseMethod {

  /**
   * {@inheritdoc}
   */
  public function authBeforeDecrypt() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresPassword() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return 'TLS';
  }

  /**
   * {@inheritdoc}
   */
  public function getCipher() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    try {
      if (function_exists('module_exists')) {
        // We are not on D8.
      }
      elseif (\Drupal::moduleHandler()->moduleExists('drd')) {
        // We are on the dashboard and not remote, so here we do support TLS.
        return TRUE;
      }
    }
    catch (\Exception $ex) {

    }
    if (function_exists('variable_get')) {
      // This is on Drupal 7 or earlier.
      return variable_get('https', FALSE);
    }
    return \Drupal::config('https');
  }

  /**
   * {@inheritdoc}
   */
  public function getCipherMethods() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, array $condition) {
    $form['info'] = array(
      '#type' => 'item',
      '#markup' => t('No settings required.'),
      '#states' => array(
        'required' => $condition,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValues(FormStateInterface $form_state) {
    return $this->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getIv() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt(array $args) {
    return serialize($args);
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($body, $iv) {
    return unserialize($body);
  }

}
