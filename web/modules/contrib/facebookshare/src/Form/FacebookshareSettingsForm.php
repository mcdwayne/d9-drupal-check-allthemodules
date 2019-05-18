<?php

/**
 * @file
 * Contains \Drupal\facebookshare\Form\FacebookshareSettingsForm.
 */

namespace Drupal\facebookshare\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Administration settings form.
 */
class FacebookshareSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'facebookshare_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['facebookshare.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all settings.
    $config = $this->config('facebookshare.settings');
    $settings = $config->get();

    $form['#attached']['library'] = array(
      'facebookshare/facebookshare.admin',
    );

    $form['facebookshare_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Content types'),
      '#description' => t('Which content types to apply the Facebook Share button to.'),
      '#options' => node_type_get_names(),
      '#default_value' => $settings['facebookshare_types'],
    );

    $form['facebookshare_location'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Location'),
      '#description' => t('Where to show the Facebook Share button.'),
      '#options' => array(
        'content' => t('Full view'),
        'teasers' => t('Teasers'),
      ),
      '#default_value' => $settings['facebookshare_location'],
    );

    $form['facebookshare_weight'] = array(
      '#type' => 'textfield',
      '#title' => t('Weight'),
      '#description' => t('The weight of which the Facebook widget should appear on the content.'),
      '#default_value' => $settings['facebookshare_weight'],
      '#size' => 5,
    );

    $form['facebookshare_app_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Admin ID'),
      '#default_value' => $settings['facebookshare_app_id'],
      '#description' => $this->t('Your Facebook Admin Username, ID or App ID. More than one admin can be separated by commas.'),
      '#required' => TRUE,
    );

    $form['facebookshare_size'] = array(
      '#type' => 'select',
      '#title' => t('Button size'),
      '#required' => TRUE,
      '#description' => t('What kind of button to show.'),
      '#options' => array(
        'small' => t('Small'),
        'large' => t('Large'),
      ),
      '#default_value' => $settings['facebookshare_size'],
    );

    $layouts = array(
      'box_count',
      'button_count',
      'button',
    );
    $options = array();
    foreach ($layouts as $layout) {
      $options[$layout] = '<img src="' . base_path() . drupal_get_path('module', 'facebookshare') . '/images/' . $layout . '.png">';
    }
    $form['facebookshare_layout'] = array(
      '#type' => 'radios',
      '#title' => t('Button size'),
      '#required' => TRUE,
      '#description' => t('What kind of button to show.'),
      '#options' => $options,
      '#default_value' => $settings['facebookshare_layout'],
    );
    $form['facebookshare_mobile_iframe'] = array(
      '#type' => 'checkbox',
      '#title' => t('Mobile Iframe'),
      '#default_value' => $settings['facebookshare_mobile_iframe'],
    );
    $form['facebookshare_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Width'),
      '#default_value' => $settings['facebookshare_width'],
      '#size' => 4,
      '#maxlength' => 4,
      '#required' => TRUE,
    );
    $form['facebookshare_height'] = array(
      '#type' => 'textfield',
      '#title' => t('Height'),
      '#default_value' => $settings['facebookshare_height'],
      '#size' => 4,
      '#maxlength' => 4,
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('facebookshare.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
