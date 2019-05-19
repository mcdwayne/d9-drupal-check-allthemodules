<?php

namespace Drupal\timelinejs;

use Drupal\Core\Render\RenderableInterface;
use Drupal\timelinejs\Entity\Timeline;
use Drupal\timelinejs\Entity\TimelineInterface;

/**
 * Defines an TimelineJS object which can be rendered by the Render API.
 */
class TimelineJS implements RenderableInterface {

  /**
   * Timeline entity.
   *
   * @var \Drupal\timelinejs\Entity\Timeline
   */
  private $timeline;

  /**
   * TimelineJS constructor.
   *
   * @param \Drupal\timelinejs\Entity\TimelineInterface $timeline
   *   The Timeline entity.
   */
  public function __construct(TimelineInterface $timeline) {
    $this->timeline = $timeline;
  }

  /**
   * Returns a render array representation of the object.
   *
   * @return mixed[]
   *   A render array.
   */
  public function toRenderable() {
    $timeline = $this->getTimeline();
    $timelineIdentifier = 'timeline-' . $timeline->id();
    return [
      '#theme' => 'timelinejs',
      '#attributes' => [
        'id' => $timelineIdentifier,
        'class' => [
          'timeline-embed',
        ],
        'data-text' => '',
      ],
      '#cache' => [
        'tags' => $timeline->getCacheTags(),
      ],
      '#google_spreadsheet_url' => $timeline->getGoogleSpreadsheetUrl(),
      '#attached' => [
        'library' => [
          'timelinejs/timelinejs',
          'timelinejs/timelinejs_init',
        ],
        'drupalSettings' => [
          'timelinejs' => [
            $timelineIdentifier => [
              'url' => $timeline->getGoogleSpreadsheetUrl(),
              'scale' => $timeline->getScale(),
              'hash_bookmark' => $timeline->getHashBookmark(),
              'start_at_end' => $timeline->getStartAtEnd(),
              'use_bc' => $timeline->getUseBc(),
              'dragging' => $timeline->getDragging(),
              'track_resize' => $timeline->getTrackResize(),
              'default_bg_color' => $timeline->getDefaultBackgroundColor(),
              'scale_factor' => $timeline->getScaleFactor(),
              'initial_zoom' => $timeline->getInitialZoom(),
              'zoom_sequence' => $timeline->getZoomSequence(),
              'timenav_position' => $timeline->getNavigationPosition(),
              'optimal_tick_width' => $timeline->getOptimalTickWidth(),
              'base_class' => $timeline->getBaseClass(),
              'timenav_height' => $timeline->getNavigationHeight(),
              'timenav_height_percentage' => $timeline->getNavigationHeightPercentage(),
              'timenav_mobile_height_percentage' => $timeline->getNavigationMobileHeightPercentage(),
              'timenav_height_min' => $timeline->getNavigationHeightMin(),
              'marker_height_min' => $timeline->getMarkerHeightMin(),
              'marker_width_min' => $timeline->getMarkerWidthMin(),
              'marker_padding' => $timeline->getMarkerPadding(),
              'start_at_slide' => $timeline->getStartSlide(),
              'menubar_height' => $timeline->getMenubarHeight(),
              'duration' => $timeline->getAnimationDuration(),
              'ease' => $timeline->getEase(),
              'slide_padding_lr' => $timeline->getSlidePaddingLeftRight(),
              'slide_default_fade' => $timeline->getSlideDefaultFade(),
              'ga_property_id' => $timeline->getGoogleAnalyticsPropertyId(),
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Get the Timeline entity.
   *
   * @return \Drupal\timelinejs\Entity\Timeline
   *   The Timeline Entity
   */
  public function getTimeline(): Timeline {
    return $this->timeline;
  }

  /**
   * Sets the Timeline entity.
   *
   * @param \Drupal\timelinejs\Entity\Timeline $timeline
   *   The Timeline Entity.
   */
  public function setTimeline(Timeline $timeline) {
    $this->timeline = $timeline;
  }

}
