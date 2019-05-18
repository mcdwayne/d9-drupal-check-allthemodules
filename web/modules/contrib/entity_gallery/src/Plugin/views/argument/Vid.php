<?php

namespace Drupal\entity_gallery\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_gallery\EntityGalleryStorageInterface;

/**
 * Argument handler to accept an entity gallery revision id.
 *
 * @ViewsArgument("entity_gallery_vid")
 */
class Vid extends NumericArgument {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity gallery storage.
   *
   * @var \Drupal\entity_gallery\EntityGalleryStorageInterface
   */
  protected $entityGalleryStorage;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   * @param \Drupal\entity_gallery\EntityGalleryStorageInterface
   *   The entity gallery storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityGalleryStorageInterface $entity_gallery_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
    $this->entityGalleryStorage = $entity_gallery_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity.manager')->getStorage('entity_gallery')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the revision.
   */
  public function titleQuery() {
    $titles = array();

    $results = $this->database->query('SELECT egr.vid, egr.egid, egpr.title FROM {entity_gallery_revision} egr WHERE egr.vid IN ( :vids[] )', array(':vids[]' => $this->value))->fetchAllAssoc('vid', PDO::FETCH_ASSOC);
    $egids = array();
    foreach ($results as $result) {
      $egids[] = $result['egid'];
    }

    $entity_galleries = $this->entityGalleryStorage->loadMultiple(array_unique($egids));

    foreach ($results as $result) {
      $entity_galleries[$result['egid']]->set('title', $result['title']);
      $titles[] = $entity_galleries[$result['egid']]->label();
    }

    return $titles;
  }

}
