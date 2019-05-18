<?php

namespace Drupal\headroomjs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class HeadroomSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['headroomjs.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'headroomjs_settings';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('headroomjs.settings');

    $form['headroomjs'] = array(
      '#type' => 'details',
      '#title' => $this->t('Headroom.js Settings'),
      '#open' => true,
      '#description' => $this->t('Configure default settings for Headroom.js.')
    );

    $form['headroomjs']['enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Headroom.js'),
      '#description' => $this->t('Enable Headroom.js functionality on your site.'),
      '#default_value' => $config->get('enable'),
    );

    $form['headroomjs']['offset'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Offset'),
      '#description' => $this->t('The vertical offset (in pixels) before the headroom object is first unpinned.'),
      '#default_value' => $config->get('offset'),
      '#required' => true
    );

    $form['headroomjs']['tolerance'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Tolerance'),
      '#description' => $this->t('The scroll tolerance (in pixels) before state changes.'),
      '#default_value' => $config->get('tolerance'),
      '#required' => true
    );

    $form['headroomjs']['selector'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('HTML Element'),
      '#description' => $this->t('The HTML element that Headroom.js should attach to. Defaults to the header tag.'),
      '#default_value' => $config->get('selector'),
      '#required' => true
    );

    $form['headroomjs']['initial_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Initial Class'),
      '#description' => $this->t('The CSS class applied to the HTML element when Headroom.js is initialized.'),
      '#default_value' => $config->get('initial_class'),
      '#required' => true
    );

    $form['headroomjs']['pinned_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pinned Class'),
      '#description' => $this->t('The CSS class applied to the HTML element when the headroom object is pinned.'),
      '#default_value' => $config->get('pinned_class'),
      '#required' => true
    );

    $form['headroomjs']['unpinned_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Unpinned Class'),
      '#description' => $this->t('The CSS class applied to the HTML element when the headroom object is unpinned.'),
      '#default_value' => $config->get('unpinned_class'),
      '#required' => true
    );

    $form['headroomjs']['top_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Top Class'),
      '#description' => $this->t('The CSS class applied to the HTML element when the headroom object is above the offset.'),
      '#default_value' => $config->get('top_class'),
      '#required' => true
    );

    $form['headroomjs']['not_top_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Not Top Class'),
      '#description' => $this->t('The CSS class applied to the HTML element when the headroom object is below the offset.'),
      '#default_value' => $config->get('not_top_class'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('headroomjs.settings')
      ->set('enable', $form_state->getValue('enable'))
      ->set('offset', (int) $form_state->getValue('offset'))
      ->set('tolerance', (int) $form_state->getValue('tolerance'))
      ->set('tolerance_up', (int) $form_state->getValue('tolerance_up'))
      ->set('tolerance_down', (int) $form_state->getValue('tolerance_down'))
      ->set('selector', $form_state->getValue('selector'))
      ->set('initial_class', $form_state->getValue('initial_class'))
      ->set('pinned_class', $form_state->getValue('pinned_class'))
      ->set('unpinned_class', $form_state->getValue('unpinned_class'))
      ->set('top_class', $form_state->getValue('top_class'))
      ->set('not_top_class', $form_state->getValue('not_top_class'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}