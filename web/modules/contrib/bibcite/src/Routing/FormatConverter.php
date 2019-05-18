<?php

namespace Drupal\bibcite\Routing;

use Drupal\bibcite\Plugin\BibciteFormatManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts export plugin id to definition array.
 */
class FormatConverter implements ParamConverterInterface {

  /**
   * Format plugins manager service.
   *
   * @var \Drupal\bibcite\Plugin\BibciteFormatManagerInterface
   */
  protected $formatManager;

  /**
   * Format converter constructor.
   *
   * @param \Drupal\bibcite\Plugin\BibciteFormatManagerInterface $format_manager
   *   Format plugins manager service.
   */
  public function __construct(BibciteFormatManagerInterface $format_manager) {
    $this->formatManager = $format_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (!empty($value) && $this->formatManager->hasDefinition($value)) {
      return $this->formatManager->createInstance($value);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'bibcite_format');
  }

}
