<?php

namespace Drupal\prepared_data\Provider;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\prepared_data\Builder\DataBuilderInterface;
use Drupal\prepared_data\Processor\ProcessorInterface;
use Drupal\prepared_data\Processor\ProcessorTrait;
use Drupal\prepared_data\Storage\StorageInterface;

/**
 * A base class for providers of prepared data.
 */
abstract class ProviderBase extends PluginBase implements ProviderInterface, ProcessorInterface {

  use ProcessorTrait;

  /**
   * The names of the key parameters
   *
   * @var array
   */
  protected $keyParamNames;

  /**
   * The storage of prepared data.
   *
   * @var \Drupal\prepared_data\Storage\StorageInterface
   */
  protected $dataStorage;

  /**
   * The builder which builds up and refreshes prepared data.
   *
   * @var \Drupal\prepared_data\Builder\DataBuilderInterface
   */
  protected $dataBuilder;

  /**
   * The account associated as current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Holds a limited list of recent key matches
   * against redundant re-matching.
   *
   * @var string[]
   */
  protected $matchStack = [];

  /**
   * An iterator for performing ::nextMatch() calls.
   *
   * @var int
   */
  protected $nextMatchIterator = -1;

  /**
   * {@inheritdoc}
   */
  public function match($argument) {
    $param_names = $this->getKeyParamNames();
    if (is_string($argument)) {
      if (isset($this->matchStack[$argument])) {
        return $this->matched($argument, $this->matchStack[$argument]);
      }
      elseif (count($this->matchStack) > 10) {
        array_shift($this->matchStack);
      }

      $pattern = $this->getKeyPattern();
      if (empty($param_names) && ($argument === $pattern)) {
        return $this->matched($argument, $pattern);
      }
      $params = [];
      $pattern_parts = explode(':', $pattern);
      foreach (explode(':', $argument) as $pos => $part) {
        if (isset($param_names[$pos])) {
          $params[$param_names[$pos]] = $part;
        }
        elseif (!isset($pattern_parts[$pos]) || ($pattern_parts[$pos] !== $part)) {
          return $this->matched($argument, FALSE);
        }
      }
      return $this->match($params);
    }
    if (is_array($argument)) {
      $params = $argument;
      if (empty($param_names) && empty($params)) {
        return $this->getKeyPattern();
      }
      if (count($param_names) !== count($params)) {
        return FALSE;
      }
      foreach ($param_names as $param_name) {
        if (!isset($params[$param_name])) {
          return FALSE;
        }
      }
      return $this->buildKeyForParams($params);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function nextMatch($partial = NULL, $reset = FALSE) {
    if (TRUE === $reset) {
      $this->nextMatchIterator = -1;
    }
    $this->nextMatchIterator++;

    if (NULL === $partial) {
      return $this->match([]);
    }
    $params = $this->getParameters($partial);
    return $this->match($params);
  }

  /**
   * {@inheritdoc}
   */
  public function setNextMatchOffset($offset) {
    $this->nextMatchIterator = $offset - 1;
  }

  /**
   * Tells the provider that the given candidate matched with the given key.
   *
   * @param string $candidate
   *   The candidate key.
   * @param string|false $key
   *   The key that matches to the candidate, or FALSE if it's not a match.
   *
   * @return string|false
   *   The key that matches to the candidate, or FALSE if it's not a match.
   */
  protected function matched($candidate, $key) {
    unset($this->matchStack[$candidate]);
    $this->matchStack[$candidate] = $key;
    return $key;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $argument) {
    if (!$this->match($argument)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function demand($argument, $force_valid = FALSE) {
    if (!($key = $this->match($argument))) {
      return NULL;
    }
    if (!($this->access($this->getCurrentUser(), $key))) {
      return NULL;
    }
    $storage = $this->getDataStorage();
    $builder = $this->getDataBuilder();
    $needs_save = FALSE;
    if ($data = $storage->load($key)) {
      if ($data->shouldRefresh()) {
        if ($force_valid) {
          $builder->refresh($data);
          $needs_save = TRUE;
        }
        elseif (!((int) $data->lastUpdated() > $data->expires())) {
          // Save the flag to refresh at the next iteration.
          $needs_save = TRUE;
        }
      }
    }
    else {
      $data = $builder->build($key);
      $needs_save = TRUE;
    }
    if ($data->shouldDelete()) {
      $storage->delete($data->key());
    }
    elseif ($needs_save) {
      $storage->save($data->key(), $data);
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function demandFresh($argument) {
    if (!($key = $this->match($argument))) {
      return NULL;
    }
    if (!($this->access($this->getCurrentUser(), $key))) {
      return NULL;
    }
    $storage = $this->getDataStorage();
    $builder = $this->getDataBuilder();
    if ($data = $storage->load($key)) {
      $builder->refresh($data);
    }
    else {
      $data = $builder->build($key);
    }
    if ($data->shouldDelete()) {
      $storage->delete($data->key());
    }
    else {
      $storage->save($data->key(), $data);
    }
    return $data;
  }

  /**
   * Builds up a key for the given set of named parameters.
   *
   * @param array $parameters
   *   The parameters to build the query for.
   *
   * @return string|false
   *   The key as string, or FALSE if the build was not successful.
   */
  protected function buildKeyForParams(array $parameters) {
    $pattern = $this->getKeyPattern();
    $bracket_params = [];
    foreach ($parameters as $name => $value) {
      if (strpos($name, '{') !== 0) {
        $bracket_params['{' . $name . '}'] = $value;
      }
      else {
        $bracket_params[$name] = $value;
      }
    }
    if ($key = @strtr($pattern, $bracket_params)) {
      return $key;
    }
    return FALSE;
  }

  /**
   * Get the names of the key parameters.
   *
   * @return array
   *   The names of the key parameters,
   *   keyed by position in key pattern.
   */
  protected function getKeyParamNames() {
    if (!isset($this->keyParamNames)) {
      $pattern = $this->getKeyPattern();
      $named_params = [];
      foreach (explode(':', $pattern) as $pos => $part) {
        $length = strlen($part);
        if ((strpos($part, '{') === 0) && substr($part, -1) === '}') {
          $named_param = substr($part, 1, $length - 2);
          $named_params[$pos] = $named_param;
        }
      }
      $this->keyParamNames = $named_params;
    }
    return $this->keyParamNames;
  }

  /**
   * Get the parameters of the given key.
   *
   * @param string $key
   *   The key to extract the parameters from.
   * @return array
   *   An associative array of parameters, keyed by parameter name.
   */
  protected function getParameters($key) {
    $parts = explode(':', $key);
    $param_names = $this->getKeyParamNames();
    $params = [];
    foreach ($param_names as $pos => $name) {
      if (isset($parts[$pos])) {
        $params[$name] = $parts[$pos];
      }
    }
    return $params;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataStorage() {
    return $this->dataStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataBuilder() {
    return $this->dataBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentUser() {
    return $this->currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataStorage(StorageInterface $storage) {
    $this->dataStorage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataBuilder(DataBuilderInterface $builder) {
    $this->dataBuilder = $builder;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentUser(AccountInterface $account) {
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateValues() {
    return [
      'nextMatchIterator' => $this->nextMatchIterator,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setStateValues(array $values) {
    if (isset($values['nextMatchIterator'])) {
      $this->nextMatchIterator = (int) $values['nextMatchIterator'];
    }
  }

}
