<?php

namespace Drupal\spammaster\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class controller.
 */
class SpamMasterBufferForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spammaster_settings_buffer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function spammasterdeletethreat($form, &$form_state) {
    $spam_form_delete = $form_state->getValue('buffer_header')['table_buffer'];
    $spammaster_buffer_date = date("Y-m-d H:i:s");
    foreach ($spam_form_delete as $spam_row_delete) {
      if (!empty($spam_row_delete)) {
        db_query('DELETE FROM {spammaster_threats} WHERE id = :row', [':row' => $spam_row_delete]);
        drupal_set_message(t('Saved Spam Buffer deletion.'));
        \Drupal::logger('spammaster-buffer')->notice('Spam Master: buffer deletion, Id: ' . $spam_row_delete);
        $spammaster_db_buffer_delete = db_insert('spammaster_keys')->fields([
          'date' => $spammaster_buffer_date,
          'spamkey' => 'spammaster-buffer',
          'spamvalue' => 'Spam Master: buffer deletion, Id: ' . $spam_row_delete,
        ])->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Default settings.
    $config = $this->config('spammaster.buffer');

    $form['buffer_header'] = [
      '#type' => 'details',
      '#title' => $this->t('<h3>Spam Buffer</h3>'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    // Construct header.
    $header = [
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
      'threat' => [
        'data' => $this->t('Threat'),
        'field'  => 'threat',
        'specifier' => 'threat',
        'sort' => 'desc',
      ],
      'search' => [
        'data' => $this->t('Search'),
      ],
    ];
    // Get table spammaster_threats data.
    $spammaster_spam_buffer = \Drupal::database()->select('spammaster_threats', 'u')
      ->fields('u', ['id', 'date', 'threat'])
      ->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(20)
      ->execute()->fetchAll();

    $output = [];
    foreach ($spammaster_spam_buffer as $results) {
      if (!empty($results)) {
        if (filter_var($results->threat, FILTER_VALIDATE_IP)) {
          $search = Url::fromUri('https://spammaster.techgasp.com/search-threat/?search_spam_threat=' . $results->threat);
          $search_display = \Drupal::l('+ Spam Master online database', $search);
        }
        else {
          $search_display = 'discard email';
          $search = '';
        }
        $output[$results->id] = [
          'id' => $results->id,
          'date' => $results->date,
          'threat' => $results->threat,
          'search' => $search_display,
        ];
      }
    }
    // Get Buffer Size.
    $spammaster_buffer_size = \Drupal::database()->select('spammaster_threats', 'u');
    $spammaster_buffer_size->fields('u', ['threat']);
    $spammaster_buffer_size_result = $spammaster_buffer_size->countQuery()->execute()->fetchField();
    $form['buffer_header']['total_buffer'] = [
      '#markup' => '<h2>Buffer Size: <b>' . $spammaster_buffer_size_result . '</b></h2>',
    ];

    // Spam Buffer Description.
    $form['buffer_header']['header_description'] = [
      '#markup' => '<p>Spam Master Buffer greatly reduces server resources like cpu, memory and bandwidth by doing fast local machine checks. Also prevents major attacks like flooding, DoS , etc. via Spam Master Firewall.</p>',
    ];

    // Display table.
    $form['buffer_header']['table_buffer'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $output,
      '#empty' => t('No threats found'),
    ];
    // Delete button at end of table, calls spammasterdeletethreat function.
    $form['buffer_header']['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => t('Delete Spam Entry'),
      '#submit' => ['::spammasterdeletethreat'],
    ];

    // Form pager if ore than 25 entries.
    $form['buffer_header']['pager'] = [
      '#type' => 'pager',
    ];

    // Spam Buffer Description.
    $form['buffer_header']['footer_description'] = [
      '#markup' => '<p>Before deleting! Spam Master Buffers for 3 months. Older Buffer entries are automatically deleted via weekly cron to keep your website clean and fast.</p>',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'spammaster.settings_buffer',
    ];
  }

}
