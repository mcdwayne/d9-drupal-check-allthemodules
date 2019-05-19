<?php
/**
 * @file
 * Contains \Drupal\wisski_apus\StackingLogger.
 */

namespace Drupal\wisski_apus;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
use Drupal\Core\Cache\CacheBackendInterface;

class StackingLogger implements LoggerInterface {
  
  /**
   * A logger that this logger optionally forwards to.
   * @var \Psr\Log\LoggerInterface
   */
  protected $backingLogger = NULL;
  
  
  /**
   * The stack of log messages.
   *
   * @var array[]
   */
  protected $stack = array();


  /**
   * The cache key used for caching or NULL if caching is disabled.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache_key = NULL;
  

  /**
   * The cache bin that the stack is written to or NULL if
   * caching is disabled.
   *
   * @var string
   */
  protected $cache_bin = NULL;
  

  /**
   * The interval after which the cache entry expires.
   *
   * @var integer
   */
  protected $cache_exp;
  

  /**
   * Constructs a new Logger. The Logger itself will not write the logging 
   * messages but pile them in a stack/array, which can then be retrieved.
   *
   * If a cache key is given, the logger further caches the whole stack whenever a
   * log message is coming in. By this you can restore the log stack if execution
   * got broken unexpectedly.
   *
   * @param backingLogger a LoggerInterface instance to which the log entries 
   *  will be passed through for further processing
   * @param cache_key the key used for caching the log entries.
   *  NULL disables caching
   * @param cache_bin the cache bin to write to. If empty, the system default
   *  bin is used.
   * @param cache_exp the time interval after which the cache will expire. 
   *  Defaults to one day. An empty value makes the entry permanent.
   */
  public function __construct(LoggerInterface $backingLogger = NULL, $cache_key = NULL, $cache_bin = NULL, $cache_exp = 86400) {
    if (!empty($backingLogger)) $this->backingLogger = $backingLogger;
    if (!empty($cache_key)) {
      $this->cache_bin = empty($cache_bin) ? \Drupal::cache() : \Drupal::cache($cache_bin);
      $this->cache_key = $cache_key;
      $this->cache_exp = empty($cache_exp) ? CacheBackendInterface::CACHE_PERMANENT : $cache_exp;
    }
  }

  
  /**
   * Return the stack of log messages.
   *
   * @param levels The returned stack is filtered by the given log levels.
   *  If the array is empty, all messages are returned.
   *
   * @return array
   *  The array of messages in the order as they came in.
   *  Each item is an array with following keys:
   *  - level: the log level
   *  - message: the message
   *  - context: the context
   *  - timestamp: the timestamp when the message came in
   */
  public function getStack($levels = array()) {
    
    if (empty($levels)) return $this->stack;

    $stack = array();
    foreach ($this->stack as $item) {
      if (in_array($item['level'], $levels)) {
        $stack[] = $item;
      }
    }
    return $stack;

  }

  
  /**
   * Sets the internal stack of log messages. The existing stack is discarded.
   *
   * @param stack the array containing the stack
   *  @see getStack() for the array structure
   */
  public function setStack($stack) {
    $this->stack = $stack;
  }

  
  /**
   * Sets the internal stack to the stack found in the cache.
   *
   * The function uses the cache configuration passed in the constructor to
   * retrieve the stack.
   *
   * @see setStack()
   * @see __construct()
   */
  public function restoreFromCache() {
    if (empty($this->cache_key)) throw new \LogicException("no cache configured on logger");

    $c = $this->cache_bin->get($this->cache_key);
#file_put_contents("/local/logs/dev8.log", "blakk" . serialize($c));

    if ($c->data) {
      $this->setStack($c->data);
    }

  }

  
  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {

    if (
      $level == LogLevel::DEBUG ||
      $level == LogLevel::INFO ||
      $level == LogLevel::NOTICE ||
      $level == LogLevel::WARNING ||
      $level == LogLevel::ERROR ||
      $level == LogLevel::CRITICAL ||
      $level == LogLevel::ALERT ||
      $level == LogLevel::EMERGENCY
    ) {

      $this->stack[] = array(
        'level' => $level,
        'message' => $message,
        'context' => $context,
        'timestamp' => time(),
      );
      if (!empty($this->cache_key)) {
        $this->cache_bin->set($this->cache_key, $this->stack, time() + $this->cache_exp);
      }
      if ($backingLogger != NULL) $backingLogger->log($level, $message, $context);
      
    } else {
      throw new InvalidArgumentException($level);
    }

  }
  
  /**
   * {@inheritdoc}
   */
  public function emergency($message, array $context = array()) {
    $this->log(LogLevel::EMERGENCY, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function alert($message, array $context = array()) {
    $this->log(LogLevel::ALERT, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function critical($message, array $context = array()) {
    $this->log(LogLevel::CRITICAL, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function error($message, array $context = array()) {
    $this->log(LogLevel::ERROR, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function warning($message, array $context = array()) {
    $this->log(LogLevel::WARNING, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function notice($message, array $context = array()) {
    $this->log(LogLevel::NOTICE, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function info($message, array $context = array()) {
    $this->log(LogLevel::INFO, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function debug($message, array $context = array()) {
    $this->log(LogLevel::DEBUG, $message, $context);
  }


}

