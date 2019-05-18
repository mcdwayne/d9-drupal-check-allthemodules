<?php

namespace Drupal\rest_absolute_urls\Normalizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\serialization\Normalizer\PrimitiveDataNormalizer;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\Request;

class StringDataNormalizer extends PrimitiveDataNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = StringInterface::class;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new StringDataNormalizer.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $value = parent::normalize($object, $format, $context);

    // By default grab the base URL from the configuration settings.
    $base_url = $this->configFactory->get('rest_absolute_urls')->get('base_url');

    // As a fallback, let Drupal to figure out the base URL himself.
    if (empty($base_url)) {
      $base_url = Request::createFromGlobals()->getSchemeAndHttpHost();
    }

    // Convert the whole message body. Returns string.
    return Html::transformRootRelativeUrlsToAbsolute($value, $base_url);
  }

}
