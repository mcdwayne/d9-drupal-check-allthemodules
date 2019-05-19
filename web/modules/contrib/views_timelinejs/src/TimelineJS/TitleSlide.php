<?php

namespace Drupal\views_timelinejs\TimelineJS;

/**
 * Defines a TimelineJS3 title slide.
 */
class TitleSlide extends Slide {

  /**
   * Constructs a new TitleSlide object.
   *
   * @param \Drupal\views_timelinejs\TimelineJS\TextInterface|null $text
   *   Text to display on the title slide.
   */
  public function __construct(TextInterface $text = NULL) {
    if (!empty($text)) {
      $this->text = $text;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildArray() {
    $slide = [];
    if (!empty($this->text)) {
      $slide['text'] = $this->text->buildArray();
    }
    if (!empty($this->media)) {
      $slide['media'] = $this->media->buildArray();
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
