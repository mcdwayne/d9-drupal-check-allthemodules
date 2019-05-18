<?php

/**
 * @file
 * Admin settings form for setting default country.
 */

namespace Drupal\country_specific_nodes\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class CsnDefaultCountry extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'country_specific_nodes_content_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    $countries = \Drupal::service('country_manager')->getStandardList();
    $selected_country = \Drupal::config('country_specific_nodes.settings')->get('country_specific_nodes_def_cn');

    $form['country_specific_nodes_def_cn'] = array(
      '#title' => t('Default Country Code'),
      '#description' => t('Please select the default country for nodes.'),
      '#type' => 'select',
      '#options' => $countries,
      '#default_value' => $selected_country,
    );

    $form['country_specific_text'] = array(
      '#type' => 'item',
      '#markup' => t('This option helps in setting the default/fallback country for the user if in rare cases the users IP is not detected, Please specify the default country such as India, Japan, etc.'),
    );

    $form['country_specific_nodes_submit'] = array(
      '#value' => t('Save'),
      '#description' => t('Save the default country.'),
      '#type' => 'submit',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('country_specific_nodes_def_cn');

    $csn_config = \Drupal::getContainer()->get('config.factory')->getEditable('country_specific_nodes.settings');
    $csn_config->set('country_specific_nodes_def_cn', $value);
    $csn_config->save();
    drupal_set_message(t('Settings successfully saved.'), 'status');
  }

}
