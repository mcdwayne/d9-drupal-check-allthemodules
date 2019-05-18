<?php

namespace Drupal\drush_task;

use Drupal\Core\Config\ConfigFactory;

/**
 * @file
 * Drush Task object definition.
 *
 * Metadata describing this service can be found in drush_task.services.yml.
 *
 * The service can be instantiated with:
 *
 *     $task = \Drupal::service('drush_task.drush_task');
 */

/**
 * Class representing a 'task'.
 *
 * Includes setup config and result storage.
 */
class DrushTask {

  /**
   * Drush site alias to target.
   *
   * @var string
   */
  public $siteAlias = '';

  /**
   * The basic drush command.
   *
   * @var string
   */
  public $command = 'status';


  /**
   * Arguments to the drush command.
   *
   * @var string
   */
  public $arguments = '';

  /**
   * Define the drush output-format parameter.
   *
   * There are times when JSON would be preferable,
   * but for now we expect and parse normal text responses.
   * It can be overridden though.
   *
   * @var string
   *
   * @see `drush topic docs-output-formats`
   */
  public $format = '';

  /**
   * The full commandline that was executed, after being wrapped in handlers.
   *
   * @var string
   */
  public $commandRaw = '';

  /**
   * Response code from running the command.
   *
   * 0 is success, anything else is an error code.
   *
   * @var int
   */
  public $resultCode = '';

  /**
   * Full unparsed text of the output.
   *
   * Expected to be JSON, but if there was an error, may be just text.
   *
   * @var string
   */
  public $resultRaw = '';
  public $resultText = '';

  /**
   * The data structure (unpacked json) result from a successful command run.
   */
  public $result = '';

  /**
   * System configs, managed through config manager.
   *
   * @var array
   */
  private $config = array(
    'drush_path' => '',
    'drush_php' => '',
    'drush_rc' => '',
  );

  /**
   * Construct a task definition.
   *
   * @inheritdoc
   */
  public function __construct(ConfigFactory $config_factory) {
    // This seems tedious, but it would not auto-flatten to an array for me.
    $config = $config_factory->get('drush_task.config');
    $this->config['drush_path'] = (string) $config->get('drush_path');
    $this->config['drush_php'] = (string) $config->get('drush_php');
    $this->config['drush_rc'] = (string) $config->get('drush_rc');
  }

  /**
   * Change a config value.
   *
   * This is not remembered - used for validation checks.
   *
   * @param $key
   * @param $val
   */
  function setConfig($key, $val) {
    $this->config[$key] = $val;
  }

  /**
   * Evaluate the command on the cli.
   *
   * Return the result as a decoded JSON struct if successful.
   * If the return is === NULL
   * (check the type if there is a chance of a valid empty return)
   * then you should investigate the $task->resultCode and
   * $task->resultRaw yourself.
   */
  public function run() {

    if (empty($this->command)) {
      throw new Exception('No drush command defined');
    }
    $logger = \Drupal::logger('drush_task');

    // Review very carefully for security back doors.
    // All administrator-user-supplied strings should be run through
    // escapeshellarg() everywhere.
    //
    // I break the $exec_[prefix,command,suffix] into slices here
    // just so the reports are not cluttered with backend irrelevancies.
    $exec_prefix = $exec_command = $exec_suffix = '';

    // The PHP runtime configuration parameter can confuse the system
    // about which version of PHP it's using.
    // Unsetting it seems to help.
    $exec_prefix .= "PHPRC= ";

    // If you need to use a specific PHP, the DRUSH_PHP enc var should help.
    if (!empty($this->config['drush_php'])) {
      $exec_prefix .= 'DRUSH_PHP=' . escapeshellarg($this->config['drush_php']) . ' ';
    }

    $exec_prefix .= escapeshellarg($this->config['drush_path']) . ' ';

    if (!empty($this->config['drush_rc'])) {
      $exec_prefix .= ' --config=' . escapeshellarg($this->config['drush_rc']) . ' ';
    }

    if (!empty($this->format)) {
      // Backend is a whole different story. Wonderfully verbose,
      // but needs tricky un-parsing. drush->drush specific really.
      // $exec .= ' --backend ';
      // We just act as a naiive client asking for data via json.
      $exec_prefix .= ' --format=' . escapeshellarg($this->format) . ' ';
    }
    if (!empty($this->siteAlias)) {
      $exec_prefix .= escapeshellarg($this->siteAlias) . ' ';
    }

    $exec_command .= escapeshellarg($this->command) . ' ';

    if (!empty($this->arguments)) {
      // For safer escaping, split all the input string into bits,
      // (while respecting quoted values)
      // so the parts can be quoted and escaped individually.
      // str_getcsv() is a handy quote-safe string splitter.
      $safely_split = str_getcsv(trim($this->arguments), ' ');
      $safely_escaped = array_map('escapeshellarg', $safely_split);
      $exec_command .= implode(' ', $safely_escaped) . ' ';
    }

    // TODO - continue to apply  all sorts of security paranoia here.
    $this->commandRaw = "$exec_prefix $exec_command $exec_suffix";
    $strings = array(
      '%exec_command' => $exec_command,
      '%commandRaw' => $this->commandRaw,
    );

    try {
      $logger->info("Calling `%exec_command`;", $strings);

      exec($this->commandRaw . ' 2>&1', $result_array, $resultCode);

      $this->resultCode = $resultCode;
      $strings['@resultCode'] = ($resultCode) ? $resultCode . '[fail]' : '0:[success]';
      $this->resultRaw = implode("\n", $result_array);
      $strings['@result'] = $this->resultRaw;

      $logger->info("Called %exec_command; Result was <b>@resultCode</b> <pre>%commandRaw</pre><pre>@result</pre>", $strings);

      // $resultCode of 0 is good.
      if (!$resultCode) {
        if ($this->format == 'json') {
          $this->result = json_decode($this->resultRaw);
        }
        else {
          $this->result = $this->resultRaw;
          $this->resultText = trim($this->resultRaw);
        }
        return $this->result;
      }
    }
    catch (Exception $e) {
      $strings['!error'] = $e->getMessage();
      $logger->error("Called %exec_command; Result was <b>!resultCode</b> <pre>%command_raw</pre>Error was !error <pre>!result</pre>", $strings);
    }

    return NULL;
  }

  /**
   * Return the available drush version , if possible.
   *
   * @return string|null
   *   Version on success, NULL on fail.
   */
  public function version() {
    $this->command = '--version';
    $result = $this->run();

    // A result of 0 is success!
    if ($this->resultCode || empty($result)) {
      $strings = array(
        '%commandRaw' => $this->commandRaw,
        '%resultRaw' => $this->resultRaw,
      );
      $message = '<strong><pre>%commandRaw</pre></strong><pre>%resultRaw</pre>';
      drupal_set_message(t($message, $strings), 'error');
    }
    return $result;
  }

}
