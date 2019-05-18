<?php

namespace Drupal\rest_media_recursive\Normalizer;

use Drupal\consumer_image_styles\ImageStylesProvider;
use Drupal\consumers\Negotiator;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class ImageFileNormalizer.
 *
 * Normalizer adds image styles for image.
 *
 * @package Drupal\rest_media_recursive\Normalizer
 */
class ImageFileNormalizer extends FileNormalizer {

  /**
   * Consumer negotiator.
   *
   * @var \Drupal\consumers\Negotiator
   */
  protected $consumerNegotiator;

  /**
   * Image style provider.
   *
   * @var \Drupal\consumer_image_styles\ImageStylesProvider
   */
  protected $imageStylesProvider;

  /**
   * Constructs an ImageItemNormalizer object.
   *
   * @param \Drupal\consumers\Negotiator $consumer_negotiator
   *   The consumer negotiator.
   * @param \Drupal\consumer_image_styles\ImageStylesProvider $imageStylesProvider
   *   Image styles utility.
   */
  public function __construct(Negotiator $consumer_negotiator, ImageStylesProvider $imageStylesProvider) {
    $this->consumerNegotiator = $consumer_negotiator;
    $this->imageStylesProvider = $imageStylesProvider;
  }

  /**
   * @see \Drupal\consumer_image_styles\Normalizer\ImageEntityNormalizer::supportsNormalization()
   */
  public function supportsNormalization($data, $format = NULL) {
    return parent::supportsNormalization($data, $format) &&
      strpos($data->get('filemime')->value, 'image/') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $normalized_values = parent::normalize($entity, $format, $context);
    $normalized_values['image_styles'] = $this->buildVariantValues($entity);

    return $normalized_values;
  }

  /**
   * Creates array of image styles for image.
   * @see \Drupal\consumer_image_styles\Normalizer\ImageEntityNormalizer::buildVariantValues()
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return array|false
   */
  protected function buildVariantValues(EntityInterface $entity) {
    $consumer = $this->consumerNegotiator->negotiateFromRequest();

    // If consumer not found return empty array.
    if (!$consumer) {
      return [];
    }

    // Prepare some utils.
    $uri = $entity->getFileUri();
    $get_image_url = function ($image_style) use ($uri) {
      return file_url_transform_relative($image_style->buildUrl($uri));
    };

    // Generate derivatives only for the found ones.
    $image_styles = $this->imageStylesProvider->loadStyles($consumer);
    $keys = array_keys($image_styles);
    $values = array_map($get_image_url, array_values($image_styles));
    $result = array_combine($keys, $values);

    // Add original url to array.
    $result['original'] = file_url_transform_relative(file_create_url($uri));

    return $result;
  }

}