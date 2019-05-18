<?php

namespace Drupal\moon_phases\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use DateTime;
use Drupal\moon_phases\MoonCalc;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MoonPhasesMonthlyController.
 */
class MoonPhasesMonthlyController extends ControllerBase {

  /**
   * The moon phase object.
   *
   * @var \Drupal\moon_phases\MoonCalc
   */
  protected $moon;

  /**
   * The starting day of the month date object.
   *
   * @var \DateTime
   */
  protected $monthStart;

  /**
   * The ending day of the month date object.
   *
   * @var \DateTime
   */
  protected $monthEnd;

  /**
   * The previous month name.
   *
   * @var string
   */
  protected $previousMonth;

  /**
   * The next month name.
   *
   * @var string
   */
  protected $nextMonth;

  /**
   * The date object to use for iteration.
   *
   * @var \DateTime
   */
  protected $iterationDate;

  /**
   * An array containing the moons for a given month.
   *
   * @var array
   */
  protected $monthMoons = [];

  /**
   * The Moon Phases config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   *   The Moon Phases config settings.
   */
  protected $moonConfigSettings;

  /**
   * MoonPhasesMonthlyController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Moon Phases config settings.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->moonConfigSettings = $configFactory->get('moon_phases.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Creates the Moon Phase calendar page.
   *
   * @return array
   *   Returns calendar render array.
   */
  public function content($month) {
    $this->setMonthStart($month);
    $this->setMonthEnd();
    $this->setIterationDate();

    $this->moon = new MoonCalc($this->monthStart);

    return [
      '#title' => $this->monthStart->format('F Y'),
      '#markup' => $this->buildContent(),
    ];
  }

  /**
   * Builds the render array and returns rendered HTML.
   *
   * @return string
   *   Returns the rendered HTML.
   */
  public function buildContent() {
    $this->getMonthsMoons();
    $this->padCalendar();
    $attributionURL = Url::fromUri('https://svs.gsfc.nasa.gov', [
      'attributes' => [
        'class' => [
          'attribution',
        ],
        'target' => '_blank',
      ],
    ]);

    $attribution = [
      '#title' => $this->t("Images courtesy of NASA's Scientific Visualization Studio"),
      '#type' => 'link',
      '#url' => $attributionURL,
    ];

    $build = [
      '#type' => 'calendar',
      '#theme' => 'moon_calendar',
      '#previous' => [
        '#type' => 'link',
        '#url' => $this->getPreviousLink(),
        '#title' => $this->t('%month', ['%month' => $this->previousMonth]),
        '#attributes' => [
          'class' => 'moon-prev',
        ],
      ],
      '#next' => [
        '#type' => 'link',
        '#url' => $this->getNextLink(),
        '#title' => $this->t('%month', ['%month' => $this->nextMonth]),
        '#attributes' => [
          'class' => 'moon-next',
        ],
      ],
      '#header' => [
        '#markup' => $this->getHeader(),
      ],
      '#calendar' => [
        '#markup' => $this->buildArray(),
      ],
      '#attribution' => ($this->showAttribution()) ? $attribution : [],
    ];

    return render($build);
  }

  /**
   * Creates the calendar header.
   *
   * @return string
   *   Returns the rendered HTML.
   */
  public function getHeader() {
    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    $header = [
      '#type' => 'header',
    ];
    foreach ($days as $day) {
      $header[] = [
        '#markup' => '<div class="' . strtolower($day) . '">' . $day . '</div>',
      ];
    }

    return render($header);
  }

  /**
   * Gets all of the moons for a given month.
   */
  public function getMonthsMoons() {
    $iterateDate = $this->iterationDate;
    $endDate = $this->getDateAsTimestamp($this->monthEnd);

    while ($this->getDateAsTimestamp($iterateDate) <= $endDate) {
      $moon = new MoonCalc($iterateDate);
      $this->monthMoons[$iterateDate->format('j')] = [
        'phase_name' => $moon->getMoonPhaseName(),
        'image' => $moon->getImageUri(),
        'date' => $iterateDate->format('j'),
        'full_date' => $iterateDate->format('l, F jS, Y'),
        'day_of_week' => $iterateDate->format('w'),
        'weekday' => strtolower($iterateDate->format('l')),
      ];
      $iterateDate->modify('+1 day');
    }
  }

  /**
   * Builds the render array for all of the days of the month.
   *
   * @return string
   *   Returns the rendered HTML.
   */
  private function buildArray() {
    $calendar = [];
    foreach ($this->monthMoons as $day) {
      // If this item is a placeholder, add an empty item to the render array.
      if (array_key_exists('placeholder', $day)) {
        $calendar[] = [
          '#type' => 'moon',
          '#theme' => 'moon_day',
          '#phase_name' => [
            '#markup' => '&nbsp;',
          ],
          '#attributes' => [
            'class' => [
              'moon-calendar-day',
              'placeholder',
            ],
          ],
        ];
      }
      // Add the moon phase data to the render array.
      else {
        $calendar[] = [
          '#type' => 'moon',
          '#theme' => 'moon_day',
          '#image' => [
            '#theme' => 'image',
            '#uri' => $day['image'],
            '#alt' => $day['phase_name'],
            '#title' => $day['phase_name'],
            '#attributes' => [
              'class' => [
                'moon',
              ],
              'data-attribution' => [
                "NASA's Scientific Visualization Studio",
              ],
            ],
          ],
          '#full_date' => [
            '#markup' => $day['full_date'],
          ],
          '#day' => [
            '#markup' => $day['date'],
          ],
          '#phase_name' => [
            '#markup' => $day['phase_name'],
          ],
          '#attributes' => [
            'class' => [
              $day['weekday'],
              'moon-calendar-day',
              'moon-day',
            ],
          ],
        ];
      }
    }

    return render($calendar);
  }

  /**
   * Pad the beginning of the month for months that start after Sunday.
   */
  private function padCalendar() {
    $first = reset($this->monthMoons);
    if ($first['day_of_week'] > 0) {
      $count = $first['day_of_week'];
      $placeholder = [
        'placeholder' => TRUE,
      ];
      for ($i = $count; $i > 0; $i--) {
        array_unshift($this->monthMoons, $placeholder);
      }
    }
  }

  /**
   * Create the previous day link.
   *
   * @return \Drupal\Core\Url
   *   Returns the URL object for the previous month link.
   */
  private function getPreviousLink() {
    $previousMonth = new DateTime($this->monthStart->format('Y-m-d'));
    $url = Url::fromRoute('moon_phases.current', ['month' => $previousMonth->modify('-1 month')->format('Y-m')], [
      'attributes' => [
        'class' => [
          'moon-prev',
        ],
      ],
    ]);
    $this->previousMonth = $previousMonth->format('F');
    return $url;
  }

  /**
   * Create the previous day link.
   *
   * @return \Drupal\Core\Url
   *   Returns the URL object for the next month link.
   */
  private function getNextLink() {
    $nextMonth = new DateTime($this->monthStart->format('Y-m-d'));
    $url = Url::fromRoute('moon_phases.current', ['month' => $nextMonth->modify('+1 month')->format('Y-m')], [
      'attributes' => [
        'class' => [
          'moon-prev',
        ],
      ],
    ]);
    $this->nextMonth = $nextMonth->format('F');
    return $url;
  }

  /**
   * Checks whether or not to show the image attribution.
   *
   * @return bool
   *   Returns 1 to show the attribution, else 0.
   */
  private function showAttribution() {
    return $this->moonConfigSettings->get('show_attribution');
  }

  /**
   * Set the start of the month.
   *
   * @param mixed $date
   *   The month to display or null for current month.
   */
  private function setMonthStart($date) {
    $setDate = ($date) ? $date : date('Y-m');
    $this->monthStart = new DateTime($setDate);
  }

  /**
   * Set the last day of the month.
   */
  private function setMonthEnd() {
    $monthEnd = new DateTime($this->monthStart->format('Y-m-d'));
    $this->monthEnd = new DateTime($monthEnd->modify('last day of this month')->format('Y-m-d'));
  }

  /**
   * Create the \DateTime object to create the days of the month.
   */
  private function setIterationDate() {
    $this->iterationDate = new DateTime($this->monthStart->format('Y-m-d'));
  }

  /**
   * Creates a Unix timestamp for a given DateTime object.
   *
   * @param \DateTime $date
   *   The date to transform.
   *
   * @return int
   *   Returns the date as a Unix timestamp.
   */
  private function getDateAsTimestamp(DateTime $date) {
    return strtotime($date->format('Y-m-d'));
  }

}
