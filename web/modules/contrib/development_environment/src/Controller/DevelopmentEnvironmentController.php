<?php

namespace Drupal\development_environment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\development_environment\Service\VarDumpServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The DevelopmentEnvironmentController class.
 */
class DevelopmentEnvironmentController extends ControllerBase implements DevelopmentEnvironmentControllerInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The variable dump service.
   *
   * @var \Drupal\development_environment\Service\VarDumpServiceInterface
   */
  protected $varDumpService;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a DevelopmentEnvironmentController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The datagbase connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\development_environment\Service\VarDumpServiceInterface $varDumpService
   *   The variable dump service.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder interface.
   */
  public function __construct(
    Connection $database,
    DateFormatterInterface $dateFormatter,
    AccountProxyInterface $currentUser,
    VarDumpServiceInterface $varDumpService,
    FormBuilderInterface $formBuilder
  ) {
    $this->database = $database;
    $this->dateFormatter = $dateFormatter;
    $this->currentUser = $currentUser;
    $this->varDumpService = $varDumpService;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('current_user'),
      $container->get('development_environment.var.dump.service'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function logListPage() {
    $query = $this->database->select('development_environment_log', 'log_data')->extend('\Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('log_data', [
      'lid',
      'email_data',
      'timestamp',
      'recipient_email',
      'subject',
    ]);
    $log_items = $query->limit(20)
      ->orderBy('lid', 'DESC')
      ->execute();

    $page = [
      'form' => $this->formBuilder->getForm('\Drupal\development_environment\Form\DevelopmentEnvironmentClearLogForm'),
      'items' => [
        '#theme' => 'table',
        '#header' => [
          '',
          $this->t('Time'),
          $this->t('Recipient'),
          $this->t('Subject'),
        ],
        '#rows' => [],
        '#empty' => $this->t('No emails have been logged'),
      ],
      'pager' => [
        '#type' => 'pager',
      ],
    ];

    foreach ($log_items as $item) {
      $url = Url::fromRoute('development_environment.suppressed_email_log', ['lid' => $item->lid]);
      $link = Link::fromTextAndUrl($this->t('View'), $url);
      $page['items']['#rows'][] = [
        $link,
        $this->dateFormatter->format($item->timestamp, 'short', '', $this->currentUser->getTimeZone()),
        $item->recipient_email,
        $item->subject,
      ];
    }

    return $page;
  }

  /**
   * {@inheritdoc}
   */
  public function mailLogPage($lid) {
    $mail_log = $this->database->query('SELECT timestamp, email_data FROM {development_environment_log} WHERE lid = :lid', [':lid' => $lid])->fetch();

    if (!$mail_log) {
      throw new NotFoundHttpException();
    }
    $mail_info = unserialize($mail_log->email_data);

    if (is_array($mail_info['body'])) {
      $body = implode('<br/>', $mail_info['body']);
    }
    else {
      $body = $mail_info['body'];
    }

    $page = [
      'header' => [
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
        '#markup' => $this->t('The following email was not sent as this is a development environment'),
      ],
      'email' => [
        '#type' => 'details',
        '#title' => $this->t('Email data'),
        '#open' => TRUE,
        'time' => [
          '#prefix' => '<p>',
          '#suffix' => '</p>',
          '#markup' => $this->t(
            'Time: %time',
            [
              '%time' => $this->dateFormatter->format($mail_log->timestamp, 'short', '', $this->currentUser->getTimeZone()),
            ]
          ),
        ],
        'recipient' => [
          '#prefix' => '<p>',
          '#suffix' => '</p>',
          '#markup' => $this->t('Recipient: %recipient', ['%recipient' => $mail_info['to']]),
        ],
        'subject' => [
          '#prefix' => '<p>',
          '#suffix' => '</p>',
          '#markup' => $this->t('Subject: %subject', ['%subject' => $mail_info['subject']]),
        ],
        'body' => [
          '#prefix' => '<div>',
          '#suffix' => '</div><br/>',
          '#markup' => $this->t('Body: %body', ['%body' => $body]),
        ],
        'headers' => [
          '#prefix' => '<div>',
          '#suffix' => '</div>',
          '#markup' => $this->t('Headers:') . $this->varDumpService->varDump($mail_info['headers'], TRUE, TRUE),
        ],
      ],
      'raw_mail_data' => [
        '#type' => 'details',
        '#title' => $this->t('Raw email data'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        'data' => [
          '#markup' => $this->varDumpService->varDump($mail_info, TRUE, TRUE),
        ],
      ],
    ];

    return $page;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsPage() {
    $page = [
      '#prefix' => '<div id="development_environment_settings_page">',
      '#suffix' => '</div>',
      'form' => $this->formBuilder->getForm('\Drupal\development_environment\Form\DevelopmentEnvironmentSettingsForm'),
    ];

    return $page;
  }

}
