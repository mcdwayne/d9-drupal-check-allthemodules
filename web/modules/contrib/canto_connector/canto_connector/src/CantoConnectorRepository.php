<?php
namespace Drupal\canto_connector;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Repository for database-related helper methods for our example.
 *
 * This repository is a service named 'canto_connector.repository'. You can see
 * how the service is defined in canto_connector/canto_connector.services.yml.
 *
 * For projects where there are many specialized queries, it can be useful to
 * group them into 'repositories' of queries. We can also architect this
 * repository to be a service, so that it gathers the database connections it
 * needs. This way other classes which use the repository don't need to concern
 * themselves with database connections, only with business logic.
 *
 * This repository demonstrates basic CRUD behaviors, and also has an advanced
 * query which performs a join with the user table.
 *
 * @ingroup canto_connector
 */
class CantoConnectorRepository {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct a repository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(Connection $connection, TranslationInterface $translation, MessengerInterface $messenger) {
    $this->connection = $connection;
    $this->setStringTranslation($translation);
    $this->setMessenger($messenger);
  }

  public function insert(array $entry) {
    $return_value = NULL;
    try {
      $return_value = $this->connection->insert('canto_oauth_domain')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage(t('db_insert failed. Message = %message', [
        '%message' => $e->getMessage(),
      ]), 'error');
    }
    return $return_value;
  }


  /**
   * Delete an entry from the database.
   *
   * @param array $entry
   *   An array containing at least the person identifier 'pid' element of the
   *   entry to delete.
   *
   * @see Drupal\Core\Database\Connection::delete()
   */
  public function delete(array $entry) {
      $query = $this->connection->delete('canto_oauth_domain');
    foreach ($entry as $field => $value) {
        $query->condition($field, $value);
    }
    $query ->execute();
  }

 


  public function getAccessToken(array $entry = []) {

      $select = $this->connection->select('canto_oauth_domain')->fields('canto_oauth_domain');
      foreach ($entry as $field => $value) {
          $select->condition($field, $value);
      }

      $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);//Fetch as an associative array.
    return $entries;
   
    
  }

}
