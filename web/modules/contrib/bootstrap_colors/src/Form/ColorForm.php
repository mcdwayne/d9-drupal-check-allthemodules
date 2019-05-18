<?php

/**
 * @file
 * Contains \Drupal\bootstrap_colors\Form\ColorForm.
 */

namespace Drupal\bootstrap_colors\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ColorForm extends FormBase {
  
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'bootstrap_colors_color_form';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'clearfix';
    $form['primary_shade'] = array(
     '#type' => 'textfield',
     '#title' => t('Primary shade'),
     '#size' => 20,
     '#maxlength' => 7,
     '#attributes' => array('id' => array('primary-shade'), 'class' => array('white')),
     '#required' => TRUE,
    );
    $form['primary_light'] = array(
     '#type' => 'textfield',
     '#title' => t('Primary light'),
     '#size' => 20,
     '#maxlength' => 7,
     '#attributes' => array('id' => array('primary-light'), 'class' => array()),
     '#required' => TRUE,
    );
    $form['primary_dark'] = array(
     '#type' => 'textfield',
     '#title' => t('Primary dark'),
     '#size' => 20,
     '#maxlength' => 7,
     '#attributes' => array('id' => array('primary-dark'), 'class' => array('white')),
     '#required' => TRUE,
    );
    $form['accent_shade'] = array(
     '#type' => 'textfield',
     '#title' => t('Accent shade'),
     '#size' => 20,
     '#maxlength' => 7,
     '#attributes' => array('id' => array('accent-shade'), 'class' => array('white')),
     '#required' => TRUE,
    );
    $form['accent_light'] = array(
     '#type' => 'textfield',
     '#title' => t('Accent light'),
     '#size' => 20,
     '#maxlength' => 7,
     '#attributes' => array('id' => array('accent-light'), 'class' => array()),
     '#required' => TRUE,
    );
    $form['accent_dark'] = array(
     '#type' => 'textfield',
     '#title' => t('Accent dark'),
     '#size' => 20,
     '#maxlength' => 7,
     '#attributes' => array('id' => array('accent-dark'), 'class' => array('white')),
     '#required' => TRUE,
    );
    $form['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    $form['cancel'] = array(
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#attributes' => array('class' => array('btn-secundary', 'btn')),
      '#submit' => array('_bootstrap_colors_cancel_submit'),
    );
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $primary_shade = $form_state->getValue('primary_shade');
    $primary_light = $form_state->getValue('primary_light');
    $primary_dark = $form_state->getValue('primary_dark');
    $accent_shade = $form_state->getValue('accent_shade');
    $accent_light = $form_state->getValue('accent_light');
    $accent_dark = $form_state->getValue('accent_dark');
    $body_bg = "white";
	$new_sass_variables = array(
      'brand-primary' => $accent_shade,
      '$body-bg' => $body_bg,
      '$btn-secondary-color' => $primary_shade,
      '$btn-secondary-bg' => $primary_light,
      '$btn-secondary-border' => $primary_dark,
      '$navbar-dark-color' => $accent_shade,
      '$navbar-dark-hover-color' => $accent_light,
      '$navbar-dark-active-color' => $accent_light,
      '$navbar-dark-disabled-color' => $accent_light,
      '$navbar-light-color' => $accent_light,
      '$navbar-light-hover-color' => $accent_dark,
      '$navbar-light-active-color' => $accent_dark,
      '$navbar-light-disabled-color' => $accent_dark,
      '$jumbotron-bg' => $accent_light,
    );
    $config = \Drupal::configFactory()->getEditable('bootstrap_library.settings');
    $old_sass_variables = $config->get('sass.variables');
    $sass_variables = isset($old_sass_variables) ? array_merge($old_sass_variables, $new_sass_variables) : $new_sass_variables;
    $config->set('sass', array("variables" => $sass_variables));
    $config->save();
    $config_colors = \Drupal::configFactory()->getEditable('bootstrap_colors.settings');
    $config_colors->set('primary_shade', $primary_shade);
    $config_colors->set('accent_shade', $accent_shade);
    $config_colors->save();
  }

  private function _bootstrap_colors_cancel_submit() {
    location.reload();
  }

}
