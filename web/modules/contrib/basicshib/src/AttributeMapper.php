<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/15/17
 * Time: 11:32 AM
 */

namespace Drupal\basicshib;


use Drupal\basicshib\Exception\AttributeException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ServerBag;

class AttributeMapper implements AttributeMapperInterface {

  /**
   * @var ImmutableConfig
   */
  private $configuration;

  /**
   * @var ServerBag
   */
  private $server;

  /**
   * @var array
   */
  private $attribute_map = [];

  /**
   * AttributeMapper constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   * @param RequestStack $request_stack
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->configuration = $config_factory
      ->get('basicshib.settings');

    $this->server = $request_stack
      ->getCurrentRequest()
      ->server;

    $this->attribute_map = $this->getAttributeMap();
  }

  /**
   * Get an attribute's value.
   *
   * @param $id
   *   The id of the attribute to fetch.  An exception is thrown if no mapping
   *   exists for the provided id.
   *
   * @param bool $empty_allowed
   *   Whether to allow empty attributes. When false, an exception is thrown if
   *   the attribute is not set.
   *
   * @return string
   *   The value of the attribute
   *
   * @throws AttributeException
   *
   * @todo Remove $empty_allowed and check this somewhere else.
   */
  public function getAttribute($id, $empty_allowed = false) {

    if (isset($this->attribute_map[$id])) {
      $def = $this->attribute_map[$id];
      $value = $this->server->get($def['name']);
      if (!$value && !$empty_allowed) {
        throw AttributeException::createWithContext(
          'Attribute is not set: @name (mapped to @id)',
          ['@name' => $def['name'], '@id' => $id],
          AttributeException::NOT_SET
        );
      }
      return $value;
    }
    throw AttributeException::createWithContext(
      'Key attribute is not mapped: @id',
      ['@id' => $id],
      AttributeException::NOT_MAPPED
    );
  }

  /**
   * @return array
   *
   * @throws AttributeException
   */
  private function getAttributeMap() {
    $config = $this->configuration
      ->get('attribute_map');

    foreach ($config['key'] as $id => $name) {
      $this->attribute_map[$id] = [
        'id' => $id,
        'name' => $name,
        'key' => true,
      ];
    }

    foreach ($config['optional'] as $def) {
      if (isset($this->attribute_map[$def['id']])) {
        throw AttributeException::createWithContext(
          'Atrribute with id @id is already defined',
          ['@id' => $def['id']],
          AttributeException::DUPLICATE_ID
        );
      }

      $this->attribute_map[$def['id']] = [
        'id' => $def['id'],
        'name' => $def['name'],
        'key' => false,
      ];
    }

    return $this->attribute_map;
  }
}
