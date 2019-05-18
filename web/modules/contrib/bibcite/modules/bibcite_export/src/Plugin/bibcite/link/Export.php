<?php

namespace Drupal\bibcite_export\Plugin\bibcite\link;

use Drupal\bibcite\Plugin\BibciteFormatManagerInterface;
use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\bibcite_entity\Plugin\BibciteLinkPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Build link to export reference entity.
 *
 * @BibciteLink(
 *   id = "export",
 *   label = @Translation("Export"),
 *   deriver = "Drupal\bibcite_export\Plugin\Derivative\FormatExportLink",
 * )
 */
class Export extends BibciteLinkPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Format manager.
   *
   * @var \Drupal\bibcite\Plugin\BibciteFormatManagerInterface
   */
  protected $formatManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BibciteFormatManagerInterface $format_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formatManager = $format_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.bibcite_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildUrl(ReferenceInterface $reference) {
    $format_id = $this->pluginDefinition['export_format'];
    $definition = $this->formatManager->getDefinition($format_id);

    return Url::fromRoute('bibcite_export.export', [
      'bibcite_format' => $definition['id'],
      'entity_type' => $reference->getEntityTypeId(),
      'entity' => $reference->id(),
    ]);
  }

}
