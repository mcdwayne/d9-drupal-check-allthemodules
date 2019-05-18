<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\Schema;

use CleverReach\BusinessLogic\Utility\ArticleSearch\SerializableJson;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;

/**
 * Class SchemaAttribute, base schema attribute.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\Schema
 */
abstract class SchemaAttribute extends SerializableJson {
  /**
   * @var  string*/
  protected $code;

  /**
   * @var  string*/
  protected $name;

  /**
   * @var  string*/
  protected $type;

  /**
   * @var bool*/
  protected $searchable = FALSE;

  /**
   * @var array*/
  protected $searchableExpressions = [];

  /**
   * @var arrayAllpossiblesearchableconditions*/
  private $possibleConditions = [
    Conditions::CONTAINS,
    Conditions::EQUALS,
    Conditions::GREATER_EQUAL,
    Conditions::GREATER_THAN,
    Conditions::LESS_EQUAL,
    Conditions::LESS_THAN,
    Conditions::NOT_EQUAL,
  ];

  /**
   * SchemaAttribute constructor.
   *
   * @param string $code
   * @param string $name
   * @param bool $searchable
   * @param array $searchableExpressions,
   *   Conditions enum contains all possible values for searchable expressions.
   */
  protected function __construct($code, $name, $searchable, $searchableExpressions = []) {
    $this->validateSchemaAttribute($code, $name, $searchableExpressions);

    $this->code = $code;
    $this->name = $name;
    $this->searchable = $searchable;
    $this->searchableExpressions = $searchableExpressions;
  }

  /**
   * @return string
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * @return bool
   */
  public function isSearchable() {
    return $this->searchable;
  }

  /**
   * @return array
   */
  public function getSearchableExpressions() {
    return $this->searchableExpressions;
  }

  /**
   * Prepares object for json serialization.
   *
   * @return array
   */
  public function toArray() {
    $result = [
      'code' => $this->code,
      'name' => $this->name,
      'type' => $this->type,
    ];

    if ($this->searchable) {
      $result['searchable'] = $this->searchable;
    }

    if (is_array($this->searchableExpressions) && count($this->searchableExpressions) > 0) {
      $result['searchableExpressions'] = $this->searchableExpressions;
    }

    return $result;
  }

  /**
   *
   */
  private function validateSchemaAttribute($code, $name, $searchableExpressions) {
    if (empty($code)) {
      Logger::logError('Item code for schema attribute is mandatory.');
      throw new \InvalidArgumentException('Item code for schema attribute is mandatory.');
    }

    if (empty($name)) {
      Logger::logError('Name for schema attribute is mandatory.');
      throw new \InvalidArgumentException('Name for schema attribute is mandatory.');
    }

    foreach ($searchableExpressions as $expression) {
      if (!in_array($expression, $this->possibleConditions)) {
        $errorMessage = 'Invalid expression : ' . $expression . '. ' .
                    'Expression must be value from enum: ' . implode(',', $this->possibleConditions);
        Logger::logError($errorMessage);
        throw new \InvalidArgumentException($errorMessage);
      }
    }
  }

}
