<?php

namespace Drupal\whitelabel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\whitelabel\PathProcessor\WhiteLabelPathProcessor;

/**
 * Form for the global configuration settings for white labels.
 */
class WhiteLabelConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'whitelabel_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'whitelabel.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('whitelabel.settings');

    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Detection mode'),
      '#default_value' => $config->get('mode'),
      '#description' => $this->t('The domain mode requires a white label DNS record and optionally a white label SSL certificate. <br>WARNING: CHANGING THIS DURING PRODUCTION WILL CAUSE EXISTING WHITE LABEL LINKS TO BREAK.'),
      '#options' => WhiteLabelPathProcessor::getModes(),
    ];

    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base domain'),
      '#default_value' => $config->get('domain') ?: \Drupal::request()->getHost(),
      '#description' => $this->t('When using subdomain-mode, this is used to determine the base domain. '),
      '#states' => [
        'visible' => [
          ':input[name="mode"]' => ['value' => WhiteLabelPathProcessor::CONFIG_DOMAIN],
        ],
        'required' => [
          ':input[name="mode"]' => ['value' => WhiteLabelPathProcessor::CONFIG_DOMAIN],
        ],
      ],
    ];

    $form['site_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to override the site name.'),
      '#default_value' => $config->get('site_name'),
      '#description' => $this->t('Allows users with the right permissions to alter the site name.'),
    ];

    $form['site_name_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to toggle the site name display.'),
      '#default_value' => $config->get('site_name_display'),
      '#description' => $this->t('Allows users with the right permissions to toggle the site name visibility. Allowing them to prevent the name from showing if it is already in their logo.'),
    ];

    $form['site_slogan'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to override the site slogan.'),
      '#default_value' => $config->get('site_slogan'),
      '#description' => $this->t('Allows users with the right permissions to alter the site slogan.'),
    ];

    $form['site_logo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to override the site logo.'),
      '#default_value' => $config->get('site_logo'),
      '#description' => $this->t('Allows users with the right permissions to alter the site logo.'),
    ];

    $form['site_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to override the theme.'),
      '#default_value' => $config->get('site_theme'),
      '#description' => $this->t('Allows users to override the theme from their white label configuration. (Only installed themes can be selected.)'),
    ];

    $form['site_colors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to override the site color scheme.'),
      '#default_value' => $config->get('site_colors'),
      '#description' => $this->t('Allows users with the right permissions to alter the theme color scheme. This requires the color module to work.'),
    ];

    $form['site_admin_theme'] = [
      '#type' => 'select',
      '#options' => whitelabel_load_available_themes(),
      '#title' => $this->t('White label theme'),
      '#description' => $this->t('Choose "Default theme" to always use the system default.'),
      '#default_value' => $config->get('site_theme'),
      '#empty_option' => $this->t('Default theme'),
    ];

    $form['commerce'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Drupal Commerce integration'),
    );
    $form['commerce']['store_resolver'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the Commerce store resolver.'),
      '#default_value' => $config->get('store_resolver'),
      '#description' => $this->t('Enabling this will active a commerce store that has the same owner as the active white label (if any).'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('whitelabel.settings');
    $config->set('mode', $form_state->getValue('mode'));
    $config->set('domain', $form_state->getValue('domain'));
    $config->set('site_name', $form_state->getValue('site_name'));
    $config->set('site_name_display', $form_state->getValue('site_name_display'));
    $config->set('site_slogan', $form_state->getValue('site_slogan'));
    $config->set('site_logo', $form_state->getValue('site_logo'));
    $config->set('site_theme', $form_state->getValue('site_theme'));
    $config->set('site_colors', $form_state->getValue('site_colors'));
    $config->set('site_admin_theme', $form_state->getValue('site_admin_theme'));
    $config->set('store_resolver', $form_state->getValue('store_resolver'));
    $config->save();
  }

}
