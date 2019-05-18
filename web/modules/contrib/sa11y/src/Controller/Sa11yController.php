<?php

namespace Drupal\sa11y\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\sa11y\Sa11yInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Sa11yController.
 */
class Sa11yController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Sa11y service.
   *
   * @var \Drupal\sa11y\Sa11y
   */
  protected $sa11y;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('sa11y.service'),
      $container->get('date.formatter'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a Sa11yController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\sa11y\Sa11yInterface $sa11y
   *   Sa11y Service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   A date formatter.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct(Connection $database, Sa11yInterface $sa11y, DateFormatterInterface $dateFormatter, FormBuilderInterface $formBuilder) {
    $this->database = $database;
    $this->sa11y = $sa11y;
    $this->dateFormatter = $dateFormatter;
    $this->formBuilder = $formBuilder;
  }

  /**
   * Gets an array of report status levels.
   *
   * @return array
   *   An array of report status.
   */
  public function getReportStatusOptions() {
    return [
      Sa11yInterface::CREATED => $this->t("Created"),
      Sa11yInterface::RUNNING => $this->t("Running"),
      Sa11yInterface::ERROR => $this->t("Error!"),
      Sa11yInterface::TIMEOUT => $this->t("Timed Out"),
      Sa11yInterface::COMPLETE => $this->t("Completed"),
      Sa11yInterface::CANCELLED => $this->t("Cancelled"),
    ];
  }

  /**
   * Outputs a listing of Reports and summary of their violations.
   */
  public function listReports() {
    $header = [
      ['data' => $this->t('Report ID'), 'field' => 'id', 'sort' => 'desc'],
      ['data' => $this->t('URL'), 'field' => 'source'],
      ['data' => $this->t('Date'), 'field' => 'timestamp'],
      ['data' => $this->t('Status'), 'field' => 'status'],
      ['data' => $this->t('Violations'), 'field' => 'violations'],
      ['data' => $this->t('Operations')],
    ];

    $query = $this->database->select('sa11y', 's')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->leftJoin('sa11y_data', 'd', 'd.report_id = s.id');
    $query->addExpression('COUNT(d.id)', 'violations');
    $query->fields('s', ['id', 'source', 'timestamp', 'status'])
      ->groupBy('s.id')
      ->groupBy('s.status')
      ->groupBy('s.timestamp')
      ->groupBy('s.source')
      ->orderByHeader($header);
    $results = $query->execute();

    $rows = [];
    $statuses = $this->getReportStatusOptions();
    foreach ($results as $result) {
      $row = [];

      $row['id'] = $result->id;

      $url = Url::fromUri($result->source, ['attributes' => ['target' => '_blank']]);
      $base_url = \Drupal::request()->getSchemeAndHttpHost();
      $row['source'] = Link::fromTextAndUrl(str_replace($base_url, '', $result->source), $url);

      $row['timestamp'] = $this->dateFormatter->format($result->timestamp);
      $row['status'] = $statuses[$result->status];
      $row['violations'] = $result->violations;

      // Add in the operations.
      $operations = [];
      if ($result->status == Sa11yInterface::CREATED || $result->status == Sa11yInterface::RUNNING) {
        $operations['cancel'] = [
          'title' => $this->t('Cancel'),
          'url' => Url::fromRoute('sa11y.report_cancel', ['report_id' => $result->id]),
        ];
      }

      if ($result->status == Sa11yInterface::COMPLETE) {
        $operations['view'] = [
          'title' => $this->t('View'),
          'url' => Url::fromRoute('sa11y.report', ['report_id' => $result->id]),
        ];
      }

      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];

      $rows[] = $row;
    }

    $build['sa11y_report_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No reports available.'),
      '#attributes' => ['id' => 'admin-sa11y', 'class' => ['admin-sa11y']],
      '#bordered' => TRUE,
      '#striped' => TRUE,
      '#attached' => [
        'library' => ['sa11y/admin'],
      ],
    ];
    $build['sa11y_report_pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Displays details about a specific report.
   *
   * @param int $report_id
   *   Unique ID of the database log message.
   *
   * @return array
   *   If the ID is located in the table, a build array in the
   *   format expected by drupal_render();
   */
  public function renderReport($report_id) {

    $build['sa11y_filter_form'] = $this->formBuilder->getForm(
      'Drupal\sa11y\Form\Sa11yFilterForm',
      $report_id,
      $this->getFilters($report_id)
    );

    $header = [
      [''],
      ['data' => $this->t('URL'), 'field' => 'url'],
      ['data' => $this->t('Type'), 'field' => 'type'],
      ['data' => $this->t('Rules')],
      ['data' => $this->t('Impact'), 'field' => 'impact'],
      ['data' => $this->t('Help Link')],
      ['data' => $this->t('HTML')],
      ['data' => $this->t('Message')],
      ['data' => $this->t('DOM Element'), 'field' => 'dom'],
    ];

    $query = $this->database->select('sa11y_data', 'd')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query
      ->fields('d', [
        'url',
        'type',
        'rule',
        'impact',
        'help',
        'html',
        'message',
        'dom',
      ]);
    $query->join('sa11y', 's', 's.id = d.report_id');
    $query->condition('s.id', $report_id);

    // Add any filtered items to the conditions.
    $filters = $this->buildFilterQuery($report_id);
    if (!empty($filters)) {
      foreach ($filters as $field => $args) {
        $and_condition = $query->orConditionGroup();
        foreach ($args as $value) {
          if ($field == 'rule') {
            $and_condition->where('FIND_IN_SET(:rule, d.rule)', [':rule' => $value]);
          }
          else {
            $and_condition->condition('d.' . $field, $value);
          }

        }
        $query->condition($and_condition);
      }
    }

    // @TODO: Need to look at limit / hiding text for visibility
    $results = $query
      ->limit(25)
      ->orderByHeader($header)
      ->execute();

    $rows = [];
    foreach ($results as $result) {
      $row = [];

      $icon = drupal_get_path('module', 'sa11y') . '/icons/exclaim.svg#exclaim';
      $row['icon'] = Markup::create('
          <svg class="icon" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
            <title>icon</title>
            <use xlink:href="/' . $icon . '" />
          </svg>');

      $url = Url::fromUri($result->url, ['attributes' => ['target' => '_blank']]);
      $base_url = \Drupal::request()->getSchemeAndHttpHost();
      $row['url'] = Link::fromTextAndUrl(str_replace($base_url, '', $result->url), $url);

      $row['type'] = $result->type;
      $row['rule'] = $result->rule;
      $row['impact'] = $result->impact;

      $url = Url::fromUri($result->help, ['attributes' => ['target' => '_blank']]);
      $row['help'] = Link::fromTextAndUrl($this->t('Details'), $url);

      $row['html'] = $result->html;
      $row['message'] = $result->message;
      $row['dom'] = $result->dom;

      $rows[] = ['data' => $row, 'class' => [$result->impact]];
    }

    $build['sa11y_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No violations found.'),
      '#attributes' => ['id' => 'admin-sa11y', 'class' => ['admin-sa11y']],
      '#bordered' => TRUE,
      '#striped' => TRUE,
      '#sticky' => TRUE,
      '#attached' => [
        'library' => ['sa11y/admin'],
      ],
    ];
    $build['sa11y_pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Receives the report from the API and processes.
   *
   * @TODO: try/catch and return proper responses.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   A 200 response for now.
   */
  public function receive() {
    $this->sa11y->receive();
    return new Response(200, [], 'OK');
  }

  /**
   * Builds a query for Sa11y report filters based on session.
   *
   * @return array
   *   An associative array with keys 'where' and 'args'.
   */
  protected function buildFilterQuery($report_id) {
    if (empty($_SESSION['sa11y_report_filter_' . $report_id])) {
      return [];
    }

    // Load the filters.
    $filters = $this->getFilters($report_id);

    // Build query.
    $conditions = [];
    foreach ($_SESSION['sa11y_report_filter_' . $report_id] as $key => $filter) {
      // Ensure items exist.
      if (isset($filters[$key])) {
        foreach ($filter as $value) {
          $conditions[$key][] = $value;
        }
      }
    }
    return $conditions;
  }

  /**
   * Gets all the type Types pertaining to a report.
   *
   * @param int $report_id
   *   The report to filter by.
   *
   * @return array
   *   An array of rule types.
   */
  protected function getTypeTypesByReport($report_id) {
    $query = $this->database->select('sa11y_data', 'd')
      ->condition('d.report_id', $report_id);
    $query->addExpression('DISTINCT type', 'type');
    $results = $query->execute();

    $types = [];
    foreach ($results as $row) {
      $types[$row->type] = ucfirst($row->type);
    }
    return $types;
  }

  /**
   * Gets all the Rules pertaining to a report.
   *
   * @param int $report_id
   *   The report to filter by.
   *
   * @return array
   *   An array of rules.
   */
  protected function getRuleTypesByReport($report_id) {
    $query = $this->database->select('sa11y_data', 'd')
      ->condition('d.report_id', $report_id);
    $query->addExpression('DISTINCT rule', 'rule');
    $results = $query->execute();

    $types = [];
    foreach ($results as $row) {
      $split = explode(',', $row->rule);
      foreach ($split as $rule) {
        $types[$rule] = ucfirst($rule);
      }
    }
    ksort($types);

    return $types;
  }

  /**
   * Gets all the Impact Types pertaining to a report.
   *
   * @param int $report_id
   *   The report id to filter by.
   *
   * @return array
   *   An array of impact types.
   */
  protected function getImpactTypesByReport($report_id) {
    $query = $this->database->select('sa11y_data', 'd')
      ->condition('d.report_id', $report_id);
    $query->addExpression('DISTINCT impact', 'impact');
    $results = $query->execute();

    $types = [];
    foreach ($results as $row) {
      $types[$row->impact] = ucfirst($row->impact);
    }
    return $types;
  }

  /**
   * Creates a list of Sa11y report filters that can be applied.
   *
   * @return array
   *   Associative array of filters. The top-level keys are used as the form
   *   element names for the filters, and the values are arrays with the
   *   following elements:
   *   - title: Title of the filter.
   *   - where: The filter condition.
   *   - options: Array of options for the select list for the filter.
   */
  protected function getFilters($report_id) {
    $filters = [];

    $typeTypes = $this->getTypeTypesByReport($report_id);
    if (!empty($typeTypes)) {
      $filters['type'] = [
        'title' => t('Type'),
        'options' => $typeTypes,
      ];
    }

    $ruleTypes = $this->getRuleTypesByReport($report_id);
    if (!empty($ruleTypes)) {
      $filters['rule'] = [
        'title' => t('Rule'),
        'options' => $ruleTypes,
      ];
    }

    $impactTypes = $this->getImpactTypesByReport($report_id);
    if (!empty($impactTypes)) {
      $filters['impact'] = [
        'title' => t('Impact'),
        'options' => $impactTypes,
      ];
    }

    return $filters;
  }

}
