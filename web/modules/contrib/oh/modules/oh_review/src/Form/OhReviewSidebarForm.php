<?php

namespace Drupal\oh_review\Form;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\oh\OhDateRange;
use Drupal\oh\OhOpeningHoursInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sidebar form.
 */
class OhReviewSidebarForm extends FormBase {

  /**
   * Preview mode: show regular, exclusions, and omissions.
   */
  const PREVIEW_MODE_ALL = 'all';

  /**
   * Preview mode: show regular, exclusions, no omissions.
   */
  const PREVIEW_MODE_ALL_EXCEPT_OMISSIONS = 'all_no_omissions';

  /**
   * Preview mode: show regular only.
   */
  const PREVIEW_MODE_ONLY_REGULAR = 'regular';

  /**
   * Preview mode: show exclusions only.
   */
  const PREVIEW_MODE_ONLY_EXCLUSIONS = 'exclusions';

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The opening hours service.
   *
   * @var \Drupal\oh\OhOpeningHoursInterface
   */
  protected $openingHours;

  /**
   * Constructs a new OhReviewController.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\oh\OhOpeningHoursInterface $openingHours
   *   Opening hours service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, OhOpeningHoursInterface $openingHours) {
    $this->configFactory = $configFactory;
    $this->openingHours = $openingHours;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('oh.opening_hours')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oh_review_sidebar';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The requirement contains the value for which parameter to get the entity.
    // It is never NULL since the IsOhBundle access check verified the request.
    $entityType = $this->getRouteMatch()->getRouteObject()->getRequirement('_is_oh_bundle');
    $entity = $this->getRouteMatch()->getParameter($entityType);

    $id = 'oh-review-sidebar-preview';
    $form['#attributes']['class'][] = 'oh-review-sidebar-preview-container';

    $form['settings'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['oh-review-sidebar-settings'],
      ],
    ];

    $form['settings']['help'] = [
      '#markup' => $this->t('Showing hours for %entity_label from the beginning of this week through one year into the future.', [
        '%entity_label' => $entity->label(),
      ]),
    ];

    $form['settings']['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Show'),
      '#options' => [
        // Todo: save preference against user storage.
        static::PREVIEW_MODE_ALL => $this->t('All, including omissions'),
        static::PREVIEW_MODE_ALL_EXCEPT_OMISSIONS => $this->t('Only regular and exclusions'),
        static::PREVIEW_MODE_ONLY_REGULAR => $this->t('Only regular'),
        static::PREVIEW_MODE_ONLY_EXCLUSIONS => $this->t('Only exclusions'),
      ],
      '#default_value' => static::PREVIEW_MODE_ALL,
      '#ajax' => [
        'callback' => [static::class, 'previewAjaxCallback'],
        'wrapper' => $id,
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['settings']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update preview'),
      '#attributes' => [
        'class' => ['js-hide'],
      ],
    ];

    $form['preview'] = $this->preview($entity, $form_state->getValue('mode') ?? static::PREVIEW_MODE_ALL);
    $form['preview']['#prefix'] = '<div id="' . $id . '">';
    $form['preview']['#suffix'] = '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Normally nothing to do. But non JS get a button.
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for preview area.
   */
  public static function previewAjaxCallback(array $form, FormStateInterface $form_state) {
    return $form['preview'];
  }

  /**
   * Generate the opening hours preview.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get opening hours.
   * @param string $mode
   *   The preview mode. See static::PREVIEW_MODE* constants.
   *
   * @return array
   *   Render array containing the preview.
   */
  protected function preview(EntityInterface $entity, string $mode): array {
    $cachable = (new CacheableMetadata())
      ->addCacheContexts(['timezone'])
      ->addCacheableDependency($entity);

    $rangeStart = $this->getRangeStart();
    $rangeEnd = (clone $rangeStart)
      ->add(new \DateInterval('P1Y'));

    $range = new OhDateRange($rangeStart, $rangeEnd);

    // Get occurrences requested by user.
    // Displaying omissions is handled by template preprocessor.
    switch ($mode) {
      case static::PREVIEW_MODE_ONLY_REGULAR:
        $occurrences = $this->openingHours->getRegularHours($entity, $range);
        break;

      case static::PREVIEW_MODE_ONLY_EXCLUSIONS:
        $occurrences = $this->openingHours->getExceptions($entity, $range);
        break;

      default:
        $occurrences = $this->openingHours->getOccurrences($entity, $range);
        break;
    }

    $build = [
      '#theme' => 'oh_review_occurrences_list',
      '#range' => $range,
      '#occurrences' => $occurrences,
      '#time_separator' => ' to ',
      '#mode' => $mode,
    ];

    $cachable->applyTo($build);
    return $build;
  }

  /**
   * Get the range start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The range start date.
   */
  protected function getRangeStart(): DrupalDateTime {
    $dayMap = [
      'Sunday',
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
    ];

    // Weekday int. 0-6 (Sun-Sat).
    $firstDayInt = $this->configFactory->get('system.date')
      ->get('first_day');
    $firstDayStr = $dayMap[$firstDayInt];
    // Today day int.
    $today = (new DrupalDateTime())->format('w');
    $weekStartString = ($today == $firstDayInt ? '' : 'last ') . $firstDayStr . ' 00:00';
    return new DrupalDateTime($weekStartString);
  }

}
