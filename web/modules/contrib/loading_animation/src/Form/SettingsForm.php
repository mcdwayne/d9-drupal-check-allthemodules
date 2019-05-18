<?php

namespace Drupal\loading_animation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'loading_animation_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['loading_animation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('loading_animation.settings');
    $form['show_on_form_submit'] = [
      '#type' => 'checkbox',
      '#title' => t('Show after form submission'),
      '#description' => t('Display the loading animation on form submit. <em>Important:</em> Buttons with further JS events registered are excluded from this trigger.'),
      '#default_value' => $config->get('show_on_form_submit')
    ];
    $form['show_on_href'] = [
      '#type' => 'checkbox',
      '#title' => t('Show on href click'),
      '#description' => t('Display the loading animation after a link has been clicked.'),
      '#default_value' => $config->get('show_on_href')
    ];
    $form['subselector'] = [
      '#type' => 'textfield',
      '#title' => t('Subselector'),
      '#description' => t('You may optionally use this subselector to reduce the DOM context which to register loading animation to.'),
      '#required' => FALSE,
      '#default_value' => $config->get('subselector')
    ];
    $form['path_match_exclude'] = [
      '#type' => 'textarea',
      '#title' => t('Exclude the following path(s)'),
      '#description' => t("Loading animation will not be used for paths that match these patterns. Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.'"),
      '#required' => TRUE,
      '#default_value' => $config->get('path_match_exclude')
    ];
    $form['close_on_click'] = [
      '#type' => 'checkbox',
      '#title' => t('Close on layer click'),
      '#description' => t('Close the layer if user clicks on it while loading process is still in progress.'),
      '#default_value' => $config->get('close_on_click')
    ];
    $form['close_on_esc'] = [
      '#type' => 'checkbox',
      '#title' => t('Close on ESC press'),
      '#description' => t('Close the layer if user presses ESC while loading process is still in progress.'),
      '#default_value' => $config->get('close_on_esc')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('loading_animation.settings')
      ->set('show_on_form_submit', $form_state->getValue('show_on_form_submit'))
      ->set('show_on_href', $form_state->getValue('show_on_href'))
      ->set('subselector', $form_state->getValue('subselector'))
      ->set('path_match_exclude', $form_state->getValue('path_match_exclude'))
      ->set('close_on_click', $form_state->getValue('close_on_click'))
      ->set('close_on_esc', $form_state->getValue('close_on_esc'))
      ->save();
  }

}
