<?php

/**
 * @file
 * Contains \Drupal\svg_embed\Plugin\Filter\SvgEmbed.
 */

namespace Drupal\svg_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\svg_embed\SvgEmbedProcessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to embed and translate SVG images.
 *
 * @Filter(
 *   id = "svg_embed",
 *   title = @Translation("Embed and translate SVG images"),
 *   description = @Translation("Allows to embed SVG graphics into text like with images and even translates text strings in the SVG file to the language of the node."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 */
class SvgEmbed extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * An entity manager object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a \Drupal\svg_embed\Plugin\Filter\SvgEmbed object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   An entity manager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    //TODO: Do we have at least one SVG reference in the $text?
    if (stristr($text, 'data-entity-type="file"') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      $processed_uuids = array();
      /** @var SvgEmbedProcessInterface $service */
      $service = \Drupal::service('svg_embed.process');

      //TODO: Identify the SVG nodes.
      foreach ($xpath->query('//*[@data-entity-type="file" and @data-entity-uuid]') as $node) {
        $uuid = $node->getAttribute('data-entity-uuid');
        // Only process the first occurrence of each file UUID.
        if (!isset($processed_uuids[$uuid])) {
          $processed_uuids[$uuid] = $service->translate($uuid, $langcode);
          //TODO: Stick the content into the $processed_uuids[$uuid].
        }
      }
    }

    return $result;
  }

}
