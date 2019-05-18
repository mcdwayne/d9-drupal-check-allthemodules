<?php

namespace Drupal\captcha_questions_dblog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Database\Connection;

/**
 * Provides route responses for the Example module.
 */
class CaptchaQuestionsDblogController extends ControllerBase {
  /**
   * DateFormatter services object.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  private $dateFormatter;

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('database')
    );
  }

  /**
   * CaptchaQuestionsDblog Constuctor.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   DateFormatter services object.
   * @param \Drupal\Core\Database\Connection $database
   *   The Database Connection.
   */
  public function __construct(DateFormatter $dateFormatter, Connection $database) {
    $this->dateFormatter = $dateFormatter;
    $this->database = $database;
  }

  /**
   * Fetch and display failed form submissions.
   *
   * @return array
   *   Returns themed table with pager
   */
  public function captchaQuestionsDblogView() {
    $header = [
      ['data' => $this->t('Submission'), 'field' => 'dblogid'],
      ['data' => $this->t('Timestamp'), 'field' => 'timestamp'],
      ['data' => $this->t('IP'), 'field' => 'IP'],
      ['data' => $this->t('Form ID'), 'field' => 'form_id'],
      ['data' => $this->t('Question asked'), 'field' => 'question_asked'],
      ['data' => $this->t('Answer given'), 'field' => 'answer_given'],
      ['data' => $this->t('Correct answer'), 'field' => 'answer_correct'],
    ];
    $rows = [];
    $query = $this->database->select('captcha_questions_dblog', 'log');
    $query->fields('log', [
      'dblogid',
      'timestamp',
      'ip',
      'form_id',
      'question_asked',
      'answer_given',
      'answer_correct',
    ]);
    // The actual action of sorting the rows is here.
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);
    // Limit the rows to 5 for each page.
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(5);
    $result = $pager->execute();

    // Constructing rows from $entries matching $header.
    foreach ($result as $e) {
      $rows[] = [
        $e->dblogid,
        $this->dateFormatter->format($e->timestamp, 'custom', 'Y-m-d H:m:s'),
        $e->ip,
        $e->form_id,
        Unicode::truncate($e->question_asked, '30', TRUE, 20),
        $e->answer_given,
        $e->answer_correct,
      ];
    }

    $count = count($rows);
    // The table description.
    $build = [
      '#markup' => $this->t('Found a total of @count failed submissions', ['@count' => $count]),
    ];

    // Generate the table.
    $build['config_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    // Finally add the pager.
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

}
