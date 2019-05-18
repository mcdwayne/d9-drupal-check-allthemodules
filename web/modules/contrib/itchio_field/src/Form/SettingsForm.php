<?php

namespace Drupal\itchio_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\itchio_field\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'itchio_field.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('itchio_field.settings');

    $form['explanation'] = [
      '#markup' => 'Setting values used by Itch.io iframes by default. Values explicitly set on the field will override these settings.'
    ];

    $form['default_linkback'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default link-back to Itch.io page value'),
      '#default_value' => $config->get('default_linkback'),
    ];

    $form['default_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Width'),
      '#default_value' => $config->get('default_width'),
      '#size' => 4,
    ];
    $form['default_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Height'),
      '#default_value' => $config->get('default_height'),
      '#size' => 4,
    ];

    $form['default_bg_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Background Color'),
      '#size' => 6,
      '#default_value' => $config->get('default_bg_color'),
    ];
    $form['default_fg_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Foreground Color'),
      '#size' => 6,
      '#default_value' => $config->get('default_fg_color'),
    ];
    $form['default_link_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Link Color'),
      '#size' => 6,
      '#default_value' => $config->get('default_link_color'),
    ];

    $form['default_borderwidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Border Width'),
      '#default_value' => $config->get('default_borderwidth'),
      '#size' => 1,
    ];
    $form['default_border_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Border Color'),
      '#size' => 6,
      '#default_value' => $config->get('default_border_color'),
    ];

    $form['default_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Itch.io username'),
      '#description' => $this->t('This value will be automatically filled for every Itch JS API button'),
      '#default_value' => $config->get('default_username'),
    ];

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
    parent::submitForm($form, $form_state);

    $this->config('itchio_field.settings')
      ->set('default_linkback', $form_state->getValue('default_linkback'))
      ->set('default_width', $form_state->getValue('default_width'))
      ->set('default_height', $form_state->getValue('default_height'))
      ->set('default_bg_color', $form_state->getValue('default_bg_color'))
      ->set('default_fg_color', $form_state->getValue('default_fg_color'))
      ->set('default_link_color', $form_state->getValue('default_link_color'))
      ->set('default_borderwidth', $form_state->getValue('default_borderwidth'))
      ->set('default_border_color', $form_state->getValue('default_border_color'))
      ->set('default_username', $form_state->getValue('default_username'))
      ->save();
  }

}
