<?php

namespace Drupal\tag1quo\Adapter\Config;

/**
 * Class Config.
 *
 * @internal This class is subject to change.
 */
class Config6 extends Config {

  /**
   * List of config names that will be converted into variable prefixes.
   *
   * @var array
   */
  protected static $prefixes = array(
    'system.site' => 'site',
    'system.theme' => 'theme',
    'tag1quo.settings' => 'tag1quo',
  );

  /**
   * {@inheritdoc}
   */
  public function __construct($name) {
    $name = isset(static::$prefixes[$name]) ? static::$prefixes[$name] : str_replace('.', '_', $name);
    parent::__construct($name);
  }

  /**
   * {@inheritdoc}
   */
  public function clear($key) {
    \variable_del($this->convertKey($key));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function convertKey($key) {
    static $keys = array();
    if (!isset($keys[$key])) {
      $keys[$key] = $this->name . '_' . str_replace('.', '_', $key);
    }
    return $keys[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    global $conf;
    db_query("DELETE FROM {variable} WHERE name LIKE '%s\_%'", $this->name);
    cache_clear_all('variables', 'cache');
    foreach (array_keys($conf) as $name) {
      if (strpos($name, $this->name . '_') === 0) {
        unset($conf[$name]);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    $value = \variable_get($this->convertKey($key));
    return $value !== NULL ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    \variable_set($this->convertKey($key), $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    return $this;
  }

}
