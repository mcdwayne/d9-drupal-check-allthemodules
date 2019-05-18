<?php

namespace Drupal\olark\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Configures Olark settings for this site.
 */
class OlarkSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'olark_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('olark.settings');

    foreach (Element::children($form) as $variable) {
      if (strpos($variable, 'olark') === 0) {
        $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
      }
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
    return ['olark.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = \Drupal::config('olark.settings');
    $form['olark_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Olark'),
      '#default_value' => $settings->get('olark_enable', TRUE),
      '#description' => $this->t('Enable / disable Olark integration for this site.'),
    ];

    $form['olark_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Olark code'),
      '#description' => $this->t('Paste the Javascript code block from <a href="http://olark.com/install">Olark.com</a>'),
      '#default_value' => $settings->get('olark_code'),
      '#attributes' => ['placeholder' => '<!-- begin olark code -->'],
    ];
    $form['olark_ios'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable in iOS devices'),
      '#description' => $this->t('Hides it on iPhone, iPad and iPod since it has issues in this platforms.'),
      '#default_value' => $settings->get('olark_ios'),
    ];
    $form['olark_enable_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable on admin pages.'),
      '#description' => $this->t('Embeds the olark code on admin pages.'),
      '#default_value' => $settings->get('olark_enable_admin'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
