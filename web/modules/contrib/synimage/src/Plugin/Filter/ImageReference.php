<?php

namespace Drupal\synimage\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\synimage\Controller\ImageRenderer;

/**
 * Provides a filter to track images uploaded via a Text Editor.
 *
 * Generates file URLs and associates the cache tags of referenced files.
 *
 * @Filter(
 *   id = "image_reference",
 *   title = @Translation("Image References"),
 *   description = @Translation("Fix. Отслеживать изображения, загруженные через Текстовый редактор"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class ImageReference extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * An entity manager object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a \Drupal\editor\Plugin\Filter\EditorFileReference object.
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

    if (stristr($text, 'data-synimage="') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      $processed_uuids = array();
      foreach ($xpath->query('//*[@data-entity-type="file" and @data-entity-uuid]') as $node) {
        $uuid = $node->getAttribute('data-entity-uuid');
        $synimage_string = $node->getAttribute('data-synimage');
        $synimage = ImageRenderer::decodeSynimage($synimage_string);
        $style = $synimage['style'];
        // If there is a 'src' attribute, set it to the file entity's current
        // URL. This ensures the URL works even after the file location changes.
        if ($node->hasAttribute('src')) {
          $file = $this->entityManager->loadEntityByUuid('file', $uuid);
          $src = ImageRenderer::styledPath($file->id(), $style);
          if ($file) {
            $node->setAttribute('src', $src);
          }
        }

        // Only process the first occurrence of each file UUID.
        if (!isset($processed_uuids[$uuid])) {
          $processed_uuids[$uuid] = TRUE;

          $file = $this->entityManager->loadEntityByUuid('file', $uuid);
          if ($file) {
            $result->addCacheTags($file->getCacheTags());
          }
        }
      }
      foreach ($xpath->query('//*[@data-uuid]') as $node) {
        $uuid = $node->getAttribute('data-uuid');
        $style = $node->getAttribute('data-colorbox');
        if ($node->hasAttribute('href')) {
          $file = $this->entityManager->loadEntityByUuid('file', $uuid);
          $src = ImageRenderer::styledPath($file->id(), $style);
          if ($file) {
            $node->setAttribute('href', $src);
          }
        }

        // Only process the first occurrence of each file UUID.
        if (!isset($processed_uuids[$uuid])) {
          $processed_uuids[$uuid] = TRUE;

          $file = $this->entityManager->loadEntityByUuid('file', $uuid);
          if ($file) {
            $result->addCacheTags($file->getCacheTags());
          }
        }
      }
      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}
