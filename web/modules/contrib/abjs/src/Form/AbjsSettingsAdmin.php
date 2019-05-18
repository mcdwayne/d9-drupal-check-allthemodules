<?php

namespace Drupal\abjs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Build form for settings module.
 */
class AbjsSettingsAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'abjs_settings_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['abjs.settings'];
  }

  /**
   * Building form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('abjs.settings');
    // Each applicable test will have one cookie. The cookie prefix will prefix
    // the name of all test cookies.
    $form['cookie_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie Prefix'),
      '#default_value' => $config->get('cookie.prefix'),
      '#description' => $this->t('This string will prefix all A/B test cookie names'),
      '#size' => 10,
      '#maxlength' => 10,
    ];
    $form['cookie_lifetime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie Lifetime'),
      '#description' => $this->t('Enter cookie lifetime in days'),
      '#default_value' => $config->get('cookie.lifetime'),
      '#size' => 4,
      '#maxlength' => 10,
    ];
    $form['cookie_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie Domain'),
      '#description' => $this->t('Enter the domain to which the test cookies will be set, e.g. example.com. Leave blank to set the cookies to the domain of the page where the tests are occurring.'),
      '#default_value' => $config->get('cookie.domain'),
      '#size' => 50,
      '#maxlength' => 100,
    ];
    $form['cookie_secure'] = [
      '#type' => 'select',
      '#title' => $this->t('Use Secure Cookies?'),
      '#description' => $this->t('This sets the secure flag on A/B test cookies'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('cookie.secure'),
    ];
    $form['ace'] = [
      '#type' => 'select',
      '#title' => $this->t('Use Ace Code Editor?'),
      '#description' => $this->t('Use Ace Code Editor for entering Condition and Experience scripts. If chosen, it will be loaded via https://cdnjs.cloudflare.com.'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('ace'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('abjs.settings')
      ->set('cookie.prefix', $form_state->getValue('cookie_prefix'))
      ->set('cookie.lifetime', $form_state->getValue('cookie_lifetime'))
      ->set('cookie.domain', $form_state->getValue('cookie_domain'))
      ->set('cookie.secure', $form_state->getValue('cookie_secure'))
      ->set('ace', $form_state->getValue('ace'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
