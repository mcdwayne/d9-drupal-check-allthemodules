<?php

namespace Drupal\zendesk_tickets\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\zendesk_tickets\ZendeskTicketFormTypeImporter;

/**
 * The Zendesk API status check form.
 */
class ZendeskTicketFormTypeImportForm extends FormBase {

  /**
   * The config object being edited.
   *
   * @var Config
   */
  protected $config;

  /**
   * Zendesk Form Type importer object.
   *
   * @var ZendeskTicketFormTypeImporter
   */
  protected $importer;

  /**
   * The state manager.
   *
   * @var StateInterface
   */
  protected $state;

  /**
   * The date formatter service.
   *
   * @var DateFormatter
   */
  protected $dateFormatter;

  /**
   * Form constructor.
   *
   * @param Config $config
   *   The config object being edited.
   * @param ZendeskTicketFormTypeImporter $importer
   *   The Zendesk ticket form type importer object.
   * @param StateInterface $state
   *   The persistent state manager service.
   * @param DateFormatter $date_formatter
   *   The date formatter service.
   * @param TranslationInterface $translator
   *   (optional) The string translation service.
   */
  public function __construct(Config $config, ZendeskTicketFormTypeImporter $importer, StateInterface $state, DateFormatter $date_formatter, TranslationInterface $translator = NULL) {
    $this->config = $config;
    $this->importer = $importer;
    $this->state = $state;
    $this->dateFormatter = $date_formatter;
    $this->stringTranslation = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->getEditable('zendesk_tickets.settings'),
      $container->get('zendesk_tickets.zendesk_ticket_form_type_importer'),
      $container->get('state'),
      $container->get('date.formatter'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zendesk_ticket_form_type_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $results = $form_state->get($this->getFormId() . '__results') ?: [];
    $info_rows = [];

    $last_import = $this->importer->getLastImportedTime();
    $info_rows[] = [
      $this->t('Last import by any method'),
      $last_import ? $this->dateFormatter->format($last_import, 'short') : $this->t('Never'),
    ];

    // Admin import - only show if there is a cron import since 'any' will
    // duplicate the time.
    $last_admin_import = $this->state->get('zendesk_tickets.last_admin_import', 0);
    if ($last_admin_import) {
      $info_rows[] = [
        $this->t('Last admin import'),
        $last_admin_import ? $this->dateFormatter->format($last_admin_import, 'short') : $this->t('Never'),
      ];
    }

    // Cron import.
    $import_cron_dt = $this->config->get('import_cron_dt');
    $info_rows[] = [
      $this->t('Cron import interval'),
      $import_cron_dt ? $this->dateFormatter->formatInterval($import_cron_dt) : $this->t('Never'),
    ];

    $last_cron_import = $this->state->get('zendesk_tickets.last_cron_import', 0);
    if ($last_cron_import) {
      $info_rows[] = [
        $this->t('Last cron import'),
        $last_cron_import ? $this->dateFormatter->format($last_cron_import, 'short') : $this->t('Has not run yet'),
      ];
    }

    // Next cron import.
    $next_cron_import_formatted = '';
    if ($import_cron_dt && $import_cron_dt > 0) {
      if ($last_cron_import) {
        $next_cron_import = $last_cron_import + $import_cron_dt;
        if ($next_cron_import > REQUEST_TIME) {
          $next_cron_import_formatted = $this->dateFormatter->format($next_cron_import, 'short');
        }
      }

      if (!$next_cron_import_formatted) {
        $next_cron_import_formatted = $this->t('Next cron run');
      }
    }
    elseif ($last_cron_import) {
      $next_cron_import_formatted = $this->t('Never');
    }

    if ($next_cron_import_formatted) {
      $info_rows[] = [
        $this->t('Next cron import'),
        $next_cron_import_formatted,
      ];
    }

    if ($info_rows) {
      $form['info'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Info'),
      ];

      $form['info']['table'] = [
        '#type' => 'table',
        '#rows' => $info_rows,
        '#responsive' => TRUE,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Import forms'),
      ],
    ];

    if (!empty($results)) {
      $form['imported'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Imported forms'),
        '#weight' => 200,
      ];

      if (!empty($results['message'])) {
        $form['imported']['message'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $results['message'],
        ];
      }

      if (!empty($results['forms'])) {
        $imported_headers = [
          $this->t('Zendesk ID'),
          $this->t('New / Updated'),
          $this->t('Label'),
          $this->t('Status'),
        ];

        $imported_rows = [];
        foreach ($results['forms'] as $imported_form_id => $imported_form) {
          $imported_row = [$imported_form_id];
          if (!empty($imported_form['entity'])) {
            $imported_row[] = !empty($imported_form['is_new']) ? $this->t('New') : $this->t('Updated');
            $imported_row[] = $imported_form['entity']->label();
            $imported_row[] = $imported_form['entity']->status() ? $this->t('Enabled') : $this->t('Disabled');
          }
          else {
            $imported_row[] = $this->t('Not imported');
            $imported_row[] = '---';
            $imported_row[] = '---';
          }

          $imported_rows[] = $imported_row;
        }

        if ($imported_rows) {
          $form['imported']['forms'] = [
            '#type' => 'table',
            '#rows' => $imported_rows,
            '#header' => $imported_headers,
            '#responsive' => TRUE,
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $results = ['forms' => []];
    $forms = $this->importer->importAll();
    if (!empty($forms)) {
      $results['forms'] = $forms;
      $this->state->set('zendesk_tickets.last_admin_import', REQUEST_TIME);
      $this->importer->getLogger()->notice('Zendesk ticket form import completed via the admin form.');
    }
    else {
      $results['message'] = $this->t('There were no forms returned. Check the API configuration to verify the connection to Zendesk.');
    }

    $form_state->set($this->getFormId() . '__results', $results);
    $form_state->setRebuild();
  }

}
