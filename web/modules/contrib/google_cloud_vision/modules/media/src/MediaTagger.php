<?php

namespace Drupal\google_cloud_vision_media;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\media\MediaInterface;

/**
 * Class MediaTagger.
 *
 * @package Drupal\google_cloud_vision_media
 */
class MediaTagger implements MediaTaggerInterface {

  /**
   * Taxonomy Term Storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  private $taxonomyTermStorage;

  /**
   * Media Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $mediaStorage;

  /**
   * MediaTagger constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->taxonomyTermStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->mediaStorage = $entityTypeManager->getStorage('media');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getTagTerm($tagName, $vocabularyName) {
    $matchingTaxonomyTerm = $this->matchTag($tagName, $vocabularyName);

    if (empty($matchingTaxonomyTerm)) {
      $matchingTaxonomyTerm = $this->createTag($tagName, $vocabularyName);
    }

    return $matchingTaxonomyTerm;
  }

  /**
   * {@inheritdoc}
   */
  public function matchTag($tagName, $vocabularyName) {
    $matchingTaxonomyTerms = $this->taxonomyTermStorage->loadByProperties([
      'name' => $tagName,
      'vid' => $vocabularyName,
    ]);
    return reset($matchingTaxonomyTerms);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createTag($tagName, $vocabularyName) {
    $matchingTaxonomyTerm = $this->taxonomyTermStorage->create([
      'name' => $tagName,
      'vid' => $vocabularyName,
    ]);
    $this->taxonomyTermStorage->save($matchingTaxonomyTerm);

    return $matchingTaxonomyTerm;
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabularyName(EntityReferenceFieldItemListInterface $tagField) {
    return reset($tagField->getSettings()['handler_settings']['target_bundles']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTags(EntityReferenceFieldItemListInterface $tagField) {
    $labelTaxonomyTermIds = array_column($tagField->getValue(), 'target_id');
    /** @var \Drupal\taxonomy\TermInterface[] $currentTaxonomyTerms */
    return $this->taxonomyTermStorage->loadMultiple($labelTaxonomyTermIds);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveTags(MediaInterface $media, $tagFieldName, array $tagTerms) {
    $media->set($tagFieldName, $tagTerms);
    $this->mediaStorage->save($media);
  }

}
