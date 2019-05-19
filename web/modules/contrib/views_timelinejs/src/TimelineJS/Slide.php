<?php

namespace Drupal\views_timelinejs\TimelineJS;

/**
 * Defines a TimelineJS3 slide.
 */
class Slide implements SlideInterface {

  /**
   * The slide start date.
   *
   * @var \Drupal\views_timelinejs\TimelineJS\DateInterface
   */
  protected $startDate;

  /**
   * The slide end date.
   *
   * @var \Drupal\views_timelinejs\TimelineJS\DateInterface
   */
  protected $endDate;

  /**
   * The slide headline and text.
   *
   * @var \Drupal\views_timelinejs\TimelineJS\TextInterface
   */
  protected $text;

  /**
   * The slide media and its metadata.
   *
   * @var \Drupal\views_timelinejs\TimelineJS\MediaInterface
   */
  protected $media;

  /**
   * The slide group.
   *
   * @var string
   */
  protected $group;

  /**
   * The slide display date.
   *
   * @var string
   */
  protected $displayDate;

  /**
   * The slide background url and color.
   *
   * @var \Drupal\views_timelinejs\TimelineJS\BackgroundInterface
   */
  protected $background;

  /**
   * The slide autolink property.
   *
   * @var bool
   */
  protected $autolink = TRUE;

  /**
   * The slide unique id.
   *
   * @var int|string
   */
  protected $uniqueId;

  /**
   * Constructs a new Slide object.
   *
   * @param \Drupal\views_timelinejs\TimelineJS\DateInterface $start_date
   *   The slide's start date.
   * @param \Drupal\views_timelinejs\TimelineJS\DateInterface|null $end_date
   *   The slide's end date.
   * @param \Drupal\views_timelinejs\TimelineJS\TextInterface|null $text
   *   Text to display on the slide.
   */
  public function __construct(DateInterface $start_date, DateInterface $end_date = NULL, TextInterface $text = NULL) {
    $this->startDate = $start_date;
    if (!empty($end_date)) {
      $this->endDate = $end_date;
    }
    if (!empty($text)) {
      $this->text = $text;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setMedia(MediaInterface $media) {
    $this->media = $media;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup($group) {
    $this->group = $group;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplayDate($display_date) {
    $this->displayDate = $display_date;
  }

  /**
   * {@inheritdoc}
   */
  public function setBackground(BackgroundInterface $backgound) {
    $this->background = $backgound;
  }

  /**
   * {@inheritdoc}
   */
  public function setUniqueId($id) {
    $this->uniqueId = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function enableAutolink() {
    $this->autolink = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function disableAutolink() {
    $this->autolink = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildArray() {
    $slide = ['start_date' => $this->startDate->buildArray()];
    // Don't render end dates that are the same as the start date.  TimelineJS
    // won't display them anyway, but skipping them can make the rendered data
    // array smaller.
    if (!empty($this->endDate) && $this->startDate != $this->endDate) {
      $slide['end_date'] = $this->endDate->buildArray();
    }
    if (!empty($this->text)) {
      $slide['text'] = $this->text->buildArray();
    }
    if (!empty($this->media)) {
      $slide['media'] = $this->media->buildArray();
    }
    if (!empty($this->group)) {
      $slide['group'] = $this->group;
    }
    if (!empty($this->displayDate)) {
      $slide['display_date'] = $this->displayDate;
    }
    if (!empty($this->background)) {
      $slide['background'] = $this->background->buildArray();
    }
    if (!$this->autolink) {
      $slide['autolink'] = FALSE;
    }
    if (!empty($this->uniqueId)) {
      $slide['unique_id'] = $this->uniqueId;
    }
    // Filter any empty values before returning.
    return array_filter($slide);
  }

}
