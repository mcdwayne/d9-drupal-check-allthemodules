<?php

namespace Drupal\alt_login;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for the module
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alt_login_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('alt_login.settings');
    $form['name_mode'] = [
      '#title' => $this->t('Generate login names henceforth using'),
      '#description' => $this->t('Not retroactive.'),
      '#type' => 'select',
      '#options' => [
        ALT_LOGIN_MODE_NORMAL => $this->t('User "name" field (Default)'),
        ALT_LOGIN_MODE_UID => $this->t('Database ID'),
      ],
      '#default_value' => $config->get('name_mode'),
      '#required' => TRUE,
      '#weight' => 1
    ];
    foreach (get_defined_functions()['user'] as $fname) {
      if (substr($fname, 0, 19) == 'alt_login_generate_') {
        $form['name_mode']['#options'][$fname] = 'callback: '.$fname;
      }
    }
    $form['display'] = [
      '#title' => $this->t('Display user name'),
      // TODO there seems to be some field token functionality seriously missing from drupal core
      '#description' => $this->t('Tokens available: @tokens.', ['@tokens' => '[user:uid] [user:account-name]']),
      '#type' => 'textfield',
      '#placeholder' => "[user:name]",
      '#default_value' => $config->get('display'),
      '#element_validate' => [[$this, 'validate_display_template']],
      '#weight' => 3
    ];
    if (\Drupal::moduleHandler()->moduleExists('token')) {
       $form['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['user'],
        '#weight' => 100,
      ];
    }
    else {
      $form['display']['#description'] .= $this->t('Install the token module for more options');
    }

    $form['display_anon'] = [
      '#title' => $this->t('Display user name to anonymous users'),
      '#description' => $this->t('Tokens available: @tokens.', ['@tokens' => '[user:uid]']),
      '#type' => 'textfield',
      '#default_value' => $config->get('display_anon'),
      //'#required' => TRUE,
      '#weight' => 3
    ];
    $form['login'] = [
      '#title' => $this->t('Allow login with'),
      '#type' => 'checkboxes',
      '#options' => [
        ALT_LOGIN_WITH_USERNAME => t('The username'),
        ALT_LOGIN_WITH_UID => t('The user id'),
        ALT_LOGIN_WITH_EMAIL => t('Email')
      ],
      '#default_value' => $config->get('login'),
      '#weight' => 4,
      ALT_LOGIN_WITH_USERNAME => [// Disable this one because it is always true
        '#disabled' => TRUE,
        '#default_value' => TRUE
      ]
    ];
    return parent::buildForm($form, $form_state);
  }

  public function validate_display_template(&$element, FormStateInterface $form_state) {
    if (is_numeric(strpos($element['#value'], '[user:display-name]'))) {
       $form_state->setError($element, $this->t('[user:display-name] would create recursion problems here!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('alt_login.settings')
      ->set('name_mode', $form_state->getValue('name_mode'))
      ->set('name_callback', $form_state->getValue('name_callback'))
      ->set('display', $form_state->getValue('display'))
      ->set('display_anon', $form_state->getValue('display_anon'))
      ->set('login', array_filter($form_state->getValue('login')))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['alt_login.settings'];
  }

}
