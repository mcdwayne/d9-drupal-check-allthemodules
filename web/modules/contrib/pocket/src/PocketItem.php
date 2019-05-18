<?php

namespace Drupal\pocket;

class PocketItem implements PocketItemInterface {

  /**
   * @var array
   */
  protected $values;

  public function __construct(array $values) {
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return $this->values['item_id'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(): string {
    return $this->values['normal_url'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDomainId(): string {
    return $this->values['origin_domain_id'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getResolvedId(): string {
    return $this->values['resolved_id'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getResolvedUrl(): string {
    return $this->values['resolved_url'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getResolvedDomainId(): string {
    return $this->values['domain_id'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseCode(): int {
    return $this->values['response_code'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType(): string {
    return $this->values['mime_type'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getContentLength(): int {
    return $this->values['content_length'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncoding(): string {
    return $this->values['encoding'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddedDate(): \DateTime {
    return self::date($this->values['date_resolved'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function getPublishedDate(): \DateTime {
    return self::date($this->values['date_published'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return $this->values['title'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getExcerpt(): string {
    return $this->values['excerpt'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getWordCount(): int {
    return $this->values['word_count'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function hasImage(): bool {
    return !empty($this->values['has_image']);
  }

  /**
   * {@inheritdoc}
   */
  public function hasVideo(): bool {
    return !empty($this->values['has_video']);
  }

  /**
   * {@inheritdoc}
   */
  public function isArticle(): bool {
    return !empty($this->values['is_article']);
  }

  /**
   * {@inheritdoc}
   */
  public function isIndex(): bool {
    return !empty($this->values['is_index']);
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthors(): array {
    return $this->values['authors'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getImages(): array {
    return $this->values['images'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getVideos(): array {
    return $this->values['videos'] ?? [];
  }

  /**
   * Convert formatted date to DateTime.
   *
   * @param $date
   *
   * @return \DateTime
   */
  private static function date($date): \DateTime {
    return $date ? \DateTime::createFromFormat('Y-m-d H:i:s', $date, new \DateTimeZone('UTC')) : new \DateTime();
  }

}
