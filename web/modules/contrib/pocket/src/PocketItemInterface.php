<?php

namespace Drupal\pocket;

interface PocketItemInterface {

  /**
   * Unique ID.
   *
   * @return string
   */
  public function id(): string;

  /**
   * Item URL.
   *
   * @return string
   */
  public function getUrl(): string;

  /**
   * Unique domain ID.
   *
   * @return string
   */
  public function getDomainId(): string;

  /**
   * Resolved ID.
   *
   * @return string
   */
  public function getResolvedId(): string;

  /**
   * Resolved item URL (after redirects).
   *
   * @return string
   */
  public function getResolvedUrl(): string;

  /**
   * Resolved domain ID.
   *
   * @return string
   */
  public function getResolvedDomainId(): string;

  /**
   * HTTP response code.
   *
   * @return int
   */
  public function getResponseCode(): int;

  /**
   * Mimetype of content.
   *
   * @return string
   */
  public function getMimeType(): string;

  /**
   * Byte length of content.
   *
   * @return int
   */
  public function getContentLength(): int;

  /**
   * Encoding of content.
   *
   * @return string
   */
  public function getEncoding(): string;

  /**
   * Date the item was added.
   *
   * @return \DateTime
   */
  public function getAddedDate(): \DateTime;

  /**
   * Published date of the item (if discovered).
   *
   * @return \DateTime
   */
  public function getPublishedDate(): \DateTime;

  /**
   * Content title.
   *
   * @return string
   */
  public function getTitle(): string;

  /**
   * Content excerpt.
   *
   * @return string
   */
  public function getExcerpt(): string;

  /**
   * Content word count.
   *
   * @return int
   */
  public function getWordCount(): int;

  /**
   * Check if the item has at least one image.
   *
   * @return bool
   */
  public function hasImage(): bool;

  /**
   * Check if the item has at least one video.
   *
   * @return bool
   */
  public function hasVideo(): bool;

  /**
   * Check if the item is an article.
   *
   * @return bool
   */
  public function isArticle(): bool;

  /**
   * Check if the item is an index page.
   *
   * @return bool
   */
  public function isIndex(): bool;

  /**
   * Get all authors of the item.
   *
   * @return array
   */
  public function getAuthors(): array;

  /**
   * Get all images from the item.
   *
   * @return array
   */
  public function getImages(): array;

  /**
   * Get all videos from the item.
   *
   * @return array
   */
  public function getVideos(): array;

}
