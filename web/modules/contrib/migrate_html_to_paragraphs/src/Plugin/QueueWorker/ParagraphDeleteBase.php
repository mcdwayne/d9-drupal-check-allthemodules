<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides base functionality for the ParagraphDelete Queue Workers.
 */
abstract class ParagraphDeleteBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The Paragraph storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paragraphStorage;

  /**
   * Creates a new ParagraphDeleteBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $paragraph_storage
   *   The Paragraph storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $paragraph_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->paragraphStorage = $paragraph_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('paragraph')
    );
  }

  /**
   * Deletes a Paragraph entity.
   *
   * @param ParagraphInterface $paragraph
   */
  protected function deleteParagraph($paragraph) {
    $paragraph->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var ParagraphInterface $paragraph */
    $paragraph = $this->paragraphStorage->load($data->pid);
    if ($paragraph instanceof ParagraphInterface) {
      $this->deleteParagraph($paragraph);
    }
  }

}
