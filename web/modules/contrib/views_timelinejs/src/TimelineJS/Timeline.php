<?php

namespace Drupal\views_timelinejs\TimelineJS;

/**
 * Defines a TimelineJS3 timeline.
 */
class Timeline implements TimelineInterface {

  /**
   * The timeline scale.
   *
   * @var string
   */
  protected $scale = 'human';

  /**
   * The timeline's title slide.
   *
   * @var \Drupal\views_timelinejs\TimelineJS\SlideInterface
   */
  protected $titleSlide;

  /**
   * The timeline's array of event slides.
   *
   * @var \Drupal\views_timelinejs\TimelineJS\SlideInterface[]
   */
  protected $events = [];

  /**
   * The timeline's array of eras.
   *
   * @var \Drupal\views_timelinejs\TimelineJS\EraInterface[]
   */
  protected $eras = [];

  /**
   * {@inheritdoc}
   */
  public function setTitleSlide(SlideInterface $slide) {
    $this->titleSlide = $slide;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitleSlide() {
    return $this->titleSlide;
  }

  /**
   * {@inheritdoc}
   */
  public function addEvent(SlideInterface $slide) {
    $this->events[] = $slide;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvents() {
    return $this->events;
  }

  /**
   * {@inheritdoc}
   */
  public function addEra(EraInterface $era) {
    $this->eras[] = $era;
  }

  /**
   * {@inheritdoc}
   */
  public function getEras() {
    return $this->eras;
  }

  /**
   * {@inheritdoc}
   */
  public function setScaleToHuman() {
    $this->scale = 'human';
  }

  /**
   * {@inheritdoc}
   */
  public function setScaleToCosomological() {
    $this->scale = 'cosomological';
  }

  /**
   * {@inheritdoc}
   */
  public function getScale() {
    return $this->scale;
  }

  /**
   * {@inheritdoc}
   */
  public function buildArray() {
    $timeline = ['scale' => $this->scale];
    if (!empty($this->titleSlide)) {
      $timeline['title'] = $this->titleSlide->buildArray();
    }
    foreach ($this->events as $event) {
      $timeline['events'][] = $event->buildArray();
    }
    foreach ($this->eras as $era) {
      $timeline['eras'][] = $era->buildArray();
    }
    return $timeline;
  }

}
