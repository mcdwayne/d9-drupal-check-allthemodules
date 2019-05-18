<?php

namespace Drupal\applenews\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Class ApplenewsNormalizerBase.
 *
 * @package Drupal\applenews\Normalizer
 */
abstract class ApplenewsNormalizerBase implements NormalizerInterface, SerializerAwareInterface {

  use SerializerAwareTrait;

  /**
   * Name of the format that this normalizer deals with.
   *
   * @var string
   */
  protected $format = 'applenews';

}
