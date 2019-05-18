<?php

namespace Drupal\drupal_inquicker\Source;

// @codingStandardsIgnoreStart
use Drupal\drupal_inquicker\Inquicker\RowCollection;
// @codingStandardsIgnoreEnd
use Drupal\drupal_inquicker\Schedule\ScheduleCollection;
use Drupal\drupal_inquicker\traits\CommonUtilities;
use Drupal\drupal_inquicker\traits\DependencyInjection;

/**
 * A source of Inquicker information.
 */
abstract class Source {

  use CommonUtilities;
  use DependencyInjection;

  /**
   * Constructor.
   *
   * @param string $key
   *   The key of this source, its identifier, for example: "default".
   * @param array $config
   *   The configuration for this source as defined in settings.php (see
   *   ./README.md) which will contain information required by subclasses,
   *   such as the API key.
   */
  public function __construct(string $key, array $config) {
    $this->key = $key;
    $this->config = $config;
  }

  /**
   * Get an item from the config.
   *
   * @param string $key
   *   The key to fetch such as "api".
   *
   * @return mixed
   *   The value of the configuration.
   *
   * @throws \Exception
   */
  public function config(string $key) {
    if (empty($this->config[$key])) {
      throw new \Exception($this->t('key @k is required in config', [
        '@k' => $key,
      ]));
    }
    return $this->config[$key];
  }

  /**
   * Get the key which defines this source.
   *
   * @return string
   *   A key such as "live", "dummy", etc.
   */
  public function key() : string {
    return $this->key;
  }

  /**
   * Is this source a live source.
   *
   * @return bool
   *   Is this source a live source.
   */
  abstract public function live() : bool;

  /**
   * Get rows from Inquicker.
   *
   * @param string $type
   *   A type such as "schedules", "languages", "appointment_types",
   *   "service_lines", "regions", "locations", "providers", "facilities".
   *
   * @return RowCollection
   *    A row collection from Inquicker.
   *
   * @throws \Throwable
   */
  public function rows(string $type, array $query = []) : RowCollection {
    return new RowCollection($this->paged('/v2/' . $type, $query));
  }

  /**
   * Modify a query before it is sent to the source.
   *
   * @param array $query
   *   A query (associative array of $_GET parameters).
   *
   * @return array
   *   A modified query.
   */
  public function modifyQuery(array $query) : array {
    $return = $query;
    if (empty($return['page'])) {
      $return['page'] = 1;
    }
    return $return;
  }

  /**
   * Get all data across multiple pages.
   *
   * @param string $path
   *   A path such as /v2/locations.
   *
   * @return array
   *   All responses from all pages.
   *
   * @throws \Exception
   */
  public function paged(string $path, array $query = []) : array {
    return $this->pagedRecursive($path, $this->modifyQuery($query));
  }

  /**
   * Helper function to get all data across multiple pages.
   *
   * You should normally use ::paged().
   *
   * @param string $path
   *   A path such as /v2/locations.
   *
   * @return array
   *   All responses from all pages.
   *
   * @throws \Exception
   */
  public function pagedRecursive(string $path, array $query = []) : array {
    $data = [];
    $url = $this->url() . '/' . $path;
    $page = $this->jsonDecode((string) $this->response($url, $query ? [
      'query' => $query,
    ] : [])->getBody());
    if ($page['metadata']['statusCode'] != 200) {
      throw new \Exception($this->t('Could not get @p', [
        '@p' => $path,
      ]));
    }
    $data = empty($page['data']) ? [] : $page['data'];
    if (!empty($page['metadata']['pagination']['nextPage'])) {
      $data = array_merge($data, $this->pagedRecursive($page['metadata']['pagination']['nextPage']));
    }
    return $data;
  }

  /**
   * Get a response for this Source.
   *
   * @param string $uri
   *   A URL for this request.
   * @param array $options
   *   Request options.
   *
   * @return object
   *   A response object.
   *
   * @throws \Exception
   */
  abstract public function response($uri, $options = []);

  /**
   * Get all Schedules for this Source.
   *
   * @param array $query
   *   Get parameters for this query, for example: [
   *     'locations' => 'location_a,location_b',
   *     'service_lines' => 'service_line_a,service_line_b',
   *   ].
   * @param string $default_type_name
   *   Certain Inquicker appointments do not have a type; however we still
   *   need to categorize them and format them as if they did. For such cases,
   *   set the desired human-readable name for output.
   *
   * @return ScheduleCollection
   *   All schedule times.
   *
   * @throws \Exception
   */
  public function schedules(array $query = [], $default_type_name = 'default') : ScheduleCollection {
    return new ScheduleCollection($this->paged('/v2/schedules', $query), $default_type_name);
  }

  /**
   * The URL for this Source.
   *
   * @return string
   *   A URL for this Source.
   */
  public function url() : string {
    return '';
  }

  /**
   * TRUE if this source is valid.
   *
   * @return bool
   *   TRUE if this source is valid.
   */
  public function valid() : bool {
    try {
      $this->validate();
      return TRUE;
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
      return FALSE;
    }
  }

  /**
   * Throw an exception if the Source is invalid.
   *
   * @throws \Exception
   */
  abstract public function validate();

}
