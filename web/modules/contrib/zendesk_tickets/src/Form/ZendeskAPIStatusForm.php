<?php

namespace Drupal\zendesk_tickets\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\zendesk_tickets\Zendesk\ZendeskAPI;

/**
 * The Zendesk API status check form.
 */
class ZendeskAPIStatusForm extends FormBase {

  /**
   * Zendesk API object.
   *
   * @var ZendeskAPI
   */
  protected $api;

  /**
   * Form constructor.
   *
   * @param ZendeskAPI $api
   *   The Zendesk API handler.
   * @param TranslationInterface $translator
   *   (optional) The string translation service.
   */
  public function __construct(ZendeskAPI $api, TranslationInterface $translator = NULL) {
    $this->api = $api;
    $this->stringTranslation = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('zendesk_tickets.zendesk_api'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zendesk_api_status_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $client = $this->api->httpClient();
    $status_results = $form_state->getValue($this->getFormId() . '__results', []);

    // Build configuration table.
    $config_rows = [];
    $config_rows[] = [
      $this->t('Enabled'),
      $this->api->isEnabled() ? $this->t('Yes') : $this->t('No'),
    ];

    $config_rows[] = [
      $this->t('Able to make API Requests'),
      $this->api->isCapable() && $client ? $this->t('Yes') : $this->t('No, check configuration.'),
    ];

    $config_rows[] = [
      $this->t('Username'),
      $this->api->getAuthUsername(),
    ];

    $config_rows[] = [
      $this->t('Authorization Strategy'),
      $this->api->getAuthStrategyLabel(),
    ];

    $config_rows[] = [
      $this->t('Url'),
      $client ? $client->getApiUrl() : $this->t('N/A'),
    ];

    $config_rows[] = [
      $this->t('Base endpoint path'),
      $client ? $client->getApiBasePath() : $this->t('N/A'),
    ];

    $form['config'] = [
      '#type' => 'table',
      '#caption' => $this->t('Configuration'),
      '#rows' => $config_rows,
      '#responsive' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Check status'),
      ],
    ];

    if ($status_results) {
      $form['tests'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Status Checks'),
        '#weight' => 200,
      ];

      $test_headers = [
        $this->t('Scenario'),
        $this->t('Result'),
        $this->t('Status code'),
        $this->t('Details'),
      ];

      foreach ($status_results as $status_check_id => $status_check) {
        $test_rows = [];
        if (empty($status_check['result'])) {
          $test_rows[] = [
            $this->t('Any endpoint'),
            $this->t('Failed'),
            '',
            $this->t('No test results.'),
          ];
        }
        elseif (!empty($status_check['result']['tests'])) {
          foreach ($status_check['result']['tests'] as $test_id => $test) {
            $test_passed = isset($test['result']['pass']) && $test['result']['pass'];
            $test_row = [
              'class' => $test_passed ? 'color-success' : 'color-error',
            ];

            $test_row['data'] = [
              !empty($test['test']['scenario']) ? $test['test']['scenario'] : $test_id,
              $test_passed ? $this->t('Passed') : $this->t('Failed'),
              isset($test['result']['code']) ? $test['result']['code'] : $this->t('N/A'),
              !empty($test['result']['message']) ? $test['result']['message'] : '',
            ];

            $test_rows[] = $test_row;
          }
        }
        else {
          $test_rows[] = [
            $this->t('Any endpoint'),
            isset($status_check['result']['pass']) && $status_check['result']['pass'] ? $this->t('Passed') : $this->t('Failed'),
            '',
            !empty($status_check['result']['message']) ? $status_check['result']['message'] : '',
          ];
        }

        $form['tests'][$status_check_id] = [
          '#type' => 'table',
          '#caption' => $this->t('@label (@endpoint)', [
            '@label' => $status_check['label'],
            '@endpoint' => !empty($status_check['result']['resource_name']) ? $status_check['result']['resource_name'] : $status_check['endpoint'],
          ]),
          '#rows' => $test_rows,
          '#header' => $test_headers,
          '#responsive' => TRUE,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @TODO: add ticket_fields tests.
    $results = [
      'ticket_forms' => [
        'label' => $this->t('Ticket Forms'),
        'endpoint' => 'ticket_forms',
        'result' => $this->api->getTester()->ticketFormsStatusCheck(),
      ],
      'tickets' => [
        'label' => $this->t('Tickets'),
        'endpoint' => 'tickets',
        'result' => $this->api->getTester()->ticketsStatusCheck(),
      ],
    ];

    $form_state->setValue($this->getFormId() . '__results', $results);
    $form_state->setRebuild();
  }

}
