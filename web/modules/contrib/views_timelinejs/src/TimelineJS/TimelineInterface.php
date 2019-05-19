<?php

namespace Drupal\views_timelinejs\TimelineJS;

/**
 * Provides an interface for defining TimelineJS3 timelines.
 */
interface TimelineInterface extends ObjectInterface {

  /**
   * Adds a new slide to the timeline's events array.
   *
   * @param \Drupal\views_timelinejs\TimelineJS\SlideInterface $slide
   *   The new slide.
   */
  public function addEvent(SlideInterface $slide);

  /**
   * Returns the timeline's array of event slides.
   *
   * @return \Drupal\views_timelinejs\TimelineJS\SlideInterface[]
   *   An array of slides.
   */
  public function getEvents();

  /**
   * Adds a new era to the timeline's eras array.
   *
   * @param \Drupal\views_timelinejs\TimelineJS\EraInterface $era
   *   The new era.
   */
  public function addEra(EraInterface $era);

  /**
   * Returns the timeline's array of eras.
   *
   * @return \Drupal\views_timelinejs\TimelineJS\EraInterface[]
   *   An array of eras.
   */
  public function getEras();

  /**
   * Sets the timeline's title slide.
   *
   * @param \Drupal\views_timelinejs\TimelineJS\SlideInterface $slide
   *   The new slide.
   */
  public function setTitleSlide(SlideInterface $slide);

  /**
   * Returns the timeline's title slide.
   *
   * @return \Drupal\views_timelinejs\TimelineJS\SlideInterface
   *   The title slide.
   */
  public function getTitleSlide();

  /**
   * Sets the timeline's scale to human.
   */
  public function setScaleToHuman();

  /**
   * Sets the timeline's scale to cosmological.
   */
  public function setScaleToCosomological();

  /**
   * Returns the timeline's scale.
   */
  public function getScale();

}
