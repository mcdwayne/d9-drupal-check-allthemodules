<?php

namespace Drupal\dblog_persistent\Logger;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\dblog\Logger\DbLog;
use Drupal\dblog_persistent\DbLogPersistentStorageInterface;
use Drupal\dblog_persistent\Entity\ChannelInterface;

/**
 * Class DbLogPersistent
 *
 * @package Drupal\dblog_persistent\Logger
 */
class DbLogPersistent extends DbLog {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $manager;

  /**
   * @var \Drupal\dblog_persistent\DbLogPersistentStorageInterface
   */
  protected $storage;

  /**
   * @var \Drupal\dblog_persistent\Entity\ChannelInterface[]
   */
  private $channels;

  public function __construct(Connection $connection,
                              LogMessageParserInterface $parser,
                              EntityTypeManagerInterface $manager,
                              DbLogPersistentStorageInterface $storage) {
    parent::__construct($connection, $parser);
    $this->manager = $manager;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   *
   * This function must be copied in order to specify a different table.
   *
   * @throws \PDOException
   */
  public function log($level, $message, array $context = []): void {
    // Remove any backtraces since they may contain an unserializable variable.
    unset($context['backtrace']);
    $args = $this->parser->parseMessagePlaceholders($message, $context);
    // Insert variables, but use untranslated string and strip markup.
    $output = \strip_tags(new FormattableMarkup($message, $args));
    foreach ($this->getChannels($level, $output, $context) as $channel) {
      $this->writeLog($channel->id(), $level, $message, $args, $context);
    }
  }

  /**
   * List the channels that a message fits into.
   *
   * @param $level
   * @param string $message
   * @param array $context
   *
   * @return \Drupal\dblog_persistent\Entity\ChannelInterface[]
   */
  protected function getChannels($level,
                                 string $message,
                                 array $context): array {
    if ($this->channels === NULL) {
      $this->channels = [];
      try {
        $this->channels = $this->manager
          ->getStorage('dblog_persistent_channel')
          ->loadMultiple();
      }
      // Prevent errors during module installation.
      catch (PluginNotFoundException $error) {}
      catch (InvalidPluginDefinitionException $error) {}
    }

    return array_filter(
      $this->channels,
      function (ChannelInterface $channel) use ($level, $message, $context) {
        return $channel->matches($level, $context['channel'], $message);
      }
    );
  }

  /**
   * Write the log into a specific channel.
   *
   * @param string $channel
   * @param $level
   * @param string $message
   * @param array $args
   * @param array $context
   */
  protected function writeLog(string $channel, $level, string $message, array $args, array $context): void {
    $this->storage->writeLog($channel, [
      'uid'       => $context['uid'],
      'type'      => Unicode::substr($context['channel'], 0, 64),
      'message'   => $message,
      'variables' => serialize($args),
      'severity'  => $level,
      'link'      => $context['link'],
      'location'  => $context['request_uri'],
      'referer'   => $context['referer'],
      'hostname'  => Unicode::substr($context['ip'], 0, 128),
      'timestamp' => $context['timestamp'],
    ]);
  }

}
