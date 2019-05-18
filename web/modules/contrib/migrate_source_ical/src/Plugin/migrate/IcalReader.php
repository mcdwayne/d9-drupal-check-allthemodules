<?php

/**
 * @file
 * Contains Drupal\migrate_source_json\Plugin\migrate\JSONReader.
 *
 * This reader can traverse multidimensional arrays and retrieve results
 * by locating subarrays that contain a known identifier field at a known depth.
 * It can locate id fields that are nested in the results and pull out all other
 * content that is at the same level. If that content contains additional nested
 * arrays or needs other manipulation, extend this class and massage the data further
 * in the getSourceFields() method.
 *
 * For example, a file that adheres to the JSON API might look like this:
 *
 Array
(
    [DTSTART] => DateTime Object
        (
            [date] => 2015-10-13 10:30:00.000000
            [timezone_type] => 3
            [timezone] => Asia/Calcutta
        )

    [DTEND] => DateTime Object
        (
            [date] => 2015-10-13 11:00:00.000000
            [timezone_type] => 3
            [timezone] => Asia/Calcutta
        )

    [RRULE] => Array
        (
            [FREQ] => WEEKLY
            [UNTIL] => DateTime Object
                (
                    [date] => 2015-11-13 05:00:00.000000
                    [timezone_type] => 2
                    [timezone] => Z
                )

            [BYDAY] => MO,TU,WE,TH,FR
        )

    [DTSTAMP] => DateTime Object
        (
            [date] => 2017-09-22 12:02:14.000000
            [timezone_type] => 2
            [timezone] => Z
        )

    [ORGANIZER] => mailto:nvhsa43nhis4uqjiec7u9ceqa0@group.calendar.google.com
    [UID] => 59m3jpl5vasf9q7ao22m3frc9o@google.com
    [ATTENDEE] => mailto:gdgautamd5@gmail.com
    [CREATED] => DateTime Object
        (
            [date] => 2015-10-08 02:26:35.000000
            [timezone_type] => 2
            [timezone] => Z
        )

    [DESCRIPTION] =>
    [LAST-MODIFIED] => DateTime Object
        (
            [date] => 2015-11-20 07:51:34.000000
            [timezone_type] => 2
            [timezone] => Z
        )

    [LOCATION] =>
    [SEQUENCE] => 1
    [STATUS] => CONFIRMED
    [SUMMARY] => Scrum Meeting
    [TRANSP] => OPAQUE
    [0] =>
    [RECURRING] => 1
)
 *
 * In the above example, the id field and the value1 field would be transformed
 * to top-level key/value pairs, as required by Migrate. The value2 field,
 * if needed, might require further manipulation by extending this class.
 *
 * @see http://php.net/manual/en/class.recursiveiteratoriterator.php
 */

namespace Drupal\migrate_source_ical\Plugin\migrate;

use Drupal\migrate\MigrateException;
use GuzzleHttp\Exception\RequestException;
use ICal\ICal;

/**
 * Object to retrieve and iterate over JSON data.
 */
class ICALReader {

  /**
   * The client class to create the HttpClient.
   *
   * @var string
   */
  protected $clientClass = '';

  /**
   * The HTTP Client
   *
   * @var JSONClientInterface resource
   */
  protected $client;

  /**
   * The request headers.
   *
   * @var array
   */
  protected $headers = [];

  /**
   * Source configuration
   *
   * @var array
   */
  protected $configuration;

  /**
   * The field name that is a unique identifier.
   *
   * @var string
   */
  protected $identifier = '';

  /**
   * The depth of the identifier in the source data.
   *
   * @var string
   */
  protected $identifierDepth = '';

  /**
   * Set the configuration created by the JSON source.
   *
   * @param array $configuration
   *   The source configuration.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function __construct(array $configuration) {

    // Pull out the values this reader will care about.
    if (!isset($configuration['identifier'])) {
      throw new MigrateException('The source configuration must include the identifier.');
    }
    if (!isset($configuration['identifierDepth'])) {
      throw new MigrateException('The source configuration must include the identifierDepth.');
    }
    $this->identifier = $configuration['identifier'];
    $this->identifierDepth = $configuration['identifierDepth'];

    // Store the rest of the configuration.
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * Return the identifier for this JSON source.
   */
  public function getIdentifier() {
    return isset($this->identifier) ? $this->identifier : '';
  }

  /**
   * Return the identifier depth for this JSON source.
   */
  public function getIdentifierDepth() {
    return isset($this->identifierDepth) ? $this->identifierDepth : NULL;
  }

  /**
   * {@inheritdoc}
   * Fetch all fields.
   */
  public function getSourceFields($url) {

    $iterator = $this->getSourceData($url);

    // Recurse through the result array. When there is an array of items at the
    // expected depth that has the expected identifier as one of the keys, pull that
    // array out as a distinct item.
    // $identifier = $this->getIdentifier();
    // $identifierDepth = $this->getIdentifierDepth();
    $items = array();
    while ($iterator->valid()) {
      $iterator->next();
      $item = $iterator->current();
      if (is_array($item)) {
        $items[] = $item;
      }
    }

    return $iterator;
  }

  /**
   * {@inheritdoc}
   * Process the fields
   */
  public function getSourceFieldsIterator($url) {
    return $this->getSourceFields($url);
  }

  /**
   * Get the source data for reading.
   *
   * @param string $url
   *   The URL to read the source data from.
   *
   * @return \RecursiveIteratorIterator|resource
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function getSourceData($url) {
    try {
      $response = [];
      $ical = new ICal($url);
      foreach ($ical->events() as $events) {
        $response[] = (array) $events;
      }
      // Each object returns and event array.
      $obj = new \ArrayIterator($response);
      return $obj;
    } catch (RequestException $e) {
      throw new MigrateException($e->getMessage(), $e->getCode(), $e);
    }
  }
}
