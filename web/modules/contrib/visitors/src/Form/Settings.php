<?php

namespace Drupal\visitors\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\DateHelper;
use Drupal\Core\Url;

class Settings extends ConfigFormBase {
  public function getFormID() {
    return 'visitors_admin_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('visitors.config');
    $form = array();

    $form['settings'] = array(
      '#type' => 'fieldset',
      '#weight' => -30,
      '#title' => t('Visitors block'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => t('Visitors block settings')
    );

    $form['settings']['show_total_visitors'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Total Visitors'),
      '#default_value' => $config->get('show_total_visitors'),
      '#description' => t('Show Total Visitors.')
    );
    $form['settings']['start_count_total_visitors'] = array(
      '#type' => 'textfield',
      '#title' => t('Total visitors start count'),
      '#default_value' => $config->get('start_count_total_visitors'),
      '#description' => t('Start the count of the total visitors at this number. Useful for including the known number of visitors in the past.')
    );

    $form['settings']['show_unique_visitor'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Unique Visitors'),
      '#default_value' => $config->get('show_unique_visitor'),
      '#description' => t('Show Unique Visitors based on their IP.')
    );

    $form['settings']['show_registered_users_count'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Registered Users Count'),
      '#default_value' => $config->get('show_registered_users_count'),
      '#description' => t('Show Registered Users.')
    );

    $form['settings']['show_last_registered_user'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Last Registered User'),
      '#default_value' => $config->get('show_last_registered_user'),
      '#description' => t('Show Last Registered User.')
    );

    $form['settings']['show_published_nodes'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Published Nodes'),
      '#default_value' => $config->get('show_published_nodes'),
      '#description' => t('Show Published Nodes.')
    );

    $form['settings']['show_user_ip'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show User IP'),
      '#default_value' => $config->get('show_user_ip'),
      '#description' => t('Show User IP.')
    );

    $form['settings']['show_since_date'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Since Date'),
      '#default_value' => $config->get('show_since_date'),
      '#description' => t('Show Since Date.')
    );

    // Statistics settings.
    $form['statistics'] = array(
      '#type' => 'fieldset',
      '#title' => t('Visitors statistics'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => t('Visitors statistics settings')
    );

    $form['statistics']['exclude_administer_users'] = array(
      '#type' => 'checkbox',
      '#title' => t('Exclude administer users from statistics'),
      '#default_value' => $config->get('exclude_administer_users'),
      '#description' => t('Exclude hits of administer users from statistics.')
    );

    $form['statistics']['items_per_page'] = array(
      '#type' => 'select',
      '#title' => 'Items per page',
      '#default_value' => $config->get('items_per_page'),
      '#options' => array(
        5 => 5,
        10 => 10,
        25 => 25,
        50 => 50,
        100 => 100,
        200 => 200,
        250 => 250,
        500 => 500,
        1000 => 1000
      ),
      '#description' =>
        t('The default maximum number of items to display per page.'),
    );


    $form['statistics']['flush_log_timer'] = array(
      '#type' => 'select',
      '#title' => t('Discard visitors logs older than'),
      '#default_value'   => $config->get('flush_log_timer'),
      '#options' => array(
        0 => 'Never',
        3600 => '1 hour',
        10800 => '3 hours',
        21600 => '6 hours',
        32400 => '9 hours',
        43200 => '12 hours',
        86400 => '1 day',
        172800 => '2 days',
        259200 => '3 days',
        604800 => '1 week',
        1209600 => '2 weeks',
        2419200 => '4 weeks',
        4838400 => '1 month 3 weeks',
        9676800 => '3 months 3 weeks',
        31536000 => '1 year',
      ),
      '#description' =>
        $this->t('Older visitors log entries (including referrer statistics) will be ' .
          'automatically discarded. (Requires a correctly configured ' .
          '<a href="@cron">cron maintenance task</a>.)',
          array('@cron' => Url::fromRoute('system.status')->toString())
        )
    );

    // Chart settings.
    $form['chart_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Chart settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => t('Visitors chart settings')
    );

    $form['chart_settings']['chart_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Width'),
      '#default_value' => $config->get('chart_width'),
      '#description' => t('Chart width.')
    );

    $form['chart_settings']['chart_height'] = array(
      '#type' => 'textfield',
      '#title' => t('Height'),
      '#default_value' => $config->get('chart_height'),
      '#description' => t('Chart height.')
    );

    return parent::buildForm($form, $form_state);;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('visitors.config');
    $config
      ->set(
        'show_total_visitors',
        (int) $form_state->getValue('show_total_visitors')
      )
      ->set(
        'start_count_total_visitors',
        (int) $form_state->getValue('start_count_total_visitors')
      )
      ->set(
        'show_unique_visitor',
        (int) $form_state->getValue('show_unique_visitor')
      )
      ->set(
        'show_registered_users_count',
        (int) $form_state->getValue('show_registered_users_count')
      )
      ->set(
        'show_last_registered_user',
        (int) $form_state->getValue('show_last_registered_user')
      )
      ->set(
        'show_published_nodes',
        (int) $form_state->getValue('show_published_nodes')
      )
      ->set(
        'show_user_ip',
        (int) $form_state->getValue('show_user_ip')
      )
      ->set(
        'show_since_date',
        (int) $form_state->getValue('show_since_date')
      )
      ->set(
        'exclude_administer_users',
        (int) $form_state->getValue('exclude_administer_users')
      )
      ->set(
        'items_per_page',
        (int) $form_state->getValue('items_per_page')
      )
      ->set(
        'flush_log_timer',
        (int) $form_state->getValue('flush_log_timer')
      )
      ->set(
        'chart_width',
        (int) $form_state->getValue('chart_width')
      )
      ->set(
        'chart_height',
        (int) $form_state->getValue('chart_height')
      )
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return array();
  }
}

