<?php

namespace Drupal\spammaster\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class controller.
 */
class SpamMasterLogForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spammaster_settings_log_form';
  }

  /**
   * {@inheritdoc}
   */
  public function spammasterstatisticspage($form, &$form_state) {
    $spam_get_statistics = $form_state->getValue('statistics_header')['buttons']['addrow']['statistics'];
    if (!empty($spam_get_statistics)) {
      $spammaster_build_statistics_url = 'http://' . $_SERVER['SERVER_NAME'] . '/statistics';
      $spammaster_statistics_url = Url::fromUri($spammaster_build_statistics_url);
      $form_state->setRedirectUrl($spammaster_statistics_url);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spammasterfirewallpage($form, &$form_state) {
    $spam_get_firewall = $form_state->getValue('statistics_header')['buttons']['addrow']['firewall'];
    if (!empty($spam_get_firewall)) {
      $spammaster_build_firewall_url = 'http://' . $_SERVER['SERVER_NAME'] . '/firewall';
      $spammaster_firewall_url = Url::fromUri($spammaster_build_firewall_url);
      $form_state->setRedirectUrl($spammaster_firewall_url);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spammasterdeletekey($form, &$form_state) {
    $spam_form_key_delete = $form_state->getValue('statistics_header')['table_key'];
    $spammaster_key_date = date("Y-m-d H:i:s");
    foreach ($spam_form_key_delete as $spam_key_delete) {
      if (!empty($spam_key_delete)) {
        db_query('DELETE FROM {spammaster_keys} WHERE id = :row', [':row' => $spam_key_delete]);
        drupal_set_message(t('Saved Spam Master Log deletion.'));
        \Drupal::logger('spammaster-log')->notice('Spam Master: log deletion, Id: ' . $spam_key_delete);
        $spammaster_db_key_delete = db_insert('spammaster_keys')->fields([
          'date' => $spammaster_key_date,
          'spamkey' => 'spammaster-log',
          'spamvalue' => 'Spam Master: log deletion, Id: ' . $spam_key_delete,
        ])->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spammasterdeletekeysall() {
    \Drupal::configFactory()->getEditable('spammaster.settings_log')
      ->set('spammaster.total_block_count', '0')
      ->save();
    $spammaster_db_keys_truncate = db_truncate('spammaster_keys')->execute();
    drupal_set_message(t('Saved Spam Master Statistics & Logs full deletion.'));
    \Drupal::logger('spammaster-log')->notice('Spam Master: Statistics & Logs full deletion.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Default settings.
    $config = $this->config('spammaster.settings');

    $form['statistics_header'] = [
      '#type' => 'details',
      '#title' => $this->t('<h3>Statistics & Log</h3>'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    // Create buttons table.
    $form['statistics_header']['buttons'] = [
      '#type' => 'table',
      '#header' => [],
    ];
    // Insert addrow statistics button.
    $form['statistics_header']['buttons']['addrow']['statistics'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => t('Visit your Statistics Page'),
      '#submit' => ['::spammasterstatisticspage'],
    ];
    // Insert addrow firewall button.
    $form['statistics_header']['buttons']['addrow']['firewall'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => t('Visit your Firewall Page'),
      '#submit' => ['::spammasterfirewallpage'],
    ];

    $spammaster_total_block_count = $config->get('spammaster.total_block_count');
    if (empty($spammaster_total_block_count)) {
      $spammaster_total_block_count = '0';
    }
    // Insert statistics table inside tree.
    $form['statistics_header']['total_block_count'] = [
      '#markup' => '<h2>Total Blocks: <b>' . $spammaster_total_block_count . '</b></h2>',
    ];

    $form['statistics_header']['statistics'] = [
      '#type' => 'table',
      '#header' => [
        'firewall' => 'Firewall',
        'registration' => 'Registration',
        'comment' => 'Comment',
        'contact' => 'contact',
      ],
    ];
    // Set wide dates.
    $time = date('Y-m-d H:i:s');
    $time_expires_1_day = date('Y-m-d H:i:s', strtotime($time . '-1 days'));
    $time_expires_7_days = date('Y-m-d H:i:s', strtotime($time . '-7 days'));
    $time_expires_31_days = date('Y-m-d H:i:s', strtotime($time . '-31 days'));

    // Generate Firewall Stats 1 day.
    $spammaster_firewall_1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_firewall_1->fields('u', ['spamkey']);
    $spammaster_firewall_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_firewall_1->where('(spamkey = :firewall)', [':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_1_result = $spammaster_firewall_1->countQuery()->execute()->fetchField();
    // Generate Firewall Stats 7 days.
    $spammaster_firewall_7 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_firewall_7->fields('u', ['spamkey']);
    $spammaster_firewall_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_firewall_7->where('(spamkey = :firewall)', [':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_7_result = $spammaster_firewall_7->countQuery()->execute()->fetchField();
    // Generate Firewall Stats 31 days.
    $spammaster_firewall_31 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_firewall_31->fields('u', ['spamkey']);
    $spammaster_firewall_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_firewall_31->where('(spamkey = :firewall)', [':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_31_result = $spammaster_firewall_31->countQuery()->execute()->fetchField();
    // Generate Firewall Stats total.
    $spammaster_firewall = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_firewall->fields('u', ['spamkey']);
    $spammaster_firewall->where('(spamkey = :firewall)', [':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_result = $spammaster_firewall->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['firewall'] = [
      '#markup' =>
      '<p>Daily Blocks: <b>' . $spammaster_firewall_1_result . '</b></p>
      <p>Weekly Blocks: <b>' . $spammaster_firewall_7_result . '</b></p>
      <p>Monthly Blocks: <b>' . $spammaster_firewall_31_result . '</b></p>
      <p>Total Blocks: <b>' . $spammaster_firewall_result . '</b></p>',
    ];

    // Generate Registration Stats 1 day.
    $spammaster_registration_1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_registration_1->fields('u', ['spamkey']);
    $spammaster_registration_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_registration_1->where('(spamkey = :registration)', [':registration' => 'spammaster-registration']);
    $spammaster_registration_1_result = $spammaster_registration_1->countQuery()->execute()->fetchField();
    // Generate Registration Stats 7 days.
    $spammaster_registration_7 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_registration_7->fields('u', ['spamkey']);
    $spammaster_registration_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_registration_7->where('(spamkey = :registration)', [':registration' => 'spammaster-registration']);
    $spammaster_registration_7_result = $spammaster_registration_7->countQuery()->execute()->fetchField();
    // Generate Registration Stats 31 days.
    $spammaster_registration_31 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_registration_31->fields('u', ['spamkey']);
    $spammaster_registration_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_registration_31->where('(spamkey = :registration)', [':registration' => 'spammaster-registration']);
    $spammaster_registration_31_result = $spammaster_registration_31->countQuery()->execute()->fetchField();
    // Generate Registration Stats total.
    $spammaster_registration = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_registration->fields('u', ['spamkey']);
    $spammaster_registration->where('(spamkey = :registration)', [':registration' => 'spammaster-registration']);
    $spammaster_registration_result = $spammaster_registration->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['registration'] = [
      '#markup' =>
      '<p>Daily Blocks: <b>' . $spammaster_registration_1_result . '</b></p>
      <p>Weekly Blocks: <b>' . $spammaster_registration_7_result . '</b></p>
      <p>Monthly Blocks: <b>' . $spammaster_registration_31_result . '</b></p>
      <p>Total Blocks: <b>' . $spammaster_registration_result . '</b></p>',
    ];

    // Generate Comment Stats 1 day.
    $spammaster_comment_1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_comment_1->fields('u', ['spamkey']);
    $spammaster_comment_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_comment_1->where('(spamkey = :comment)', [':comment' => 'spammaster-comment']);
    $spammaster_comment_1_result = $spammaster_comment_1->countQuery()->execute()->fetchField();
    // Generate Comment Stats 7 days.
    $spammaster_comment_7 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_comment_7->fields('u', ['spamkey']);
    $spammaster_comment_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_comment_7->where('(spamkey = :comment)', [':comment' => 'spammaster-comment']);
    $spammaster_comment_7_result = $spammaster_comment_7->countQuery()->execute()->fetchField();
    // Generate Comment Stats 31 days.
    $spammaster_comment_31 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_comment_31->fields('u', ['spamkey']);
    $spammaster_comment_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_comment_31->where('(spamkey = :comment)', [':comment' => 'spammaster-comment']);
    $spammaster_comment_31_result = $spammaster_comment_31->countQuery()->execute()->fetchField();
    // Generate Comment Stats total.
    $spammaster_comment = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_comment->fields('u', ['spamkey']);
    $spammaster_comment->where('(spamkey = :comment)', [':comment' => 'spammaster-comment']);
    $spammaster_comment_result = $spammaster_comment->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['comment'] = [
      '#markup' =>
      '<p>Daily Blocks: <b>' . $spammaster_comment_1_result . '</b></p>
      <p>Weekly Blocks: <b>' . $spammaster_comment_7_result . '</b></p>
      <p>Monthly Blocks: <b>' . $spammaster_comment_31_result . '</b></p>
      <p>Total Blocks: <b>' . $spammaster_comment_result . '</b></p>',
    ];

    // Generate Contact Stats 1 day.
    $spammaster_contact_1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_contact_1->fields('u', ['spamkey']);
    $spammaster_contact_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_contact_1->where('(spamkey = :contact)', [':contact' => 'spammaster-contact']);
    $spammaster_contact_1_result = $spammaster_contact_1->countQuery()->execute()->fetchField();
    // Generate Contact Stats 7 days.
    $spammaster_contact_7 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_contact_7->fields('u', ['spamkey']);
    $spammaster_contact_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_contact_7->where('(spamkey = :contact)', [':contact' => 'spammaster-contact']);
    $spammaster_contact_7_result = $spammaster_contact_7->countQuery()->execute()->fetchField();
    // Generate Contact Stats 31 days.
    $spammaster_contact_31 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_contact_31->fields('u', ['spamkey']);
    $spammaster_contact_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_contact_31->where('(spamkey = :contact)', [':contact' => 'spammaster-contact']);
    $spammaster_contact_31_result = $spammaster_contact_31->countQuery()->execute()->fetchField();
    // Generate Contact Stats total.
    $spammaster_contact = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_contact->fields('u', ['spamkey']);
    $spammaster_contact->where('(spamkey = :contact)', [':contact' => 'spammaster-contact']);
    $spammaster_contact_result = $spammaster_contact->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['contact'] = [
      '#markup' =>
      '<p>Daily Blocks: <b>' . $spammaster_contact_1_result . '</b></p>
      <p>Weekly Blocks: <b>' . $spammaster_contact_7_result . '</b></p>
      <p>Monthly Blocks: <b>' . $spammaster_contact_31_result . '</b></p>
      <p>Total Blocks: <b>' . $spammaster_contact_result . '</b></p>',
    ];

    // Construct header.
    $header_key = [
      'id' => [
        'data' => $this->t('ID'),
        'field'  => 'id',
        'specifier' => 'id',
        'sort' => 'desc',
      ],
      'date' => [
        'data' => $this->t('Date'),
        'field'  => 'date',
        'specifier' => 'date',
        'sort' => 'desc',
      ],
      'spamkey' => [
        'data' => $this->t('Type'),
        'field'  => 'spamkey',
        'specifier' => 'spamkey',
        'sort' => 'desc',
      ],
      'spamvalue' => [
        'data' => $this->t('Description'),
        'field'  => 'spamvalue',
        'specifier' => 'spamvalue',
        'sort' => 'desc',
      ],
    ];
    // Get table spammaster_keys data.
    $spammaster_spam_key = \Drupal::database()->select('spammaster_keys', 'u')
      ->fields('u', ['id', 'date', 'spamkey', 'spamvalue'])
      ->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header_key)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(20)
      ->execute()->fetchAll();

    $output_key = [];
    foreach ($spammaster_spam_key as $results_key) {
      if (!empty($results_key)) {
        $output_key[$results_key->id] = [
          'id' => $results_key->id,
          'date' => $results_key->date,
          'spamkey' => $results_key->spamkey,
          'spamvalue' => $results_key->spamvalue,
        ];
      }
    }
    // Display table.
    $form['statistics_header']['table_key'] = [
      '#type' => 'tableselect',
      '#header' => $header_key,
      '#options' => $output_key,
      '#empty' => t('No log found'),
    ];
    // Spam Log Description.
    $form['statistics_header']['description'] = [
      '#markup' => '<p>Before deleting! Spam Master keeps logs for 3 months. Older logs are automatically deleted via weekly cron to keep your website clean and fast.</p>',
    ];
    // Delete button at end of table, calls spammasterdeletekey function.
    $form['statistics_header']['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => t('Delete Log Entry'),
      '#submit' => ['::spammasterdeletekey'],
    ];
    // Delete button at end of table, calls spammasterdeletekeysall function.
    $form['statistics_header']['submit_all'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => t('Delete all Statistics & Logs -> Caution, no way back'),
      '#submit' => ['::spammasterdeletekeysall'],
    ];
    // Form pager if ore than 25 entries.
    $form['statistics_header']['pager'] = [
      '#type' => 'pager',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'spammaster.settings_log',
    ];
  }

}
