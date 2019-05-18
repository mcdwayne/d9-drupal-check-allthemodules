<?php

namespace Drupal\devel_mail_logger\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

/**
 * Class DevelMailLoggerController.
 */
class DevelMailLoggerController extends ControllerBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;
  /**
   * Drupal\Core\Datetime\DateFormatterInterface definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;
  /**
   * Drupal\Core\Form\FormBuilderInterface definition.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Drupal\Core\Mail\MailManagerInterface definition.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $pluginManagerMail;

  /**
   * Constructs a new DevelMailLoggerController object.
   */
  public function __construct(
    Connection $database,
    DateFormatterInterface $date_formatter,
    FormBuilderInterface $form_builder,
    MailManagerInterface $plugin_manager_mail
  ) {
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder = $form_builder;
    $this->pluginManagerMail = $plugin_manager_mail;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('form_builder'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   *  Show a list of mails from db
   */
  public function listMails() {

    $header = [
      [
        'data' => $this->t('Date'),
        'field' => 'm.timestamp',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('To'),
        'field' => 'm.recipient',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Subject'),
        'field' => 'm.subject',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    $query = $this->database->select('devel_mail_logger', 'm')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $results = $query->fields('m', ['id', 'timestamp', 'subject', 'recipient'])
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    $rows = [];

    foreach ($results as $result) {
      $rows[] = [
        'data' => [
          $this->t($this->dateFormatter->format($result->timestamp, 'short')),
          $result->recipient,
          Link::createFromRoute($result->subject, 'devel_mail_logger.mail', ['id' => $result->id]),
        ],
      ];
    }

    $build = array(
      'form' => $this->formBuilder->getForm('Drupal\devel_mail_logger\Form\DevelMailLoggerDeleteForm'),
      'mail_table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['id' => 'admin-dblog', 'class' => ['admin-dblog']],
        '#empty' => $this->t('No debug mails available.'),
      ],

      'pager' => array(
        '#type' => 'pager'
      ),
    );

    return $build;
  }

  /**
   * Show a single mail
   */
  public function showMail($id){

    $build = [];

    $mail_log = $this->database->query('SELECT * FROM {devel_mail_logger} m WHERE m.id = :id', [':id' => $id])->fetchObject();
    $mail = json_decode($mail_log->message);

    $rows = [];
    foreach ($mail->headers as $key => $value) {
      $rows[] = [
        ['data' => $key, 'header' => true],
        $value
      ];
    }

    $build['headers'] = array(
      '#type' => 'details',
      '#title' => 'Headers',
    );
    $build['headers']['table'] = array(
      '#type' => 'table',
      '#rows' => $rows,
    );


    $rows = [
      [
        ['data' => $this->t('To: '), 'header' => TRUE],
        $this->t($mail->to),
      ],
      [
        ['data' => $this->t('Subject: '), 'header' => TRUE],
        $this->t($mail->subject),
      ],
      [
        ['data' => $this->t('Body: '), 'header' => TRUE],
	Markup::create(nl2br(is_array($mail->body) ? implode('', $mail->body) : $mail->body)),
      ],
    ];

    $build['mail_table'] = [
      '#type' => 'table',
      '#rows' => $rows,
    ];

    return $build;
  }


  /**
   * Send a test mail to current user
   * @return [type] [description]
   */
  public function sendMail(){
    $module = 'devel_mail_logger';
    $key = 'send_test';
    $to = \Drupal::currentUser()->getEmail();
    $params['message'] = 'body';
    $params['subject'] = 'subject';
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    $result = $this->pluginManagerMail->mail($module, $key, $to, $langcode, $params, NULL, $send);

    return array(
      '#markup' => t('Mail sent.'),
    );
  }
}
