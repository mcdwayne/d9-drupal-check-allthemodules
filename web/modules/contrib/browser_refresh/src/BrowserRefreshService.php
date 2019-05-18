<?php

/**
 * @file
 * Contains \Drupal\browser_refresh\BrowserRefreshServiceStatus.
 */

namespace Drupal\browser_refresh;

/**
 * Class BrowserRefreshService.
 *
 * @package Drupal\browser_refresh
 */
class BrowserRefreshService implements BrowserRefreshServiceInterface {

  /* @var \Symfony\Component\Console\Output\OutputInterface $output */
  private $output;

  /**
   * @inheritDoc
   */
  public function setOutput($output) {
    $this->output = $output;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function isActive($display = FALSE) {
    $active = (bool) $this->getPid();
    if ($display) {
      /* @var \Drupal\Core\StringTranslation\TranslatableMarkup $msg */
      if ($active) {
        $msg = t('Browser Refresh is running (pid=:pid).', array(':pid' => $this->getPid()));
      }
      else {
        $msg = t('Browser Refresh is not running.');
      }
      $this->displayMessage($msg->render(), ($active ? 'status' : 'error'));
    }
    return $active;
  }

  /**
   * @inheritDoc
   */
  public function getPid() {
    $config = \Drupal::configFactory()->getEditable('browser_refresh.settings');
    $pid = $config->get('pid');
    if ($pid) {
      $result = $this->exec('ps --cols=999 -lFp ' . $pid, 1);
      if (strpos($result, 'browser-refresh')) {
        return $pid;
      }
      $config->set('pid', 0)->save(TRUE);
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function start() {
    // Check if browser-refresh is already running
    if ($this->isActive()) {
      $this->displayMessage('Browser Refresh already started.', 'warning');
      return;
    }

    // Install or update packages
    $this->exec('npm update');

    // Check if browser-refresh is installed
    $executable = DRUPAL_ROOT . '/' . drupal_get_path('module', 'browser_refresh') . '/node_modules/.bin/browser-refresh';
    if (!file_exists($executable)) {
      $executable = $this->exec('which browser-refresh');
      if (empty($executable)) {
        $this->displayMessage(t('Main application browser-refresh is not available!'), 'error');
        $this->displayMessage(t('Try calling this: npm install -g browser-refresh'), 'status');
        return;
      }
    }

    // Check if browser-refresh-client is installed
    if ($this->exec('node br.js check') != 'OK!') {
      $this->displayMessage(t('Required node modules are not installed!'), 'error');
      $this->displayMessage(t('Try calling this: npm install -g browser-refresh-client'), 'status');
      return;
    }

    // Write configuration for the new process.
    $config_filename = DRUPAL_ROOT . '/' . drupal_get_path('module', 'browser_refresh') . '/.browser-refresh';
    $temp_filename = drupal_realpath(drupal_tempnam('temporary://', 'browser-refresh-'));
    $params = array(
      'watch' => array(DRUPAL_ROOT),
      'urlFileName' => $temp_filename,
    );
    file_put_contents($config_filename, json_encode($params));

    // Start the process.
    $pid = (int) $this->exec('nohup ' . $executable . ' > /dev/null 2>&1 & echo $!');
    if ($pid && file_exists($temp_filename)) {
      $url = '';
      while (empty($url)) {
        sleep(1);
        $url = file_get_contents($temp_filename);
      }
      \Drupal::configFactory()->getEditable('browser_refresh.settings')
        ->set('url', $url)
        ->set('pid', $pid)
        ->save(TRUE);
    }
    unlink($config_filename);
    unlink($temp_filename);

    $this->isActive(TRUE);
    $this->displayMessage('You should refresh your browser to see the updated status.');
  }

  /**
   * @return mixed
   */
  public function stop() {
    if (!$this->isActive()) {
      $this->displayMessage('Browser Refresh is not running.', 'error');
      return;
    }

    $pid = $this->getPid();
    \Drupal::configFactory()->getEditable('browser_refresh.settings')
      ->set('url', '')
      ->set('pid', 0)
      ->save(TRUE);
    $this->exec('kill ' . $pid);
    $this->displayMessage('Browser Refresh stopped.');
    $this->displayMessage('You should refresh your browser to see the updated status.');
  }

  /**
   * @inheritDoc
   */
  public function restart() {
    $this->stop();
    $this->start();
  }

  /**
   * Callback to execute a command line command from the module's directory
   * and to return the given line of output.
   *
   * @param $command
   * @return string
   */
  private function exec($command, $line = 0) {
    chdir(DRUPAL_ROOT . '/' . drupal_get_path('module', 'browser_refresh'));

    $op = array();
    exec($command, $op);
    return empty($op[$line]) ? '' : $op[$line];
  }

  /**
   * Callback to output a message. If called by Drush a drupal_set_message() is
   * invoked, otherwise we write to the OutputInterface from DrupalConsole.
   *
   * @param $msg
   * @param string $type
   */
  private function displayMessage($msg, $type = 'status') {
    if (empty($this->output)) {
      drupal_set_message($msg, $type);
    }
    else {
      $this->output->writeln($msg);
    }
  }

}
