<?php

namespace Drupal\tc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\Time;

/**
 * Returns responses for TC routes.
 */
class TcDisplayController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $timeService;

  /**
   * Constructs a TcDisplayController object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   * @param \Drupal\Component\Datetime\Time $timeService
   *   The time service.
   */
  public function __construct(Connection $connection, DateFormatterInterface $dateFormatter, Time $timeService) {
    $this->connection = $connection;
    $this->dateFormatter = $dateFormatter;
    $this->timeService = $timeService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = $container->get('database');
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter */
    $dateFormatter = $container->get('date.formatter');
    /** @var \Drupal\Component\Datetime\Time $timeService */
    $timeService = $container->get('datetime.time');
    return new static(
      $connection,
      $dateFormatter,
      $timeService
    );
  }

  /**
   * Returns the data for given user's given field in a given period.
   *
   * @param $uid
   *   The user's ID to retrieve data on behalf of.
   * @param $field
   *   The field to retrieve data from.
   * @param $period
   *   The period, going back from REQUEST_TIME. May be one of the indices
   *   returned by _tc_get_periods().
   *
   * @return array|bool
   *   Boolean FALSE if no data found; an array of labels and data otherwise.
   *   - labels: An array of timestamps to be displayed. This is based on the
   *     given period, their format is provided by _tc_get_dateformats().
   *   - data: An array of (float) data points to be displayed.
   *   - min:
   *     - time: Time of the minimum value in the given constraints.
   *     - value: The minimum value in the given constraints.
   *   - max:
   *     - time: Time of the maximum value in the given constraints.
   *     - value: The minimum value in the given constraints.
   *   - last:
   *     - time: Time of the last value in the given constraints.
   *     - value: The last value in the given constraints.
   *
   * @see _tc_get_periods()
   * @see _tc_get_dateformats()
   */
  private function getDataDirect($uid, $field, $period) {
    $return = [];
    $periods = _tc_get_periods();
    $dateformats = _tc_get_dateformats();
    // This helps devices with less computing power to display graphs by
    // limiting the number of points to render. For a single day, we are
    // displaying one data point for 10 minutes worth of averaged data, which
    // means 144 points. For longer periods, we are still displaying 144 points,
    // but covering the whole period.
    $multiplier = 60 * 10 * $periods[$period];
    // This is an example SQL query we expect to be generated for a single day.
    /*
    SELECT timestamp, AVG(field_value)
    FROM tc_data
    WHERE uid = 1
    AND field_id = 'a'
    AND timestamp >= 1441950750 - 60 * 60 * 24
    GROUP BY ROUND(timestamp/10/60)*10*60
    ORDER BY timestamp ASC
     */
    $query = $this->connection->select('tc_data', 'td');
    $query->addExpression('ROUND(timestamp/' . $multiplier . ')*' . $multiplier, 'round_timestamp');
    $query->addExpression('AVG(field_value)', 'avg_field_value');
    $timestamp = $this->timeService->getRequestTime() - 60 * 60 * 24 * $periods[$period];
    $records = $query->condition('uid', $uid)
      ->condition('field_id', $field)
      ->condition('timestamp', $timestamp, '>=')
      ->groupBy('round_timestamp')
      ->orderBy('round_timestamp', 'ASC')
      ->execute();
    $label_prev = '';
    foreach ($records as $row) {
      $label = $this->dateFormatter->format($row->round_timestamp, 'custom', $dateformats[$period]);
      if ($label != $label_prev) {
        $label_prev = $label;
        $return['labels'][] = $label;
      }
      else {
        $return['labels'][] = '';
      }
      $return['data'][] = (float) $row->avg_field_value;
    }
    if (empty($return)) {
      return FALSE;
    }
    // Retrieve the min and max values with timestamps for the given period.
    $row = $this->connection->select('tc_data', 'td')
      ->fields('td', ['timestamp', 'field_value'])
      ->condition('uid', $uid)
      ->condition('field_id', $field)
      ->condition('timestamp', $timestamp, '>=')
      ->orderBy('field_value', 'ASC')
      ->orderBy('timestamp', 'ASC')
      ->range(0, 1)
      ->execute()
      ->fetch();
    $return['min'] = [
      'time' => $this->dateFormatter->format($row->timestamp),
      'value' => (float) $row->field_value,
    ];
    $row = $this->connection->select('tc_data', 'td')
      ->fields('td', ['timestamp', 'field_value'])
      ->condition('uid', $uid)
      ->condition('field_id', $field)
      ->condition('timestamp', $timestamp, '>=')
      ->orderBy('field_value', 'DESC')
      ->orderBy('timestamp', 'ASC')
      ->range(0, 1)
      ->execute()
      ->fetch();
    $return['max'] = [
      'time' => $this->dateFormatter->format($row->timestamp),
      'value' => (float) $row->field_value,
    ];
    // Retrieve last value with timestamps for the given period.
    $row = $this->connection->select('tc_data', 'td')
      ->fields('td', ['timestamp', 'field_value'])
      ->condition('uid', $uid)
      ->condition('field_id', $field)
      ->condition('timestamp', $timestamp, '>=')
      ->orderBy('timestamp', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetch();
    $return['last'] = [
      'time' => $this->dateFormatter->format($row->timestamp),
      'value' => (float) $row->field_value,
    ];
    return $return;
  }

  /**
   * Display data for the last week for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface|NULL $user
   *   The user to retrieve data on behalf of.
   *
   * @return array
   *   Render array with all the results.
   */
  public function displayWeek(AccountInterface $user = NULL) {
    return $this->display('week', $user);
  }

  /**
   * Display data for the last month for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface|NULL $user
   *   The user to retrieve data on behalf of.
   *
   * @return array
   *   Render array with all the results.
   */
  public function displayMonth(AccountInterface $user = NULL) {
    return $this->display('month', $user);
  }

  /**
   * Display data for the last year for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface|NULL $user
   *   The user to retrieve data on behalf of.
   *
   * @return array
   *   Render array with all the results.
   */
  public function displayYear(AccountInterface $user = NULL) {
    return $this->display('year', $user);
  }

  /**
   * Display a given period's data for the given user.
   *
   * @param string $period
   *   The period, going back from REQUEST_TIME. May be one of the indices
   *   returned by _tc_get_periods().
   * @param \Drupal\Core\Session\AccountInterface|NULL $user
   *   The user to retrieve data on behalf of.
   *
   * @return array
   *   Render array with all the results.
   *
   * @see _tc_get_periods()
   */
  public function display($period = 'day', AccountInterface $user = NULL) {
    $uid = $user->id();

    // Retrieve settings for the user.
    $settings = _tc_get_settings($this->connection, $uid);
    if (!$settings) {
      // @FIXME: Error handling: Settings not found.
      return [
        '#markup' => $this->t('Settings not found.'),
      ];
    }
    $settings = $settings['settings'];
    $enabled_fields = array_filter($settings['field_enabled']);
    if (!$enabled_fields) {
      // @FIXME: Error handling: No enabled fields.
      return [
        '#markup' => $this->t('No enabled fields.'),
      ];
    }

    // Build the output (as a render array).
    $output = [];
    $data = [
      'datasets' => [],
    ];
    foreach (array_keys($enabled_fields) as $field) {
      // Retrieve data for the current field.
      $retrieved_data = $this->getDataDirect($uid, $field, $period);
      if (!$retrieved_data) {
        $output[$field] = [
          // @TODO: A theme call? a Twig template?
          'header' => [
            // @FIXME: check_plain.
            '#markup' => '<h2>' . $settings['field_name'][$field] . '</h2>',
          ],
          'message' => [
            '#markup' => $this->t('No data found in this period.'),
          ],
        ];
        continue;
      }
      // Build the JS data for the current field.
      $data[$field]['datasets'][] = [
        'label' => $settings['field_name'][$field], // @TODO: check_plain()?
        'data' => $retrieved_data['data'],
      ];
      $data[$field]['labels'] = $retrieved_data['labels'];
      // Build the render array for the current field.
      $output[$field] = [
        // @TODO: A theme call? a Twig template?
        'header' => [
          // @FIXME: check_plain.
          '#markup' => '<h2>' . $settings['field_name'][$field] . '</h2>',
        ],
        'min' => [
          '#prefix' => '<div>',
          '#markup' => $this->t('Min: %min at %time', [
            // No need to check_plain the value because of the conversion to
            // float in getData().
            '%min' => $retrieved_data['min']['value'],
            '%time' => $retrieved_data['min']['time'],
          ]),
          '#suffix' => '</div>',
        ],
        'max' => [
          '#prefix' => '<div>',
          '#markup' => $this->t('Max: %min at %time', [
            // No need to check_plain the value because of the conversion to
            // float in getData().
            '%min' => $retrieved_data['max']['value'],
            '%time' => $retrieved_data['max']['time'],
          ]),
          '#suffix' => '</div>',
        ],
        'chart' => [
          // For some unknown weird reason if this markup contains the <canvas>
          // as needed by Chart.js, it does not find its way into HTML. So we
          // are adding a wrapper <div> and the <canvas> will be added by tc.js.
          '#markup' => '<div class="tcChart" id="tcChart' . $field . '" data-tcchart="' . $field . '"></div>',
        ],
      ];
    }

    return [
      'charts' => $output,
      '#attached' => [
        // Pushing down data to JS.
        'drupalSettings' => [
          'tc' => $data,
        ],
        // Including our own JS "library". Dependencies for it (like Chart.js,
        // Chart.Scatter and jQuery) are handled by tc.libraries.yml.
        'library' => [
          'tc/tc',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }
}
