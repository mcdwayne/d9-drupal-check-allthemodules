<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 29.01.17
 * Time: 12:29
 */

namespace Drupal\elastic_search\Mapping;

use Drupal\elastic_search\Elastic\ElasticConnectionFactory;
use Drupal\elastic_search\Exception\MappingValidatorException;

/**
 * Class MappingValidator
 *
 * @package Drupal\elastic_search\Mapping
 */
class MappingValidator {

  /**
   * @var ElasticConnectionFactory
   */
  protected $connectionFactory;

  /**
   * @var string
   */
  protected $suffix = '_validation_test';

  /**
   * MappingValidator constructor.
   *
   * @param \Drupal\elastic_search\Elastic\ElasticConnectionFactory $connectionFactory
   */
  public function __construct(ElasticConnectionFactory $connectionFactory) {
    $this->connectionFactory = $connectionFactory;
  }

  /**
   * @param string $suffix
   */
  public function setSuffix(string $suffix) {
    $this->suffix = $suffix;
  }

  /**
   * @return string
   */
  public function getSuffix(): string {
    return $this->suffix;
  }

  /**
   * Validate an elastic mapping
   *
   * Because the Elastic validate API doesn't support validating an index
   * operation we need to actually try to create the index and see if it throws
   * an exception. So this class will make a test index, see what happens and
   * then delete it if it was created successfully. To make sure that indices
   * are not overridden it adds a suffix to the index name This can be
   * specified, or the default is used
   *
   * @param array $map
   *
   * @return bool
   */
  public function validate(array $map) {
    return $this->validateMapping($map);
  }

  /**
   * Internal validation function
   *
   * @param array $mapping
   *
   * @return bool
   *
   * @throws \Exception
   */
  private function validateMapping(array $mapping) {

    $client = $this->connectionFactory->getElasticConnection();

    if (!array_key_exists('index', $mapping)) {
      throw new MappingValidatorException('no index key in mapping');
    }

    $mapping['index'] .= $this->suffix;
    try {
      $response = $client->indices()->create($mapping);
    } catch (\Throwable $t) {
      $client->indices()->delete(['index' => $mapping['index']]);
      return FALSE;
    }
    $del = $client->indices()->delete(['index' => $mapping['index']]);

    //Test that it could be created and deleted successfully.
    //If either fails then we return false
    return $response['acknowledged'] && $del['acknowledged'];
  }

}
