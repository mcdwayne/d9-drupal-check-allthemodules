<?php

namespace Drupal\entity_normalization_normalizers\Normalizer;

use DateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a date field into epoch seconds.
 */
class DateTimeToEpochNormalizer implements NormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = []) {
    /** @var \Drupal\datetime\Plugin\Field\FieldType\DateTimeItem $data */
    $date = new DateTime($data->getValue()['value']);
    return (int) $date->format('U');
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $data instanceof DateTimeItem;
  }

}
