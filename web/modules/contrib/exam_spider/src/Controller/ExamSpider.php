<?php

namespace Drupal\exam_spider\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\exam_spider\ExamSpiderDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A class for muliple ExamSpider functions.
 */
class ExamSpider extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The ExamSpider service.
   *
   * @var \Drupal\user\ExamSpiderData
   */
  protected $ExamSpiderData;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('exam_spider.data'),
      $container->get('language_manager'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * Constructs a ExamSpider object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\exam_spider\ExamSpiderDataInterface $examspider_data
   *   The ExamSpider multiple services.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(Connection $database, ExamSpiderDataInterface $examspider_data, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager) {
    $this->database = $database;
    $this->userStorage = $this->entityManager()->getStorage('user');
    $this->ExamSpiderData = $examspider_data;
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
  }

  /**
   * Displays a listing of Exams list.
   */
  public function examSpiderDashboard() {
    $createexam_url = Url::fromRoute('exam_spider.exam_spider_add_exam');
    $createexam_link = Link::fromTextAndUrl($this->t('+ Create @examSpiderExamTitle', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]), $createexam_url)->toString();
    $output['add_exams_link'] = [
      '#markup' => $createexam_link,
    ];
    $header = [
      [
        'data' => EXAM_SPIDER_EXAM_TITLE . ' Id',
        'field' => 'el.id',
        'sort' => 'desc',
      ],
      [
        'data' => EXAM_SPIDER_EXAM_TITLE . ' Name',
        'field' => 'el.exam_name',
      ],
      [
        'data' => EXAM_SPIDER_EXAM_TITLE . ' Description',
        'field' => 'exam_description',
      ],
      [
        'data' => 'Created By',
        'field' => 'el.uid',
      ],
      [
        'data' => 'Status',
        'field' => 'el.status',
      ],
      [
        'data' => 'Operations',
      ],
    ];
    $query = $this->database->select('exam_list', 'el')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('el',
      ['id', 'exam_name', 'exam_description', 'uid', 'status']
    );
    $results = $query
      ->limit(10)
      ->orderByHeader($header)
      ->execute()
      ->fetchAll();
    $rows = [];
    foreach ($results as $row) {
      if ($row->status == 0) {
        $status = 'Closed';
      }
      else {
        $status = 'Open';
      }
      $addquestion_url = Url::fromRoute('exam_spider.exam_spider_add_question', ['examid' => $row->id]);
      $addquestion_link = Link::fromTextAndUrl($this->t('Questions'), $addquestion_url)->toString();
      $editexam_url = Url::fromRoute('exam_spider.exam_spider_edit_exam', ['examid' => $row->id]);
      $editexam_link = Link::fromTextAndUrl($this->t('Edit'), $editexam_url)->toString();
      $deleteexam_url = Url::fromRoute('exam_spider.exam_spider_delete_exam', ['examid' => $row->id]);
      $deleteexam_link = Link::fromTextAndUrl($this->t('Delete'), $deleteexam_url)->toString();
      $examcontinue_url = Url::fromRoute('exam_spider.exam_spider_exam_continue', ['examid' => $row->id]);
      $examcontinue_link = Link::fromTextAndUrl($row->exam_name, $examcontinue_url)->toString();
      $operations = $this->t(
        '@addquestion_link | @editexam_link | @deleteexam_link', [
          '@addquestion_link' => $addquestion_link,
          '@editexam_link' => $editexam_link,
          '@deleteexam_link' => $deleteexam_link,
        ]
      );
      $user = $this->userStorage->load($row->uid);
      $rows[] = [
        'data' => [
          EXAM_SPIDER_EXAM_TITLE . '-' . $row->id,
          $examcontinue_link,
          $row->exam_description,
          $user->getUsername(),
          $status,
          $operations,
        ],
      ];
    }
    $output['exams_list'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No Exams available.@create_exam_link', ['@create_exam_link' => $createexam_link]),
      '#attributes' => ['class' => 'exams-list-table'],
    ];
    $output['exams_pager'] = ['#type' => 'pager'];
    return $output;
  }

  /**
   * All exam listed page to start exam page callbacks.
   */
  public function examSpiderExamStart() {
    $output = NULL;
    $header = [
      [
        'data' => EXAM_SPIDER_EXAM_TITLE . ' Name',
        'field' => 'el.exam_name',
      ],
      [
        'data' => EXAM_SPIDER_EXAM_TITLE . ' Description',
        'field' => 'el.exam_description',
      ],
      [
        'data' => 'Operations',
      ],
    ];
    $query = $this->database->select('exam_list', 'el')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->fields(
      'el', ['id', 'exam_name', 'exam_description', 'status']
    );
    $results = $query
      ->limit(10)
      ->orderByHeader($header)
      ->execute()
      ->fetchAll();
    $rows = [];
    foreach ($results as $row) {
      if ($row->status == 1) {

      }
      $examcontinue_url = Url::fromRoute('exam_spider.exam_spider_exam_continue', ['examid' => $row->id]);
      $examcontinue_link = Link::fromTextAndUrl($this->t('Start @examSpiderExamTitle', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]), $examcontinue_url)->toString();
      $examcontinue__name_link = Link::fromTextAndUrl($this->t('@examName', ['@examName' => $row->exam_name]), $examcontinue_url)->toString();
      $rows[] = [
        'data' => [
          $examcontinue__name_link,
          $row->exam_description,
          $examcontinue_link,
        ],
      ];
    }
    $output['exams_start_list'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No @examSpiderExamTitle created yet.', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
      '#attributes' => ['class' => 'exams-start-table'],
    ];
    $output['exams_start_pager'] = ['#type' => 'pager'];
    return $output;
  }

  /**
   * Send result score card via mail.
   */
  public function examSpiderExamResultMail($resultid) {
    if (is_numeric($resultid)) {
      $query = $this->database->select("exam_results", "er")
        ->fields("er")
        ->condition('id', $resultid);
      $exam_result_data = $query->execute()->fetchAssoc();
      $user_data = $this->userStorage->load($exam_result_data['uid']);
      $exam_data = $this->ExamSpiderData->examSpiderGetExam($exam_result_data['examid']);
      $body = $this->t('Hi @tomail,

      You have got @score_obtain marks out of @total_marks.
      Wrong Answer(s) @wrong_quest.

      Many Thanks,
      @sitename', [
        '@score_obtain'   => $exam_result_data['obtain'],
        '@total_marks'    => $exam_result_data['total'],
        '@wrong_quest'    => $exam_result_data['wrong'],
        '@sitename'       => $this->config('system.site')->get('name'),
        '@tomail'         => @$user_data->get('name')->value,

      ]);
      $module = 'exam_spider';
      $key = 'exam_spider_result';
      $to = $user_data->get('mail')->value;
      $params['message'] = $body;
      $params['subject'] = 'Eaxam Result for ' . $exam_data['exam_name'];
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
      $send = TRUE;
      $result = $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
      if ($result['result'] !== TRUE) {
        drupal_set_message($this->t('There was a problem sending your message and it was not sent.'), 'error');
      }
      else {
        drupal_set_message(
          $this->t('Your message has been sent.')
        );
      }
      return $this->redirect('exam_spider.exam_spider_exam_results');
    }
  }

}
