<?php

/**
 * @file
 * Contains \Drupal\yplog\Form\YplogSettings.
 */

namespace Drupal\yplog\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class YplogSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yplog_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('yplog.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['yplog.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['yplog_font'] = [
      '#type' => 'textfield',
      '#title' => t('Graph font'),
      '#description' => t('Enter the path to a font file on the server file system.'),
      '#default_value' => \Drupal::config('yplog.settings')->get('yplog_font'),
    ];
    return parent::buildForm($form, $form_state);
  }

}
