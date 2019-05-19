<?php

namespace Drupal\social_stats\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Class SocialStatsCronConfigForm
 * @package Drupal\social_stats\Form
 *
 * Social stats cron settings form.
 */
class SocialStatsCronConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'social_stats.cron_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_stats_cron_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config_social_stats = $this->configFactory->get('social_stats.cron_settings');

    // Loop over all the content types to build the config array per content-type.
    $form['basic_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Basic Settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => FALSE,
      '#weight' => -1,
    );

    $form['basic_settings']['social_stats_url_root'] = array(
      '#title' => t('URL Root'),
      '#type' => 'textfield',
      '#description' => t('Root of the URL to check against social media. For instance, "https://mysite.com" (no trailing slash). This is useful for fetching production site stats from a different environment.'),
      '#default_value' => $config_social_stats->get('url_root', ''),
    );

    $form['basic_settings']['social_stats_date_options'] = array(
      '#type' => 'radios',
      '#title' => t('Select options'),
      '#options' => array('Start Date', 'Offset'),
      '#default_value' => $config_social_stats->get('date_options', 0),
    );

    $form['basic_settings']['social_stats_start_date'] = array(
      '#title' => t('Start Date (MM/DD/YYYY)'),
      '#type' => 'textfield',
      '#maxlength' => 20,
      '#attributes' => array('class' => array('pickadate')),
      '#default_value' => $config_social_stats->get('start_date', '01/01/1970'),
      '#description' => t('The oldest date from which the statistics should be retrieved.'),
      '#states' => array(
        'visible' => array(
          ':input[name="social_stats_date_options"]' => array('value' => 0),
        ),
      ),
    );

    $form['basic_settings']['social_stats_date_offset'] = array(
      '#title' => t('Date Offset'),
      '#type' => 'textfield',
      '#maxlength' => 20,
      '#size' => 20,
      '#default_value' => $config_social_stats->get('date_offset', '-100 days'),
      '#description' => t('The days offset from which the stats should be retrieved.'),
      '#states' => array(
        'visible' => array(
          ':input[name="social_stats_date_options"]' => array('value' => 1),
        ),
      ),
    );
    $form['configuration'] = array(
      '#type' => 'fieldset',
      '#title' => t('Cron configuration'),
      '#weight' => 0,
    );
    $form['configuration']['social_stats_cron_interval'] = array(
      '#type' => 'select',
      '#title' => t('Cron interval'),
      '#description' => t('Time after which social data should be collected.'),
      '#default_value' => $config_social_stats->get('cron_interval', 60 * 60 * 24),
      '#options' => array(
        60 => t('1 minute'),
        300 => t('5 minutes'),
        3600 => t('1 hour'),
        60 * 60 * 6 => t('6 hours'),
        60 * 60 * 12 => t('12 hours'),
        60 * 60 * 24 => t('1 day'),
        60 * 60 * 24 * 7 => t('1 week'),
        60 * 60 * 24 * 7 * 2 => t('2 weeks'),
        60 * 60 * 24 * 7 * 4 => t('1 month'),
      ),
    );

    $form['configuration']['social_stats_cron_duration'] = array(
      '#type' => 'textfield',
      '#title' => t('Cron duration'),
      '#description' => t('Time (in secs) for which the queue should execute.'),
      '#default_value' => $config_social_stats->get('cron_duration', 300),
      '#size' => 3,
      '#maxlength' => 3,
    );

    $form['#attached'] = array(
      'library' => array(
        'social_stats/social_stats',
      ),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user_input_values = $form_state->getUserInput();
    $url_root = $user_input_values['social_stats_url_root'];

    // Find if the URL entered by the user is valid.
    $is_valid_url = UrlHelper::isValid($url_root);
    if (!$is_valid_url) {
      $form_state->setErrorByName('social_stats_url_root', $this->t("Please enter a valid URL."));
    }

    // Find if the URL has trailing slash.
    $last_char = substr($url_root, -1);
    if ($last_char == '/') {
      $form_state->setErrorByName('social_stats_url_root', $this->t("The URL should not contain the trailing slash"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('social_stats.cron_settings');
    $config->set('url_root', $values['social_stats_url_root']);
    $config->set('date_options', $values['social_stats_date_options']);
    $config->set('start_date', $values['social_stats_start_date']);
    $config->set('date_offset', $values['social_stats_date_offset']);
    $config->set('cron_interval', $values['social_stats_cron_interval']);
    $config->set('cron_duration', $values['social_stats_cron_duration']);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
