<?php

namespace Drupal\digitalmeasures_migrate\Plugin\migrate\destination;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a destination to store retrieved profile fragments to a DB table.
 *
 * User profiles in Digital Measures can be quite large. Processing them
 * all at once can lead to HTTP timeouts and memory limit problems. This plugin
 * stages those profiles to a database table so they can be later imported
 * locally.
 *
 * @MigrateDestination(
 *   id = "digitalmeasures_api_profile_staging"
 * )
 *
 */
class ProfileStaging extends DestinationBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The Drupal database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * The time service.
   *
   * @var TimeInterface;
   */
  protected $datetime;

  /**
   * DigitalMeasuresApiProfile constructor.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              Connection $database,
                              TimeInterface $datetime,
                              MigrationInterface $migration) {
    $this->database = $database;
    $this->datetime = $datetime;

    $this->supportsRollback = TRUE;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition,
                                MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('datetime.time'),
      $migration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    // Since this migration is one user to many database tables, the dest ID
    // is...strange. Migrations assume a one-to-one ID mapping between the
    // source and destination plugins. So, we have to go with the only
    // unique key we've got -- the user ID.
    return [
      'userId' => [
        'type' => 'integer',
        'unsigned' => FALSE,
        'size' => 'big',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'id' => $this->t('The profile fragment ID'),
      'userId' => $this->t('The Digital Measures user ID'),
      'category' => $this->t('The profile fragment type'),
      'xml' => $this->t('The XML of the profile fragment'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // Get the user ID and profile XML from the pipeline.
    $userId = $row->getDestinationProperty('userId');
    $profileXml = $row->getDestinationProperty('profile_xml');

    // If the profile XML is empty, skip further processing.
    if (empty($profileXml)) {
      return [$userId];
    }

    // Parse the profile XML.
    $xml = new \SimpleXMLElement($profileXml);

    // Break up the profile into fragment types ("categories").
    foreach ($this->configuration['categories'] as $category) {
      $name = $category['name'];
      $item_selector = $category['item_selector'];
      $id_selector = $category['id_selector'];

      // Get the fragments from the profile for the given type.
      $items = $xml->xpath($item_selector);

      // For each fragment...
      foreach ($items as $item) {
        // ...get the ID using the given selector.
        $id_xml = $item->xpath($id_selector);

        // If we found the ID...
        if ($id_xml) {

          // ... extract it from the document.
          $id = reset($id_xml)->__toString();

          // Get the rest of the fragment document.
          $body = $item->asXML();

          // Save it to the database.
          $this->mergeRecord($userId, $id, $name, $body);
        }
      }
    }

    return [$userId];
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    // Get the dest ID names.
    $column_names = array_keys($this->getIds());

    // Delete profile fragments from our table...
    $query = $this->database->delete('digitalmeasures_migrate_profile');

    // ..which matching IDs.
    foreach ($column_names as $column_name) {
      $query->condition($column_name, $destination_identifier[$column_name]);
    }

    $query->execute();
  }

  /**
   * Saves a profile fragment to the database.
   *
   * @param int $userId
   *   The user ID.
   * @param int $id
   *   The profile fragment ID.
   * @param string $type
   *   The fragment type.
   * @param $body
   *   The fragment XML document.
   *
   * @throws \Exception
   */
  protected function mergeRecord($userId, $id, $type, $body) {
    $this->database->merge('digitalmeasures_migrate_profile')
      ->keys([
        'id' => $id,
        'userId' => $userId,
      ])
      ->fields([
        'category' => $type,
        'xml' => $body,
        'created' => $this->datetime->getRequestTime(),
      ])
      ->execute();
  }

}
