<?php

namespace Drupal\blockchain\Utils;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface BlockchainRequestInterface.
 *
 * @package Drupal\blockchain\Utils
 */
interface BlockchainRequestInterface extends BlockchainHttpInterface {

  const TYPE_SUBSCRIBE = 'subscribe';
  const TYPE_ANNOUNCE = 'announce';
  const TYPE_COUNT = 'count';
  const TYPE_FETCH = 'fetch';
  const TYPE_PULL = 'pull';
  // Hash of (self+bc_token)
  const PARAM_AUTH = 'auth';
  const PARAM_TIMESTAMP = 'timestamp';
  const PARAM_PREVIOUS_HASH = 'previous_hash';
  const PARAM_SELF_URL = 'self_url';

  /**
   * Getter for all params.
   *
   * @return string[]
   *   Array.
   */
  public static function getAllParamKeys();

  /**
   * Getter for param.
   *
   * @return string
   *   Value.
   */
  public function getAuthParam();

  /**
   * Getter for param.
   *
   * @return array
   *   Value.
   */
  public function getBlocksParam();

  /**
   * Getter for type property.
   *
   * @param string $type
   *   Given type.
   *
   * @return string
   *   Value.
   */
  public function setRequestType($type);

  /**
   * Getter for type property.
   *
   * @return string
   *   Value.
   */
  public function getRequestType();

  /**
   * BlockchainRequestInterface constructor.
   *
   * @param array $params
   *   Params.
   * @param string $ip
   *   Client ip.
   */
  public function __construct(array $params, $ip);

  /**
   * Setter for is valid property.
   *
   * @param bool $valid
   *   Value.
   *
   * @return static
   *   This object.
   */
  public function setValid($valid);

  /**
   * Predicate to define validness.
   *
   * @return bool
   *   Test result.
   */
  public function isValid();

  /**
   * Factory method.
   *
   * @param array $params
   *   Params.
   * @param string $ip
   *   Client ip.
   *
   * @return static
   *   This object.
   */
  public function create(array $params, $ip);

  /**
   * BlockchainRequestInterface constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return static
   *   This object.
   */
  public static function createFromRequest(Request $request);

  /**
   * Predicate.
   *
   * @return bool
   *   Test result.
   */
  public function hasAuthParam();

  /**
   * Predicate.
   *
   * @return bool
   *   Test result.
   */
  public function hasBlocksParam();

  /**
   * Predicate.
   *
   * @return bool
   *   Test result.
   */
  public function hasTimestampParam();

  /**
   * Getter for timestamp property.
   *
   * @return string
   *   Value.
   */
  public function getTimestampParam();

  /**
   * Predicate.
   *
   * @return bool
   *   Test result.
   */
  public function hasPreviousHashParam();

  /**
   * Getter for previous hash property.
   *
   * @return string
   *   Value.
   */
  public function getPreviousHashParam();

  /**
   * Serializer.
   *
   * @return string
   *   Serialized object.
   */
  public function sleep();

  /**
   * Deserializer.
   *
   * @param string $data
   *   String data.
   *
   * @return $this
   *   This object.
   */
  public static function wakeup($data);

  /**
   * Getter for param.
   *
   * @return string
   *   Value.
   */
  public function getSelfUrl();

  /**
   * Predicate.
   *
   * @return bool
   *   Test result.
   */
  public function hasSelfUrl();

}
