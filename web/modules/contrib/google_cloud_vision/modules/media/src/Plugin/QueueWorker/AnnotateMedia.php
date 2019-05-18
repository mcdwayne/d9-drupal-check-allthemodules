<?php

namespace Drupal\google_cloud_vision_media\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\google_cloud_vision_media\Exceptions\LabelAlreadyExistsException;
use Drupal\google_cloud_vision_media\MediaManagerInterface;
use Drupal\google_cloud_vision_media\MediaTaggerInterface;
use Google\Cloud\Vision\Annotation\Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes media entities to annotate at google cloud vision.
 *
 * @QueueWorker(
 *   id = "google_cloud_vision_media",
 *   title = @Translation("Google Cloud Vision Media"),
 *   cron = {"time" = 10}
 * )
 */
class AnnotateMedia extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * CloudConvert Processor.
   *
   * @var \Drupal\google_cloud_vision_media\MediaManagerInterface
   */
  private $mediaManager;

  /**
   * Media Tagger.
   *
   * @var \Drupal\google_cloud_vision_media\MediaTaggerInterface
   */
  private $mediaTagger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, MediaManagerInterface $mediaManager, MediaTaggerInterface $mediaTagger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->mediaManager = $mediaManager;
    $this->mediaTagger = $mediaTagger;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('google_cloud_vision.media_manager'),
      $container->get('google_cloud_vision.media_tagger')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function processItem($mediaId) {
    $mediaStorage = $this->entityTypeManager->getStorage('media');
    $mediaTypeStorage = $this->entityTypeManager->getStorage('media_type');

    /** @var \Drupal\media\MediaInterface $media */
    $media = $mediaStorage->load($mediaId);
    $mediaTypeId = $media->bundle();
    /** @var \Drupal\media\MediaTypeInterface $mediaType */
    $mediaType = $mediaTypeStorage->load($mediaTypeId);
    $enabled = $mediaType->getThirdPartySetting('google_cloud_vision_media', 'enabled', 0);
    $labelFieldName = $mediaType->getThirdPartySetting('google_cloud_vision_media', 'label_tag_field');

    if (!$enabled || empty($labelFieldName)) {
      return;
    }

    if (!$media->hasField($labelFieldName)) {
      return;
    }

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $labelField */
    $labelField = $media->get($labelFieldName);

    try {
      $annotations = $this->mediaManager->annotate($media);
    } catch (\Exception $exception) {
      watchdog_exception('google_cloud_vision', $exception, 'Something went wrong when trying to annotate the image(s).');
      return;
    }

    if (empty($annotations)) {
      return;
    }

    /** @var \Drupal\taxonomy\TermInterface[] $currentTaxonomyTerms */
    $currentTaxonomyTerms = $this->mediaTagger->getCurrentTags($labelField);

    $existingLabels = [];
    foreach ($currentTaxonomyTerms as $currentTaxonomyTerm) {
      $existingLabels[] = $currentTaxonomyTerm->label();
    }

    $vocabularyName = $this->mediaTagger->getVocabularyName($labelField);
    $newTaxonomyTerms = $this->processAnnotations($annotations, $vocabularyName, $existingLabels);

    $this->mediaTagger->saveTags($media, $labelFieldName, array_replace($currentTaxonomyTerms, $newTaxonomyTerms));
  }

  /**
   * Process all the Google Cloud Vision Annotations.
   *
   * @param \Google\Cloud\Vision\Annotation[] $annotations
   *   Google Cloud Vision Annotations.
   * @param string $vocabularyName
   *   Name of the vocabulary for the terms.
   * @param array $existingLabels
   *   Existing label names attached to the media entity.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   New terms to be added to the media entity.
   */
  private function processAnnotations(array $annotations, $vocabularyName, array $existingLabels) {
    $newTaxonomyTerms = [];
    foreach ($annotations as $annotation) {
      $labels = $annotation->labels();
      if ($labels === NULL) {
        continue;
      }
      $newTaxonomyTerms = array_replace($newTaxonomyTerms, $this->processLabels($labels, $vocabularyName, $existingLabels));
    }
    return $newTaxonomyTerms;
  }

  /**
   * Process all the labels from an Google Cloud Vision Annotation.
   *
   * @param \Google\Cloud\Vision\Annotation\Entity[] $labels
   *   Labels found in the Annotation.
   * @param string $vocabularyName
   *   Name of the vocabulary for the terms.
   * @param array $existingLabels
   *   Existing label names attached to the media entity.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   New terms to be added to the media entity.
   */
  private function processLabels(array $labels, $vocabularyName, array $existingLabels) {
    $newTaxonomyTerms = [];
    foreach ($labels as $label) {
      try {
        $taxonomyTerm = $this->processLabel($label, $vocabularyName, $existingLabels);
      } catch (LabelAlreadyExistsException $e) {
        continue;
      }

      $newTaxonomyTerms[$taxonomyTerm->id()] = $taxonomyTerm;
    }
    return $newTaxonomyTerms;
  }

  /**
   * Process an Annotation Label from google cloud vision.
   *
   * @param \Google\Cloud\Vision\Annotation\Entity $label
   *   Label found in an Annotation.
   * @param string $vocabularyName
   *   Name of the vocabulary for the terms.
   * @param array $existingLabels
   *   Existing label names attached to the media entity.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   Found taxonomy term.
   *
   * @throws \Drupal\google_cloud_vision_media\Exceptions\LabelAlreadyExistsException
   */
  private function processLabel(Entity $label, $vocabularyName, array $existingLabels) {
    $tagName = $label->description();
    if (\in_array($tagName, $existingLabels, TRUE)) {
      throw new LabelAlreadyExistsException('Label already exists.');
    }

    return $this->mediaTagger->getTagTerm($tagName, $vocabularyName);
  }

}
