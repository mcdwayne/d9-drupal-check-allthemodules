<?php

namespace Drupal\exam_spider\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\UserStorageInterface;
use Drupal\exam_spider\ExamSpiderDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the exam results form.
 *
 * @package Drupal\exam_spider\Form
 */
class ExamSpiderResultsForm extends FormBase {

  /**
   * The ExamSpider service.
   *
   * @var \Drupal\user\ExamSpiderData
   */
  protected $ExamSpiderData;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a ExamSpider object.
   *
   * @param \Drupal\exam_spider\ExamSpiderDataInterface $examspider_data
   *   The ExamSpider multiple services.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   */
  public function __construct(ExamSpiderDataInterface $examspider_data, DateFormatterInterface $date_formatter, Renderer $renderer, UserStorageInterface $user_storage) {
    $this->ExamSpiderData = $examspider_data;
    $this->dateFormatter = $date_formatter;
    $this->render = $renderer;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('exam_spider.data'),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * Get exam results form.
   */
  public function getFormId() {
    return 'exam_results_form';
  }

  /**
   * Build exam results form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['#attached']['library'][] = 'exam_spider/exam_spider';
    $exam_names = [];
    $exams_data = $this->ExamSpiderData->examSpiderGetExam();
    $examresults_url = Url::fromRoute('exam_spider.exam_spider_exam_results');
    $link_options = [
      'attributes' => [
        'class' => [
          'button',
        ],
      ],
    ];
    $examresults_url->setOptions($link_options);
    $examresults_link = Link::fromTextAndUrl($this->t('Reset'), $examresults_url)->toString();
    $form['#method'] = 'get';
    if ($exams_data) {
      foreach ($exams_data as $exam_name) {
        $exam_names[$exam_name->id] = $exam_name->exam_name;
      }
      $form['filter'] = [
        '#type' => 'details',
        '#title' => $this->t('Filter option'),
        '#attributes' => ['class' => ['container-inline']],
      ];
      $form['filter']['exam_name'] = [
        '#type' => 'select',
        '#title' => $this->t('@examSpiderExamTitle Name', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
        '#options' => $exam_names,
        '#default_value' => isset($_GET['exam_name']) ? $_GET['exam_name'] : NULL,
      ];
      $form['filter']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Filter'),
      ];
      $form['filter']['reset_button'] = [
        '#markup' => $examresults_link,
      ];
    }
    $exam_spider_exam_results = $this->examSpiderExamResults();
    $form['#suffix'] = $this->render->render($exam_spider_exam_results);
    return $form;
  }

  /**
   * Exam results form submit callbacks.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Get exam results function.
   */
  public function examSpiderExamResults() {
    $header = [
      [
        'data' => 'REG Id',
        'field' => 'er.id',
        'sort' => 'desc',
      ],
      [
        'data' => EXAM_SPIDER_EXAM_TITLE . ' Name',
        'field' => 'er.examid',
      ],
      [
        'data' => 'Name',
        'field' => 'er.uid',
      ],
      [
        'data' => 'Total Marks',
        'field' => 'er.total',
      ],
      [
        'data' => 'Obtain Marks',
        'field' => 'er.obtain',
      ],
      [
        'data' => 'Wrong',
        'field' => 'er.wrong',
      ],
      [
        'data' => 'Date',
        'field' => 'er.created',
      ],
      [
        'data' => 'Operations',
      ],
    ];
    $query = db_select('exam_results', 'er')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->fields(
      'er', ['id', 'examid', 'uid', 'total', 'obtain', 'wrong', 'created']
    );
    if (isset($_GET['exam_name'])) {
      $query->condition('examid', $_GET['exam_name']);
    }
    $results = $query
      ->limit(10)
      ->orderByHeader($header)
      ->execute()
      ->fetchAll();
    $rows = [];
    foreach ($results as $row) {
      $deleteresult_url = Url::fromRoute('exam_spider.exam_spider_delete_result', ['resultid' => $row->id]);
      $deleteresult_link = Link::fromTextAndUrl($this->t('Delete'), $deleteresult_url)->toString();
      $sendmail_url = Url::fromRoute('exam_spider.exam_spider_exam_result_mail', ['resultid' => $row->id, 'uid' => $row->uid]);
      $sendmail_link = Link::fromTextAndUrl($this->t('Send Mail'), $sendmail_url)->toString();
      $operations = $this->t('@deleteresult_link | @sendmail_link', ['@deleteresult_link' => $deleteresult_link, '@sendmail_link' => $sendmail_link]);

      $exam_data = $this->ExamSpiderData->examSpiderGetExam($row->examid);
      $user = $this->userStorage->load($row->uid);
      $username = $user->getUsername();
      $rows[] = [
        'data' => [
          $this->t('REG -') . $row->id,
          $exam_data['exam_name'],
          $username,
          $row->total,
          $row->obtain,
          $row->wrong,
          $this->dateFormatter->format($row->created, 'short'),
          $operations,
        ],
      ];
    }
    $output['exams_result_list'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No @examSpiderExamTitle result found.', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
      '#attributes' => ['class' => 'exams-result-table'],
    ];
    $output['exams_result_pager'] = ['#type' => 'pager'];
    return $output;
  }

}
