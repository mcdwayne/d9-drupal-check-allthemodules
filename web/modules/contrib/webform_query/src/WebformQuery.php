<?php

namespace Drupal\webform_query;

use Drupal\Core\Database\Connection;

class WebformQuery {

  /**
   * @var \Drupal\Core\Database\Connection; 
   */
  protected $connection;

  /**
   * Array of conditions.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * Array of sort conditions.
   *
   * @var array
   */
  protected $sort = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }
  
  /**   
   * @param integer $webform_id
   */
  public function setWebform($webform_id = NULL) {
    if (!is_null($webform_id)) {
      $this->addCondition('webform_id', $webform_id);
    }      
  }
    
  /**
   * 
   * @param string $field
   *  Field name.
   * @param mixed $value
   *  Value to compare.
   * @param type $operator
   *  Operator.
   * 
   * @return $this
   */
  public function addCondition($field, $value = NULL, $operator = '=') {
    // Check for webform_id.
    if ($field === 'webform_id') {
      // Check for existing condition at 0.
      if (key_exists(0, $this->conditions)) {
        $this->conditions[] = $this->conditions[0];
      }
       $this->conditions[0] = [
        'field' => $field,
        'value' => $value,
        'operator' => $operator,
      ];
    }
    else {

      if (empty($operator)) {
        $operator = '=';
      }

      // Validate opertaor.
      $operator = $this->validateOperator($operator);

      // If operator is good then add the condition.
      if ($operator !== '') {
        $this->conditions[] = [
          'field' => $field,
          'value' => $value,
          'operator' => $operator,
        ];
      }
    }

    return $this;
  }

  public function orderBy($field, $direction = 'ASC') {
    // Make sure direction is valid.
    $direction = ($direction !== 'ASC') ? 'DESC' : 'ASC';

    $this->sort[] = [
      'field' => $field,
      'direction' => $direction,
    ];

    return $this;

  }

  /**
   * 
   * Execute the query.
   * 
   * @return array
   *  Array of objects with one property: sid
   */
  public function execute() {
    // Generate query elements from the conditions.
    $query_elements = $this->buildQuery();
    
    // Clear the conditions and sorting.
    $this->conditions = [];
    $this->sort = [];

    // Execute the query.
    $response = $this->connection->query($query_elements['query'], $query_elements['values']);

    // Return the results.
    return $response->fetchAll();        
  }

  /**
   * Build the query from the conditions.
   */
  public function buildQuery() {
    $query = 'SELECT DISTINCT sid FROM {webform_submission_data} wsd';
    $values = [];
    foreach ($this->conditions as $key => $condition) {
      // Check if it's the first condition.
      if ($key === 0) {
        // Check for database field webform_id.
        if ($condition['field'] == 'webform_id') {
          $query .= ' WHERE wsd.webform_id ' . $condition['operator'] . ' :' . $condition['field'];
        }
        else {
          $query .= ' WHERE wsd.name = :' . $condition['field'] . '_name AND wsd.value ' . $condition['operator'] . ' :' .  $condition['field'];
          $values[':' . $condition['field'] .'_name'] = $condition['field'];
        }                
      }
      else {
        // Normal condition for a webform submission field.
        $alias = 'wsd' . $key;
        $query .= ' AND sid IN (SELECT sid from {webform_submission_data} ' . $alias . ' WHERE ' . $alias . '.name = :' . $condition['field'] . '_name';
        $query .= ' AND ' . $alias . '.value ' . $condition['operator'] . ' :' . $condition['field'] . ')';
        $values[':' . $condition['field'] .'_name'] = $condition['field'];
      }
      $values[':' . $condition['field']] = $condition['value'];
    }

    // Check for sort criteria.
    foreach ($this->sort as $key => $orderby) {
      // Add comma separator for for additional ORDER BY.
      if ($key > 0) {
        $query  .= ',';
      }
      // "obt": Order By Table.
      $orderby_alias = 'obt' . $key;

      $query .= ' ORDER BY ('
        . 'SELECT ' . $orderby_alias . '.value FROM {webform_submission_data} ' . $orderby_alias 
        . ' WHERE ' . $orderby_alias . '.name=\'' . $orderby['field'] . '\''
        . ' AND ' . $orderby_alias . '.sid=wsd.sid'
        . ') ' . $orderby['direction'];
    }

    return ['query' => $query, 'values' => $values];

  }

  /**
   * 
   * Perform basic validation of the operator.
   * 
   * @param string $operator
   * @return string
   *  Return operator or nothing.   
   */
  public function validateOperator($operator) {
    if (stripos($operator, 'UNION') !== FALSE || strpbrk($operator, '[-\'"();') !== FALSE) {      
      trigger_error('Invalid characters in query operator: ' . $operator, E_USER_ERROR);
      return '';      
    }
    return $operator;
  }

}
