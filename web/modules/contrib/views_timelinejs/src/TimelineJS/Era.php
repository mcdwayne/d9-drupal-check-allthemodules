<?php

namespace Drupal\views_timelinejs\TimelineJS;

/**
 * Defines a TimelineJS3 era.
 */
class Era implements EraInterface {

  /**
   * The era start date.
   *
   * @var \Drupal\views_timelinejs\TimelineJS\DateInterface
   */
  protected $startDate;

  /**
   * The era end date.
   *
   * @var \Drupal\views_timelinejs\TimelineJS\DateInterface
   */
  protected $endDate;

  /**
   * The era headline and text.
   *
   * @var \Drupal\views_timelinejs\TimelineJS\TextInterface
   */
  protected $text;

  /**
   * Constructs a new Era object.
   *
   * @param \Drupal\views_timelinejs\TimelineJS\DateInterface $start_date
   *   The era's start date.
   * @param \Drupal\views_timelinejs\TimelineJS\DateInterface $end_date
   *   The era's end date.
   * @param \Drupal\views_timelinejs\TimelineJS\TextInterface|null $text
   *   Text to display on the era.
   */
  public function __construct(DateInterface $start_date, DateInterface $end_date, TextInterface $text = NULL) {
    $this->startDate = $start_date;
    $this->endDate = $end_date;
    if (!empty($text)) {
      $this->text = $text;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildArray() {
    $era = [
      'start_date' => $this->startDate->buildArray(),
      'end_date' => $this->endDate->buildArray(),
    ];
    if (!empty($this->text)) {
      $era['text'] = $this->text->buildArray();
    }
    return $era;
  }

}
