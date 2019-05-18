<?php

/**
 * @file
 * Contains \Drupal\rng_date_scheduler\Controller\DateExplain.
 */

namespace Drupal\rng_date_scheduler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\rng_date_scheduler\EventDateProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Cache\Cache;

/**
 * Provides dynamic tasks.
 */
class DateExplain extends ControllerBase {

  /**
   * The related request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The event date provider.
   *
   * @var \Drupal\rng_date_scheduler\EventDateProviderInterface
   */
  protected $eventDateProvider;

  /**
   * Construct a new DateExplain controller.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *  The request stack.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *    The date formatter service.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   * @param \Drupal\rng_date_scheduler\EventDateProviderInterface $event_date_provider
   *   The event date provider.
   */
  function __construct(RequestStack $request_stack, DateFormatterInterface $date_formatter, EventManagerInterface $event_manager, EventDateProviderInterface $event_date_provider) {
    $this->requestStack = $request_stack;
    $this->dateFormatter = $date_formatter;
    $this->eventManager = $event_manager;
    $this->eventDateProvider = $event_date_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('date.formatter'),
      $container->get('rng.event_manager'),
      $container->get('rng_date_scheduler.event_dates')
    );
  }

  public function eventDates(EntityInterface $rng_event) {
    $build = [];
    $build['#cache']['keys'] = ['rng_date_scheduler', 'event_explain', $rng_event->getEntityTypeId(), $rng_event->id()];
    $build['#cache']['tags'] = $rng_event->getCacheTagsToInvalidate();
    $build['#cache']['contexts'] = ['rng_event'];
    $build['#attached']['library'][] = 'rng_date_scheduler/rng_date_scheduler.user';

    $max_age = Cache::PERMANENT;

    if ($event_type = $this->eventManager->eventType($rng_event->getEntityTypeId(), $rng_event->bundle())) {
      $build['#cache']['tags'] = Cache::mergeTags($build['#cache']['tags'], $event_type->getCacheTagsToInvalidate());
    }

    $row = [];
    $row_dates = [];

    $previous_after = NULL;
    $dates = $this->eventDateProvider->getDates($rng_event);
    foreach ($dates as $date) {
      /** @var \Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList $field_item_list */
      $field_item_list = $rng_event->{$date->getFieldName()};

      $before = $date->canAccessBefore();
      $after = $date->canAccessAfter();

      $row[] = $this->permittedCell([$previous_after, $before]);

      $row_dates[]['#plain_text'] = $this->dateFormatter
        ->format($date->getDate()->format('U'), 'long');

      $row[]['#plain_text'] = $field_item_list->getFieldDefinition()
        ->getLabel();

      $previous_after = $after;
    }

    $row[] = $this->permittedCell([$previous_after]);

    $build['table'] = [
      '#type' => 'table',
      '#attributes' => ['class' => 'rng-date-scheduler-explain']
    ];

    // Add the date indicator row.
    $now = DrupalDateTime::createFromTimestamp($this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME'));
    $row_indicator = [];
    $d = 0;
    $current = FALSE;
    for ($i = 0; $i < count($row); $i += 2) {
      $is_last = !isset($dates[$d]);
      if (!$current && ($is_last || $now < $dates[$d]->getDate())) {
        if (!$is_last) {
          $max_age = $dates[$d]->getDate()->format('U') - $now->format('U');
        }
        $row_indicator[] = [
          '#markup' => $this->t('Now'),
          '#wrapper_attributes' => ['class' => ['active-time']]
        ];
        $current = TRUE;
      }
      else {
        $row_indicator[]['#wrapper_attributes'] = ['class' => ['inactive-time']];
        $row_indicator[]['#wrapper_attributes'] = ['class' => ['inactive-time']];
      }
      $d++;
    }

    $build['table'][] = $row;
    $build['table']['dates'] = $row_dates;
    $build['table']['indicator'] = $row_indicator;
    $build['table']['indicator']['#attributes'] = ['class' => ['current-indicator']];

    if (!count($dates)) {
      unset($build['table']);
    }

    $messages = [];

    $fields = $this->eventDateProvider
      ->getFieldAccess($rng_event->getEntityTypeId(), $rng_event->bundle(), TRUE);
    foreach ($fields as $field_name => $field) {
      // Determine fields without dates.
      $field_item_list = $rng_event->{$field_name};
      if ($field_item_list && !count($field_item_list)) {
        $field_label = $field_item_list->getFieldDefinition()
          ->getLabel();
        $messages[] = $this->t('%label is not used as it does not contain a date.', [
          '%label' => $field_label,
        ]);
      }
    }

    if (!count($fields)) {
      // @todo. Do not show 'Date' tab if no date fields are configured.
      // @todo. Remove this message.
      $messages[] = $this->t('No dates fields are configured for events of this type.');
    }

    if (!count($dates)) {
      $default_access = $this->eventDateProvider
        ->getDefaultAccess($rng_event->getEntityTypeId(), $rng_event->bundle());
      if ($default_access === FALSE) {
        $messages[] = $this->t('New registrations are forbidden because there are no dates.');
      }
    }

    if ($messages) {
      $build['messages'] = [
        '#title' => $this->t('Date fields'),
        '#theme' => 'item_list',
        '#items' => $messages,
      ];
    }

    $build['#cache']['max-age'] = $max_age;

    return $build;
  }

  function permittedCell(array $access) {
    $forbidden = in_array(FALSE, $access, TRUE);
    $class = $forbidden ? 'forbidden' : 'neutral';
    $cell = [
      '#wrapper_attributes' => ['class' => [$class], 'rowspan' => 2],
      '#markup' => $forbidden ? $this->t('New registrations forbidden') : $this->t('Neutral'),
    ];
    return $cell;
  }

}
