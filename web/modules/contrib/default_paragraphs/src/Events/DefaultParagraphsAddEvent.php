<?php

namespace Drupal\default_paragraphs\Events;

use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Represents Paragraph Entity adding as default.
 */
class DefaultParagraphsAddEvent extends Event {

  /**
   * The paragraph entity being added.
   *
   * @var \Drupal\paragraphs\ParagraphInterface
   */
  protected $paragraphEntity;

  /**
   * The bundle of the paragraph entity.
   *
   * @var string
   */
  private $targetBundle;

  /**
   * DefaultParagraphsAddEvent constructor.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph_entity
   *   The paragraph entity being created.
   * @param string $target_bundle
   *   The bundle, which the paragraph is referenced to.
   */
  public function __construct(ParagraphInterface $paragraph_entity, $target_bundle) {
    $this->paragraphEntity = $paragraph_entity;
    $this->targetBundle = $target_bundle;
  }

  /**
   * Get the paragraph entity.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   A paragraph entity.
   */
  public function getParagraphEntity() {
    return $this->paragraphEntity;
  }

  /**
   * Get the paragraph's bundle.
   *
   * @return string
   *   A paragraph entity's bundle.
   */
  public function getTargetBundle() {
    return $this->targetBundle;
  }

  /**
   * Set the paragraph entity.
   *
   * @param \Drupal\paragraphs\paragraphInterface $paragraph_entity
   *   The updated paragraph entity.
   */
  public function setParagraphEntity(paragraphInterface $paragraph_entity) {
    $this->paragraphEntity = $paragraph_entity;
  }

}
