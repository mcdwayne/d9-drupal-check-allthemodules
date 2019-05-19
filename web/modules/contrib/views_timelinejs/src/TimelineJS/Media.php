<?php

namespace Drupal\views_timelinejs\TimelineJS;

/**
 * Defines a TimelineJS3 media object.
 */
class Media implements MediaInterface {

  /**
   * The media url, blockquote, or iframe.
   *
   * @var string
   */
  protected $url;

  /**
   * The media caption.
   *
   * @var string
   */
  protected $caption;

  /**
   * The media credit.
   *
   * @var string
   */
  protected $credit;

  /**
   * The media thumbnail image url.
   *
   * @var string
   */
  protected $thumbnail;

  /**
   * Constructs a new Media object.
   *
   * @param string $url
   *   The URL of the media resource.
   */
  public function __construct($url) {
    $this->url = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function setCaption($text) {
    $this->caption = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function setCredit($text) {
    $this->credit = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function setThumbnail($url) {
    $this->thumbnail = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function buildArray() {
    $media = ['url' => $this->url];
    if (!empty($this->caption)) {
      $media['caption'] = $this->caption;
    }
    if (!empty($this->credit)) {
      $media['credit'] = $this->credit;
    }
    if (!empty($this->thumbnail)) {
      $media['thumbnail'] = $this->thumbnail;
    }
    return $media;
  }

}
