<?php

namespace Drupal\box\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\box\BoxStorageInterface;

/**
 * Argument handler to accept a box revision id.
 *
 * @ViewsArgument("box_vid")
 */
class Vid extends NumericArgument {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The box storage.
   *
   * @var \Drupal\box\BoxStorageInterface
   */
  protected $boxStorage;

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
   * @param \Drupal\box\BoxStorageInterface $box_storage
   *   The box storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, BoxStorageInterface $box_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
    $this->boxStorage = $box_storage;
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
      $container->get('entity.manager')->getStorage('box')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the revision.
   */
  public function titleQuery() {
    $titles = [];

    // @todo: br.title column does not exists.
    // Fix it like: https://www.drupal.org/project/drupal/issues/2628130
    $results = $this->database->query('SELECT br.vid, br.id, br.title FROM {box_revision} br WHERE br.vid IN ( :vids[] )', [':vids[]' => $this->value])->fetchAllAssoc('vid', \PDO::FETCH_ASSOC);
    $ids = [];
    foreach ($results as $result) {
      $ids[] = $result['id'];
    }

    $boxes = $this->boxStorage->loadMultiple(array_unique($ids));

    foreach ($results as $result) {
      $boxes[$result['id']]->set('title', $result['title']);
      $titles[] = $boxes[$result['id']]->label();
    }

    return $titles;
  }

}
