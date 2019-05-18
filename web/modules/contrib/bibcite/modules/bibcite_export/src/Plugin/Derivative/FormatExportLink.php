<?php

namespace Drupal\bibcite_export\Plugin\Derivative;

use Drupal\bibcite\Plugin\BibciteFormatManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic tabs based on available formats.
 */
class FormatExportLink extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Bibcite format manager service.
   *
   * @var \Drupal\bibcite\Plugin\BibciteFormatManagerInterface
   */
  protected $formatManager;

  /**
   * Construct a new FormatLocalTask.
   *
   * @param \Drupal\bibcite\Plugin\BibciteFormatManagerInterface $format_manager
   *   Bibcite format manager service.
   */
  public function __construct(BibciteFormatManagerInterface $format_manager) {
    $this->formatManager = $format_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('plugin.manager.bibcite_format'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->formatManager->getDefinitions() as $format_id => $format_definition) {
      $this->derivatives[$format_id] = $base_plugin_definition;
      $this->derivatives[$format_id]['label'] = $format_definition['label'];
      $this->derivatives[$format_id]['export_format'] = $format_id;
    }

    return $this->derivatives;
  }

}
