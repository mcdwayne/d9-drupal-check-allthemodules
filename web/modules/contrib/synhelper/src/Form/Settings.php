<?php

namespace Drupal\synhelper\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\synhelper\Controller\AjaxResult;
use Drupal\synhelper\Controller\MenuFix;

/**
 * Implements the form controller.
 */
class Settings extends ConfigFormBase {
  /**
   * AJAX Wrapper.
   *
   * @var wrapper
   */
  private $wrapper = 'synhelper-results';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'synhelper';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['synhelper.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('synhelper.settings');
    $form['#suffix'] = '<div id="' . $this->wrapper . '"></div>';
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];
    $form["general"]['no-index'] = [
      '#title' => $this->t('Search engines indexing is forbidden'),
      '#description' => $this->t('Make sure to remove the production site'),
      '#type' => 'checkbox',
      '#maxlength' => 20,
      '#required' => FALSE,
      '#size' => 15,
      '#default_value' => $config->get('no-index'),
    ];
    $form["general"]['fz152'] = [
      '#title' => $this->t('FZ-152 checkbox'),
      '#description' => $this->t('Consent checkbox will displayed with all contact-module forms'),
      '#type' => 'checkbox',
      '#maxlength' => 20,
      '#required' => FALSE,
      '#size' => 15,
      '#default_value' => $config->get('fz152'),
    ];
    $form["general"]['style-page'] = [
      '#title' => $this->t('Styles page'),
      '#description' => $this->t('The styles page is available') . ' <a href="/demo-page">Demo</a>',
      '#type' => 'checkbox',
      '#maxlength' => 20,
      '#required' => FALSE,
      '#size' => 15,
      '#default_value' => $config->get('style-page'),
    ];

    $form['contact'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact forms'),
      '#open' => TRUE,
    ];
    $form['contact']['ya_counter'] = [
      '#title' => $this->t('Yandex Counter ID'),
      '#default_value' => $config->get('ya-counter'),
      '#maxlength' => 20,
      '#size' => 15,
      '#type' => 'textfield',
    ];
    $form['contact']['ya_goals'] = [
      '#title' => $this->t('Goals'),
      '#default_value' => $config->get('ya-goals'),
      '#type' => 'textarea',
      '#description' => 'goal|form id',
    ];
    $form['contact']['show_form_id'] = [
      '#title' => $this->t('Display form id'),
      '#default_value' => $config->get('show-ids'),
      '#type' => 'checkbox',
    ];
    $form['contact']['show_debug'] = [
      '#title' => $this->t('Debug mode'),
      '#default_value' => $config->get('debug'),
      '#type' => 'checkbox',
    ];

    $form['menu'] = [
      '#type' => 'details',
      '#title' => $this->t('Menu helper'),
      '#open' => TRUE,
    ];
    $form['menu']['actions']['build'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rebuild Editor Menu'),
      '#attributes' => ['class' => ['btn', 'btn-xs']],
      '#ajax'   => [
        'callback' => '::ajaxMenuBuild',
        'effect'   => 'fade',
        'progress' => ['type' => 'throbber', 'message' => ""],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * F ajaxMenuBuild.
   */
  public function ajaxMenuBuild(array &$form, $form_state) {
    $otvet = "ajaxMenuBuild:\n";
    $otvet .= MenuFix::editor();
    return AjaxResult::ajax($this->wrapper, $otvet);
  }

  /**
   * Implements a form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('synhelper.settings');
    $config
      ->set('no-index', $form_state->getValue('no-index'))
      ->set('fz152', $form_state->getValue('fz152'))
      ->set('style-page', $form_state->getValue('style-page'))
      ->set('ya-counter', $form_state->getValue('ya_counter'))
      ->set('ya-goals', $form_state->getValue('ya_goals'))
      ->set('show-ids', $form_state->getValue('show_form_id'))
      ->set('debug', $form_state->getValue('show_debug'))
      ->save();
  }

}
