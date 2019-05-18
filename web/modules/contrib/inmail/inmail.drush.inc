<?php
/**
 * @file
 * Drush commands for the Inmail module.
 */

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Entity\HandlerConfig;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * @addtogroup processing
 * @{
 * The primary purpose of Drush integration for Inmail is to pipe incoming mail
 * to the message processor. You may want to configure your MTA to redirect
 * messages to a script that runs `drush inmail-process`. You can use the
 * provided postfix-filter.sh bash script or create your own.
 *
 * Another use for the Drush commands is manual testing during development. You
 * can evaluate the effects of various types of messages by feeding the email
 * source files to `drush inmail-process`, just as if the message had just been
 * fetched and forwarded by an MTA.
 */

/**
 * Implements hook_drush_commands().
 */
function inmail_drush_command() {
  $items = array();

  // @todo Drush command option to specify analyzers/handlers https://www.drupal.org/node/2379927
  $items['inmail-process'] = array(
    'description' => 'Process a mail message provided through STDIN, or one or more mail messages by filename.',
    'arguments' => array(
      'deliverer' => 'The ID of a Drush Deliverer plugin configuration.',
      'file [file2 ...]' => 'One or more file names to parse as messages. If no filenames are given, a message is read from STDIN.',
    ),
    'examples' => array(
      'cat 1.eml | drush inmail-process drush_deliverer' => 'Process a message through STDIN',
      'drush inmail-process drush_deliverer 1.eml 2.eml 3.eml' => 'Process each of the messages contained by the three files',
    ),
    'drupal dependencies' => array('inmail'),
  );

  $items['inmail-plugins'] = array(
    'description' => 'Tell which plugins are being used for message delivery, analysis and handling.',
    'drupal dependencies' => array('inmail'),
  );

  return $items;
}

/**
 * Drush command implementation for processing a mail message.
 */
function drush_inmail_process() {
  if (!inmail_drush_assert_uri()) {
    return;
  }

  /** @var \Drupal\inmail\MessageProcessorInterface $processor */
  $processor = \Drupal::service('inmail.processor');

  $args = func_get_args();

  // Identify deliverer.
  $deliverer_config_id = array_shift($args);
  if (empty($deliverer_config_id)) {
    drush_set_error(dt('You need to specify a Deliverer.'));
    return;
  }
  $deliverer = DelivererConfig::load($deliverer_config_id);
  if (empty($deliverer)) {
    drush_set_error(dt('Deliverer "@deliverer_id" not found', ['@deliverer_id' => $deliverer_config_id]));
    return;
  }
  if (!$deliverer->isAvailable()) {
    drush_set_error(dt('Deliverer "@deliverer_id" is not available', ['@deliverer_id' => $deliverer_config_id]));
    return;
  }

  if (count($args) > 0) {
    $raws = array();
    foreach ($args as $filename) {
      $raws[] = file_get_contents($filename[0] == '/' ? $filename : __DIR__ . '/' . $filename);
    }
    $results = $processor->processMultiple($raws, $deliverer);
    if (drush_get_option('verbose')) {
      $processed = 0;
      foreach ($results as $key => $result) {
        if ($messages = inmail_get_log_message($result)) {
          drush_log(dt('@key: @message', ['@key' => $key, '@message' => strip_tags(implode("\n", $messages))]));
          $processed++;
        }
      }
      drush_log(dt('@count messages successfully processed.', ['@count' => $processed]));
    }
  }
  else {
    $raw = _inmail_get_piped_input();
    /** @var ProcessorResultInterface $result */
    $result = $processor->process('unique_key', $raw, $deliverer);
    if (drush_get_option('verbose')) {
      if ($result) {
        drush_log(dt('Message successfully processed.'));
        $messages = inmail_get_log_message($result);
        drush_log(dt(strip_tags(implode("\n", $messages))));
      }
      else {
        drush_set_error(dt('Message was not processed.'));
      }
    }
  }
}

/**
 * Output the class names of current analyzers and handlers.
 */
function drush_inmail_plugins() {
  $deliverer_configs = DelivererConfig::loadMultiple();
  foreach ($deliverer_configs as $deliverer_config) {
    echo "Deliverer: {$deliverer_config->label()} ({$deliverer_config->getPluginId()})\n";
  }

  /** @var \Drupal\inmail\Entity\DelivererConfig[] $deliverer_configs */
  $deliverer_configs = DelivererConfig::loadMultiple();
  foreach ($deliverer_configs as $deliverer_config) {
    echo "Deliverer: {$deliverer_config->label()} ({$deliverer_config->getPluginId()})\n";
  }

  /** @var \Drupal\inmail\Entity\AnalyzerConfig[] $analyzer_configs */
  $analyzer_configs = AnalyzerConfig::loadMultiple();
  foreach ($analyzer_configs as $analyzer_config) {
    echo "Analyzer: {$analyzer_config->label()} ({$analyzer_config->getPluginId()})\n";
  }

  $handler_configs = HandlerConfig::loadMultiple();
  foreach ($handler_configs as $handler_config) {
    // Handlers have fallback support. Creating plugin instances enables us to
    // detect if a config references a missing plugin.
    /** @var \Drupal\inmail\Plugin\inmail\Handler\HandlerInterface $handler */
    $handler = \Drupal::service('plugin.manager.inmail.handler')->createInstance($handler_config->getPluginId());
    echo "Handler: {$handler_config->label()} ({$handler->getPluginId()})\n";
  }
}

/**
 * Checks that the site URI is set, and sets an error message otherwise.
 *
 * @return bool
 *   TRUE if the URI is set, otherwise FALSE.
 */
function inmail_drush_assert_uri() {
  $host = \Drupal::request()->getHost();
  if ($host == 'default') {
    drush_set_error(dt('Site URI not specified. Please use the --uri option.'));
    return FALSE;
  }
  return TRUE;
}

/**
 * Check for piped input.
 *
 * @return string
 *   Data piped to Drush.
 */
function _inmail_get_piped_input() {
  static $data = NULL;
  if (is_null($data)) {
    $data = '';
    while ($input = fread(STDIN, 1024)) {
      $data .= $input;
    }
  }
  return $data;
}
