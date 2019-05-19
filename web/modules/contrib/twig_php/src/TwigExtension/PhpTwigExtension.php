<?php
/**
 * @file
 * Contains the PhpTwigExtension class.
 */

namespace Drupal\twig_php\TwigExtension;

/**
 * Class PhpTwigExtension
 */
class PhpTwigExtension extends \Twig_Extension {

  /**
   * This module is dangerous and should never be used.
   */
  const IS_DANGEROUS = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      'php' => new \Twig_SimpleFilter('php', [
        $this,
        'executeCode'
      ], []),
      'php_include' => new \Twig_SimpleFilter('php_include', [
        $this,
        'includeFile'
      ], []),
      'php_require' => new \Twig_SimpleFilter('php_require', [
        $this,
        'requireFile'
      ], []),
      'php_function' => new \Twig_SimpleFilter('php_function', [
        $this,
        'executeFunction'
      ], ['is_variadic' => TRUE]),
      'php_func' => new \Twig_SimpleFilter('php_func', [
        $this,
        'executeFunction'
      ], ['is_variadic' => TRUE]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'php';
  }

  /**
   * Gets the config value for twig_php.settings.
   *
   * @param string $key
   *   Config key.
   *
   * @return mixed
   *   Returns the config value.
   */
  private function getConfig($key) {
    $config = \Drupal::config('twig_php.settings');
    return $config->get($key);
  }

  /**
   * Executes a PHP function.
   *
   * @param string $function
   *   PHP function name.
   * @param array $args
   *   PHP function args.
   *
   * You can set the options using Drupal Console:
   * > drupal config:override twig_php.settings allow_function_execution 1
   *
   * To disable PHP function execution you can run:
   * > drupal config:override twig_php.settings allow_function_execution 0
   *
   * To only allow execution of specific functions, you can modify the allowed_functions
   * config option. Example:
   * > drupal config:edit twig_php.settings
   *   allowed_functions:
   *     - print_r
   *     - var_dump
   *     - var_export
   *     - user_load
   *
   * @return mixed
   *   Returns the result of executing the PHP function with args.
   */
  public function executeFunction($function, array $args = array()) {
    if (!$this->getConfig('allow_function_execution')) {
      throw new \Exception('Cannot execute PHP functions in Twig templates.');
    }
    if ($allowed_functions = $this->getConfig('allowed_functions')) {
      if (!in_array($function, $allowed_functions)) {
        throw new \Exception('Invalid PHP function "' . $function . '" executed in Twig template.');
      }
    }
    return call_user_func_array($function, $args);
  }

  /**
   * Requires inclusion of a file.
   *
   * @param string $file
   *   File path to require.
   * @param boolean $return
   *   Whether the result of including the file should be returned.
   *
   * You can set the options using Drupal Console:
   * > drupal config:override twig_php.settings allow_require_file 1
   *
   * To disable PHP file includes you can run:
   * > drupal config:override twig_php.settings allow_require_file 0
   *
   * @return mixed
   *   Returns the required file output.
   */
  public function requireFile($file, $return = FALSE) {
    if (!$this->getConfig('allow_require_file')) {
      throw new \Exception('Cannot require files in Twig templates.');
    }
    try {
      $result = require $file;
      if ($return) {
        return $result;
      }
    } catch (\Exception $e) {
      return $e->getMessage();
    }
  }

  /**
   * Includes a file.
   *
   * @param string $file
   *   File path to include.
   * @param boolean $return
   *   Whether the result of including the file should be returned.
   *
   * You can set the options using Drupal Console:
   * > drupal config:override twig_php.settings allow_include_file 1
   *
   * To disable PHP file includes you can run:
   * > drupal config:override twig_php.settings allow_include_file 0
   *
   * @return mixed
   *   Returns the included file output.
   */
  public function includeFile($file, $return = FALSE) {
    if (!$this->getConfig('allow_include_file')) {
      throw new \Exception('Cannot include files in Twig templates.');
    }
    try {
      $result = include $file;
      if ($return) {
        return $result;
      }
    } catch (\Exception $e) {
      return $e->getMessage();
    }
  }

  /**
   * Executes arbitrary PHP code.
   *
   * @param string $codez
   *   Dangerous code to execute.
   * @param boolean $return
   *   Whether to include a return statement in the code.
   *
   * You can set the options using Drupal Console:
   * > drupal config:override twig_php.settings allow_php_execution 1
   *
   * To disable PHP execution you can run:
   * > drupal config:override twig_php.settings allow_php_execution 0
   *
   * @return mixed
   *   Returns the executed code result.
   */
  public function executeCode($codez, $return = TRUE) {
    if (!$this->getConfig('allow_php_execution')) {
      throw new \Exception('Cannot execute PHP code in Twig templates.');
    }
    if ($return) {
      $codez = 'return ' . $codez;
    }
    try {
      $result = eval($codez);
    } catch (\Exception $e) {
      $result = $e->getMessage();
    }
    return $result;
  }

}
