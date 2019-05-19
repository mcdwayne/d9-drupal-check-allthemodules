<?php

namespace Drupal\timelinejs\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Timeline entities.
 *
 * @ingroup timelinejs
 */
interface TimelineInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Timeline name.
   *
   * @return string
   *   Name of the Timeline.
   */
  public function getName();

  /**
   * Sets the Timeline name.
   *
   * @param string $name
   *   The Timeline name.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setName($name);

  /**
   * Gets the Timeline creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Timeline.
   */
  public function getCreatedTime();

  /**
   * Sets the Timeline creation timestamp.
   *
   * @param int $timestamp
   *   The Timeline creation timestamp.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Timeline published status indicator.
   *
   * Unpublished Timeline are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Timeline is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Timeline.
   *
   * @param bool $published
   *   TRUE to set this Timeline to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setPublished($published);

  /**
   * Gets the Timeline revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Timeline revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Timeline revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Timeline revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Sets the Timeline scale.
   *
   * @param string $scale
   *   Either 'human' or 'cosmological'.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setSale($scale);

  /**
   * Gets the Timeline scale.
   *
   * @return string
   *   The scale of the Timeline, either 'human' or 'cosmological'.
   */
  public function getScale();

  /**
   * Sets the HashBookmark option.
   *
   * @param bool $hashBookmark
   *   True if the timeline should use a hash bookmark, false otherwise.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setHashBookmark($hashBookmark);

  /**
   * Gets the HashBookmark option.
   *
   * @return bool
   *   True if the timeline should use a hash bookmark, false otherwise.
   */
  public function getHashBookmark();

  /**
   * Sets the Start at End option.
   *
   * @param bool $startAtEnd
   *   True if the timeline should start at the last event, false otherwise.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setStartAtEnd($startAtEnd);

  /**
   * Gets the Start at End option.
   *
   * @return bool
   *   True if the timeline should start at the last event, false otherwise.
   */
  public function getStartAtEnd();

  /**
   * Sets the Use BC option.
   *
   * @param bool $useBc
   *   True if the timeline should support BC events, false otherwise.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setUseBc($useBc);

  /**
   * Gets the Use BC option.
   *
   * @return bool
   *   True if the timeline should support BC events, false otherwise.
   */
  public function getUseBc();

  /**
   * Sets the Dragging option.
   *
   * @param bool $dragging
   *   True if the timeline should support dragging, false otherwise.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setDragging($dragging);

  /**
   * Gets the Dragging option.
   *
   * @return bool
   *   True if the timeline should support dragging, false otherwise.
   */
  public function getDragging();

  /**
   * Sets the Track Resize option.
   *
   * @param bool $trackResize
   *   True if the timeline should track resize, false otherwise.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setTrackResize($trackResize);

  /**
   * Gets the Track Resize option.
   *
   * @return bool
   *   True if the timeline should track resize, false otherwise.
   */
  public function getTrackResize();

  /**
   * Sets the Default Background Color option.
   *
   * @param string $color
   *   The background color represented as a hex code.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setDefaultBackgroundColor($color);

  /**
   * Gets the Default Background Color option.
   *
   * @return string
   *   The background color represented as a hex code.
   */
  public function getDefaultBackgroundColor();

  /**
   * Sets the Scale Factor option.
   *
   * @param float $scaleFactor
   *   The number of screen widths wide the timeline should be at first
   *   presentation.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setScaleFactor($scaleFactor);

  /**
   * Gets the Scale Factor Color option.
   *
   * @return float
   *   The number of screen widths wide the timeline should be at first
   *   presentation.
   */
  public function getScaleFactor();

  /**
   * Sets the Initial Zoom option.
   *
   * @param float $initialZoom
   *   The position in the zoom_sequence series used to scale the Timeline when
   *   it is first created. Takes precedence over scale_factor.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setInitialZoom($initialZoom);

  /**
   * Gets the Initial Zoom  option.
   *
   * @return float
   *   The position in the zoom_sequence series used to scale the Timeline when
   *   it is first created. Takes precedence over scale_factor.
   */
  public function getInitialZoom();

  /**
   * Sets the Optimal Tick width.
   *
   * @param int $width
   *   Optimal distance (in pixels) between ticks on axis.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setOptimalTickWidth($width);

  /**
   * Gets the Optimal Tick option.
   *
   * @return int
   *   Optimal distance (in pixels) between ticks on axis.
   */
  public function getOptimalTickWidth();

  /**
   * Sets the navigation height.
   *
   * @param int $height
   *   The height in pixels of the timeline nav.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setNavigationHeight($height);

  /**
   * Gets the navigation height.
   *
   * @return int
   *   The height in pixels of the timeline nav.
   */
  public function getNavigationHeight();

  /**
   * Sets the minimum navigation height.
   *
   * @param int $height
   *   The minimum height in pixels of the timeline nav.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setNavigationHeightMin($height);

  /**
   * Gets the minimum navigation height.
   *
   * @return int
   *   The height in pixels of the timeline nav.
   */
  public function getNavigationHeightMin();

  /**
   * Sets the navigation height as a percentage.
   *
   * @param float $height
   *   The height of the timeline nav as a percentage of the screen.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setNavigationHeightPercentage($height);

  /**
   * Gets the navigation height as a percentage.
   *
   * @return float
   *   The height of the timeline nav as a percentage of the screen.
   */
  public function getNavigationHeightPercentage();

  /**
   * Sets the mobile navigation height as a percentage.
   *
   * @param float $height
   *   The height of the timeline nav as a percentage of the mobile s creen.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setNavigationMobileHeightPercentage($height);

  /**
   * Gets the mobile navigation height as a percentage.
   *
   * @return float
   *   The height of the timeline nav as a percentage of the mobile screen.
   */
  public function getNavigationMobileHeightPercentage();

  /**
   * Sets the minimum marker height.
   *
   * @param int $height
   *   The minimum marker height (in pixels).
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setMarkerHeightMin($height);

  /**
   * Gets the minimum marker height.
   *
   * @return int
   *   The minimum marker height (in pixels).
   */
  public function getMarkerHeightMin();

  /**
   * Sets the minimum marker width.
   *
   * @param int $width
   *   The minimum marker width (in pixels).
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setMarkerWidthMin($width);

  /**
   * Gets the minimum marker width.
   *
   * @return int
   *   The minimum marker width (in pixels).
   */
  public function getMarkerWidthMin();

  /**
   * Sets the top and bottom padding (in pixels) for markers.
   *
   * @param int $padding
   *   The top and bottom padding (in pixels) for markers.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setMarkerPadding($padding);

  /**
   * Gets the minimum marker width.
   *
   * @return int
   *   The top and bottom padding (in pixels) for markers.
   */
  public function getMarkerPadding();

  /**
   * Sets the first slide to display when the timeline is loaded.
   *
   * @param int $slideNumber
   *   The first slide to display when the timeline is loaded.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setStartSlide($slideNumber);

  /**
   * Gets first slide to display when the timeline is loaded.
   *
   * @return int
   *   The top and bottom padding (in pixels) for markers.
   */
  public function getStartSlide();

  /**
   * Sets the menubar height.
   *
   * @param int $height
   *   The menubar height.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setMenubarHeight($height);

  /**
   * Gets the menubar height.
   *
   * @return int
   *   The menubar height
   */
  public function getMenubarHeight();

  /**
   * Sets the animation duration.
   *
   * @param int $duration
   *   The animation duration in milliseconds.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setAnimationDuration($duration);

  /**
   * Gets the animation duration.
   *
   * @return int
   *   The animation duration in milliseconds.
   */
  public function getAnimationDuration();

  /**
   * Sets the easing.
   *
   * @param string $ease
   *   The easing.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   *
   * @see Timeline::getEasingOptionsArray()
   */
  public function setEase($ease);

  /**
   * Gets the easing.
   *
   * @return string
   *   The easing.
   */
  public function getEase();

  /**
   * Sets the Google Analytics Property ID.
   *
   * @param string $googleAnalyticsPropertyId
   *   Google Analytics Property ID.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setGoogleAnalyticsPropertyId($googleAnalyticsPropertyId);

  /**
   * Gets the Google Analytics Property ID.
   *
   * @return string
   *   Google Analytics Property ID.
   */
  public function getGoogleAnalyticsPropertyId();

  /**
   * Sets the padding (in pixels) on the left and right of each slide.
   *
   * @param int $padding
   *   The padding (in pixels) on the left and right of each slide.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setSlidePaddingLeftRight($padding);

  /**
   * Gets the padding (in pixels) on the left and right of each slide.
   *
   * @return int
   *   The padding (in pixels) on the left and right of each slide.
   */
  public function getSlidePaddingLeftRight();

  /**
   * Sets the slide_default_fade option.
   *
   * @param string $fade
   *   The slide_default_fade option.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setSlideDefaultFade($fade);

  /**
   * Gets the padding (in pixels) on the left and right of each slide.
   *
   * @return string
   *   The anislide_default_fade option.
   */
  public function getSlideDefaultFade();

  /**
   * Sets the Zoom Sequence option.
   *
   * @param array $zoomSequence
   *   Array of values for TimeNav zoom levels. Each value is a scale_factor,
   *   which means that at any given level, the full timeline would require
   *   that many screens to display all events.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setZoomSequence(array $zoomSequence);

  /**
   * Gets the Initial Zoom  option.
   *
   * @return array
   *   Array of values for TimeNav zoom levels. Each value is a scale_factor,
   *   which means that at any given level, the full timeline would require
   *   that many screens to display all events.
   */
  public function getZoomSequence();

  /**
   * Sets the timeline navigation position.
   *
   * @param string $position
   *   Either 'top' or 'bottom'.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setNavigationPosition($position);

  /**
   * Gets the timeline navigation position.
   *
   * @return string
   *   The position of the navigation, either 'top' or 'bottom'.
   */
  public function getNavigationPosition();

  /**
   * Gets the Google Spreadsheet Url.
   *
   * @return string
   *   The public url of the Google Spreadsheet.
   */
  public function getGoogleSpreadsheetUrl();

  /**
   * Gets the Google Spreadsheet Url.
   *
   * @param string $url
   *   The url of the Google Spreadsheet.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setGoogleSpreadsheetUrl($url);

  /**
   * Sets the base class option.
   *
   * @param string $class
   *   The base class.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setBaseClass($class);

  /**
   * Gets the base class.
   *
   * @return string
   *   The base class.
   */
  public function getBaseClass();

}
