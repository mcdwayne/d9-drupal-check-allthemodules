<?php

namespace Drupal\moon_phases\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use DateTime;
use Drupal\moon_phases\MoonCalc;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MoonPhasesController.
 */
class MoonPhasesController extends ControllerBase {

  /**
   * The moon phase object.
   *
   * @var \Drupal\moon_phases\MoonCalc
   */
  protected $moon;

  /**
   * An array containing the data pertaining to a phase of the moon.
   *
   * @var array
   */
  protected $phaseData = [];

  /**
   * The current date object.
   *
   * @var \DateTime
   */
  protected $currentDate;

  /**
   * The date object of the moon phase to display.
   *
   * @var \DateTime
   */
  protected $phaseDate;

  /**
   * The description of the moon phase.
   *
   * @var string
   */
  protected $phaseDescription;

  /**
   * The Moon Phases config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $moonConfigSettings;

  /**
   * MoonPhasesController constructor.
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
   * Creates the default Moon Phase page.
   *
   * @return array
   *   Returns rendered HTML.
   */
  public function content($date) {
    $this->setCurrentDate($date);
    $this->moon = new MoonCalc($this->currentDate);
    $this->setPhaseData();
    $this->setPhaseDescription();

    return $this->buildPage();
  }

  /**
   * Collates all of the Moon Phase data and returns the rendered page.
   *
   * @return array
   *   Returns a render array.
   */
  public function buildPage() {
    $this->title = $this->moon->getMoonPhaseName();
    $description = $this->phaseDescription;
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

    $items = [];

    foreach ($this->phaseData as $key => $days) {
      if ($key == 'illumination') {
        $items[] = $this->t('%illum% illumination', ['%illum' => $days]);
      }
      else {
        $items[] = $this->t('%days days until next %phase',
            ['%days' => $days, '%phase' => $key]);
      }
    }

    // Get an unorderd list.
    $itemList = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $items,
      '#title' => '',
      '#attributes' => [
        'class' => ['moon-phase-data'],
      ],
    ];
    $phaseData = render($itemList);

    $build = [
      '#type' => 'moon',
      '#theme' => 'moon',
      '#image' => [
        '#theme' => 'image',
        '#uri' => $this->moon->getImageUri(),
        '#alt' => $this->moon->getMoonPhaseName(),
        '#title' => $this->moon->getMoonPhaseName(),
        '#attributes' => [
          'class' => [
            'moon',
          ],
          'data-attribution' => [
            "NASA's Scientific Visualization Studio",
          ],
        ],
      ],
      '#description' => [
        '#markup' => $description,
      ],
      '#phase_data' => [
        '#markup' => $phaseData,
      ],
      '#previous' => [
        '#title' => $this->t('Previous'),
        '#type' => 'link',
        '#url' => $this->getPreviousLink(),
      ],
      '#next' => [
        '#title' => $this->t('Next'),
        '#type' => 'link',
        '#url' => $this->getNextLink(),
      ],
      '#attribution' => ($this->showAttribution()) ? $attribution : [],
      '#attributes' => [
        'class' => [
          strtolower(str_replace(' ', '-', $this->moon->getMoonPhaseName())),
        ],
      ],
    ];
    $content = render($build);

    return [
      '#title' => $this->currentDate->format('F jS, Y') . ': ' . $this->moon->getMoonPhaseName(),
      '#markup' => $content,
    ];
  }

  /**
   * Sets the Moon Phase data and set the $phaseData array.
   */
  public function setPhaseData() {
    $this->phaseData = [
      'New Moon' => floor($this->moon->getDaysUntilNextNewMoon()),
      'First Quarter' => floor($this->moon->getDaysUntilNextFirstQuarterMoon()),
      'Full Moon' => floor($this->moon->getDaysUntilNextFullMoon()),
      'Third Quarter' => floor($this->moon->getDaysUntilNextLastQuarterMoon()),
      'illumination' => $this->moon->getPercentOfIllumination(),
    ];
  }

  /**
   * Returns the previous day Url object.
   *
   * @return \Drupal\Core\Url
   *   Returns the previous day Url object.
   */
  public function getPreviousLink() {
    $previousDay = new DateTime($this->moon->getMoonPhaseDate()->format('Y-m-d'));
    $url = Url::fromRoute('moon_phases.content', ['date' => $previousDay->modify('-1 day')->format('Y-m-d')], [
      'attributes' => [
        'class' => [
          'moon-prev',
        ],
      ],
    ]);
    return $url;
  }

  /**
   * Returns the next day Url object.
   *
   * @return \Drupal\Core\Url
   *   Returns the next day Url object.
   */
  public function getNextLink() {
    $nextDay = new DateTime($this->moon->getMoonPhaseDate()->format('Y-m-d'));
    $url = Url::fromRoute('moon_phases.content', ['date' => $nextDay->modify('+1 day')->format('Y-m-d')], [
      'attributes' => [
        'class' => [
          'moon-next',
        ],
      ],
    ]);
    return $url;
  }

  /**
   * Checks whether or not to show the image attribution.
   *
   * @return bool
   *   Returns 1 to show the attribution, else 0.
   */
  public function showAttribution() {
    return $this->moonConfigSettings->get('show_attribution');
  }

  /**
   * Set the description, either from the settings or the default.
   */
  public function setPhaseDescription() {
    $name = strtolower(str_replace(' ', '_', $this->moon->getMoonPhaseName()));
    $default = constant('MOON_PHASE_' . strtoupper(str_replace(' ', '_', $this->moon->getMoonPhaseName())));

    $this->phaseDescription = ($this->moonConfigSettings->get($name)) ? $this->moonConfigSettings->get($name) : $default;
  }

  /**
   * Set the DateTime object to use for the moon phase.
   *
   * @param string $date
   *   The date to set.
   */
  private function setCurrentDate($date) {
    $setDate = ($date) ? $date . date(' H:i:s', time()) : date('Y-m-d H:i:s');
    $this->currentDate = new DateTime($setDate);
  }

}
