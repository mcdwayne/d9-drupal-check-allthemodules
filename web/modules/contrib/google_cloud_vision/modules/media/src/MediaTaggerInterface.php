<?php

namespace Drupal\google_cloud_vision_media;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\media\MediaInterface;

/**
 * Interface MediaTaggerInterface.
 *
 * @package Drupal\google_cloud_vision_media\MediaTaggerInterface
 */
interface MediaTaggerInterface {

  /**
   * Add a tag to the list of tags to save.
   *
   * @param string $tagName
   *   Name of the tag.
   * @param string $vocabularyName
   *   Name of the vocabulary for the taxonomy terms.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The taxonomy term.
   */
  public function getTagTerm($tagName, $vocabularyName);

  /**
   * Find an existing matching tag in the vocabulary.
   *
   * @param string $tagName
   *   Name of the tag.
   * @param string $vocabularyName
   *   Name of the vocabulary for the taxonomy terms.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The matching Taxonomy Term.
   */
  public function matchTag($tagName, $vocabularyName);

  /**
   * Create a new taxonomy term for the tag.
   *
   * @param string $tagName
   *   Name of the tag.
   * @param string $vocabularyName
   *   Name of the vocabulary for the taxonomy terms.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created Taxonomy Term.
   */
  public function createTag($tagName, $vocabularyName);

  /**
   * Get the vocabulary name for the tag field.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $tagField
   *   Entity reference field for taxonomy terms.
   *
   * @return string
   *   The name of the vocabulary.
   */
  public function getVocabularyName(EntityReferenceFieldItemListInterface $tagField);

  /**
   * Get the current tag taxonomy terms.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $tagField
   *   Entity reference field for taxonomy terms.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   List of existing Taxonomy Terms in the field.
   */
  public function getCurrentTags(EntityReferenceFieldItemListInterface $tagField);

  /**
   * Save the tags in the taxonomy term reference field.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media item.
   * @param string $tagFieldName
   *   Name of the field to safe the taxonomy terms in.
   * @param \Drupal\taxonomy\TermInterface[] $tagTerms
   *   Taxonomy terms to save in the field.
   *
   * @return \Drupal\google_cloud_vision_media\MediaTaggerInterface
   *   The Media Tagger.
   */
  public function saveTags(MediaInterface $media, $tagFieldName, array $tagTerms);

}
