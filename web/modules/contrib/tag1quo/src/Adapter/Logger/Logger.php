<?php

namespace Drupal\tag1quo\Adapter\Logger;

use Drupal\tag1quo\Adapter\Core\Core;
use Drupal\tag1quo\VersionedClass;

/**
 * Class Logger.
 *
 * @internal This class is subject to change.
 */
class Logger extends VersionedClass {

  const CHANNEL = 'tag1quo';

  const EMERGENCY = 'emergency';
  const ALERT = 'alert';
  const CRITICAL = 'critical';
  const ERROR = 'error';
  const WARNING = 'warning';
  const NOTICE = 'notice';
  const INFO = 'info';
  const DEBUG = 'debug';

  const RFC_EMERGENCY = 0;
  const RFC_ALERT = 1;
  const RFC_CRITICAL = 2;
  const RFC_ERROR = 3;
  const RFC_WARNING = 4;
  const RFC_NOTICE = 5;
  const RFC_INFO = 6;
  const RFC_DEBUG = 7;

  /**
   * An array of blacklisted functions.
   *
   * @var array
   */
  protected static $blacklistFunctions = array(
    'debug',
    '_drupal_error_handler',
    '_drupal_exception_handler',
  );

  /**
   * The channel that is being logged to.
   *
   * @var string
   */
  protected $channel;

  /**
   * The Core adapter.
   *
   * @var \Drupal\tag1quo\Adapter\Core\Core
   */
  protected $core;


  /**
   * Flag indicating whether watchdog is available.
   *
   * @var bool
   */
  protected $watchdogAvailable;

  /**
   * Logger constructor.
   *
   * @param \Drupal\tag1quo\Adapter\Core\Core $core
   *   A Core adapter instance.
   * @param string $channel
   *   The channel to log to.
   */
  public function __construct(Core $core, $channel = self::CHANNEL) {
    $this->core = $core;
    $this->channel = $channel ?: static::CHANNEL;
    $this->watchdogAvailable = function_exists('watchdog');
  }

  /**
   * Creates a new Logger instance.
   *
   * @param \Drupal\tag1quo\Adapter\Core\Core $core
   *   A Core adapter instance.
   * @param string $channel
   *   The channel to log to.
   *
   * @return static
   */
  public static function create(Core $core, $channel = self::CHANNEL) {
    return static::createVersionedStaticInstance([$core, $channel]);
  }

  /**
   * Decodes an exception and retrieves the correct caller.
   *
   * @param \Exception|\Throwable $exception
   *   The exception object that was thrown.
   *
   * @return array
   *   An error in the format expected by _drupal_log_error().
   */
  public static function decodeException($exception) {
    $message = $exception->getMessage();
    $backtrace = $exception->getTrace();

    // Add the line throwing the exception to the backtrace.
    array_unshift($backtrace, array(
      'line' => $exception->getLine(),
      'file' => $exception->getFile(),
    ));

    // For PDOException errors, we try to return the initial caller,
    // skipping internal functions of the database layer.
    if (static::isDatabaseConnection($exception)) {
      // The first element in the stack is the call, the second element gives us
      // the caller. We skip calls that occurred in one of the classes of the
      // database layer or in one of its global functions.
      $db_functions = array('db_query', 'db_query_range');
      while (!empty($backtrace[1]) && ($caller = $backtrace[1]) &&
        ((isset($caller['class']) && (strpos($caller['class'], 'Query') !== FALSE || strpos($caller['class'], 'Database') !== FALSE || strpos($caller['class'], 'PDO') !== FALSE)) ||
          in_array($caller['function'], $db_functions))) {
        // We remove that call.
        array_shift($backtrace);
      }
      if (isset($exception->query_string, $exception->args)) {
        $message .= ": " . $exception->query_string . "; " . print_r($exception->args, TRUE);
      }
    }

    $caller = static::getLastCaller($backtrace);

    return array(
      '%type' => get_class($exception),
      // The standard PHP exception handler considers that the exception message
      // is plain-text. We mimic this behavior here.
      '@message' => $message,
      '%function' => $caller['function'],
      '%file' => $caller['file'],
      '%line' => $caller['line'],
      'severity_level' => static::ERROR,
      'backtrace' => $backtrace,
      '@backtrace_string' => $exception->getTraceAsString(),
    );
  }

  public static function isDatabaseConnection(\Exception $exception) {
    return $exception instanceof \PDOException;
  }

  /**
   * Gets the last caller from a backtrace.
   *
   * @param array $backtrace
   *   A standard PHP backtrace. Passed by reference.
   *
   * @return array
   *   An associative array with keys 'file', 'line' and 'function'.
   */
  public static function getLastCaller(array &$backtrace) {
    // Errors that occur inside PHP internal functions do not generate
    // information about file and line. Ignore black listed functions.
    while (($backtrace && !isset($backtrace[0]['line'])) ||
      (isset($backtrace[1]['function']) && in_array($backtrace[1]['function'], static::$blacklistFunctions))) {
      array_shift($backtrace);
    }

    // The first trace is the call itself.
    // It gives us the line and the file of the last call.
    $call = $backtrace[0];

    // The second call gives us the function where the call originated.
    if (isset($backtrace[1])) {
      if (isset($backtrace[1]['class'])) {
        $call['function'] = $backtrace[1]['class'] . $backtrace[1]['type'] . $backtrace[1]['function'] . '()';
      }
      else {
        $call['function'] = $backtrace[1]['function'] . '()';
      }
    }
    else {
      $call['function'] = 'main()';
    }

    return $call;
  }


  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    $severity = $this->convertLevelToSeverity($level);

    if ($this->watchdogAvailable) {
      \watchdog($this->channel, (string) $message, $context, $this->convertLevelToSeverity($level));
      return;
    }

    // As a last fallback, log to syslog().
    $this->syslog($severity, $message, $context);
  }

  /**
   * Logs an exception.
   *
   * @param \Exception $exception
   *   The exception that was thrown.
   * @param string $message
   *   Optional. A message to use. By default it provides a template that
   *   identifies where the exception occurred.
   * @param array $context
   *   Optional. An array of parameters that will be used to replace tokens in
   *   $message.
   * @param string $level
   *   Optional. The severity level. By default this is an error.
   */
  public function logException(\Exception $exception, $message = NULL, $context = array(), $level = self::ERROR) {
    if (empty($message)) {
      $message = '%type: @message in %function (line %line of %file).';
    }
    $context += static::decodeException($exception);
    $this->log($level, $message, $context);
  }

  /**
   * Logs a message to syslog().
   *
   * @param string|int $level
   *   The level to log.
   * @param string $message
   *   The message to format.
   * @param array $context
   *   An array of variables used to replace in tokens in $message.
   */
  public function syslog($level, $message, array $context = array()) {
    $message = '[%channel] - ' . (string) $message;
    $context['%channel'] = $this->channel;
    if ($context) {
      foreach ($context as $key => $value) {
        switch ($key[0]) {
          case '@':
            $args[$key] = $this->core->checkPlain($value);
            break;
          case '%':
          default:
            $args[$key] = $this->core->checkPlain($value);
            break;
          case '!':
        }
      }
    }
    $severity = is_numeric($level) ? (int) $level : $this->convertLevelToSeverity($level);
    \syslog($severity, strtr($message, $context));
  }

  /**
   * Converts the normal worded levels into integers.
   *
   * @param string $level
   *   The level.
   *
   * @return int
   *   The integer representation of $level.
   */
  public function convertLevelToSeverity($level = self::NOTICE) {
    static $levels;
    if ($levels === NULL) {
      $levels = array(
        static::EMERGENCY => static::RFC_EMERGENCY,
        static::ALERT => static::RFC_ALERT,
        static::CRITICAL => static::RFC_CRITICAL,
        static::ERROR => static::RFC_ERROR,
        static::WARNING => static::RFC_WARNING,
        static::NOTICE => defined('WATCHDOG_NOTICE') ? WATCHDOG_NOTICE : LOG_NOTICE,
        static::INFO => defined('WATCHDOG_INFO') ? WATCHDOG_INFO : LOG_INFO,
        static::DEBUG => defined('WATCHDOG_DEBUG') ? WATCHDOG_DEBUG : LOG_DEBUG,
      );
    }
    return isset($levels[$level]) ? (int) $levels[$level] : (int) $level;
  }

  /**
   * {@inheritdoc}
   */
  public function emergency($message, array $context = array()) {
    $this->log(static::EMERGENCY, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function alert($message, array $context = array()) {
    $this->log(static::ALERT, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function critical($message, array $context = array()) {
    $this->log(static::CRITICAL, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function error($message, array $context = array()) {
    $this->log(static::ERROR, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function warning($message, array $context = array()) {
    $this->log(static::WARNING, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function notice($message, array $context = array()) {
    $this->log(static::NOTICE, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function info($message, array $context = array()) {
    $this->log(static::INFO, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function debug($message, array $context = array()) {
    $this->log(static::DEBUG, $message, $context);
  }

}
