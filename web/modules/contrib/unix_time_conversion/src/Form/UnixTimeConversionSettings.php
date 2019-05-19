<?php

/**
 * @file
 * Contains Drupal\unix_time_conversion\Form\UnixTimeConversionSettings
 */

namespace Drupal\unix_time_conversion\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class UnixTimeConversionSettings.
 *
 * @package Drupal\unix_time_conversion\Form\UnixTimeConversionSettings
 */
class UnixTimeConversionSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'unix_time_conversion.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'unix_time_conversion_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    $config = \Drupal::configFactory()->getEditable('unix_time_conversion.settings');
    // Include the helper functions file.
    module_load_include('inc', 'unix_time_conversion', 'unix_time_conversion.helper_functions');
    $form = array();
    /*
     * Timestamp To Date.
     */
    $form['unix_time_conversion_time_to_date'] = array(
      '#type' => 'fieldset',
      '#title' => t('Timestamp To Date'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    // Timestamp field title.
    $form['unix_time_conversion_time_to_date']['unix_time_conversion_timestamp_field_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Timestamp Field Title'),
      '#description' => t('Will serve as field title for input timestamp field. Ex: Timestamp.'),
      '#required' => TRUE,
      '#default_value' => $config->get('unix_time_conversion_timestamp_field_title'),
    );
    // Timestamp field description.
    $form['unix_time_conversion_time_to_date']['unix_time_conversion_timestamp_field_description'] = array(
      '#type' => 'textfield',
      '#title' => t('Timestamp Field Description'),
      '#description' => t('Will serve as field description for input timestamp field.'),
      '#default_value' => $config->get('unix_time_conversion_timestamp_field_description'),
    );
    $url = Url::fromUri('http://php.net/manual/en/function.date.php');
    $link_options = array('attributes' => array('target' => '_blank'));
    $url->setOptions($link_options);
    $link = Link::fromTextAndUrl(t('php manual'), $url)->toString();

    // Output format.
    $form['unix_time_conversion_time_to_date']['unix_time_conversion_time_to_date_output_format'] = array(
      '#title' => t('Date Output Format'),
      '#type' => 'textfield',
      '#description' => t('A user-defined date format. See the !phpmanual for available options.', array(
        '!phpmanual' => $link,
          )
      ),
      '#required' => TRUE,
      '#default_value' => $config->get('unix_time_conversion_time_to_date_output_format'),
    );
    
    // Date To Timestamp.
    
    $form['unix_time_conversion_date_to_time'] = array(
      '#type' => 'fieldset',
      '#title' => t('Date To Timestamp'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    
    // Date field title.
    $form['unix_time_conversion_date_to_time']['unix_time_conversion_date_field_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Date Field Title'),
      '#description' => t('Will serve as field title for input date field. Ex: Date.'),
      '#required' => TRUE,
      '#default_value' => $config->get('unix_time_conversion_date_field_title'),
    );
    
    // Time field title.
    $form['unix_time_conversion_date_to_time']['unix_time_conversion_time_field_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Time Field Title'),
      '#description' => t('Will serve as field title for input time field. Ex: Time.'),
      '#required' => TRUE,
      '#default_value' => $config->get('unix_time_conversion_time_field_title'),
    );
    return parent::buildForm($form, $form_state);
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
    \Drupal::configFactory()->getEditable('unix_time_conversion.settings')
        ->set('unix_time_conversion_timestamp_field_title', $form_state->getValue('unix_time_conversion_timestamp_field_title'))
        ->set('unix_time_conversion_timestamp_field_description', $form_state->getValue('unix_time_conversion_timestamp_field_description'))
        ->set('unix_time_conversion_time_to_date_output_format', $form_state->getValue('unix_time_conversion_time_to_date_output_format'))
        ->set('unix_time_conversion_date_field_title', $form_state->getValue('unix_time_conversion_date_field_title'))
        ->set('unix_time_conversion_time_field_title', $form_state->getValue('unix_time_conversion_time_field_title'))
        ->save();

    return parent::submitForm($form, $form_state);
  }

}
