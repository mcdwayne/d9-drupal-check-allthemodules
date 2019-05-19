<?php

namespace Drupal\sceditor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\FileTransfer\SSH;

/**
 * Class SCEditorController.
 */
class SCEditorController extends ControllerBase {

  /**
   * Sceditorpage.
   *
   * @return string
   *   Return Hello string.
   */
  public function scEditorPage() {
    //\Drupal::service('page_cache_kill_switch')->trigger(); // @codingStandardsIgnoreLine
    $this->checkFtpStatus();
    $root = DRUPAL_ROOT;
    $path = NULL;
    $links = [];
    if (isset($_GET['file'])) {
      if ($_GET['file'] == 'clear') {
        shell_exec('git checkout .');
      }
      $path = $_GET['file'];
      if (!$this->isInDirectory($_GET['file'], $root)) {
        $path = NULL;
      }
      else {
        $path = '/' . $path;
      }
    }
    $url = $root . $path;
    if (is_file($url)) {
      if (!empty($_POST['file_content'])) {
        $config = \Drupal::config('sceditor.ftpaccess'); // @codingStandardsIgnoreLine
        $sftp_server = $config->get('host');
        $sftp_user = $config->get('username');
        $sftp_pass = $config->get('password');
        $sftp_port = $config->get('port');
        $sftp_private_key = $config->get('ssh_private_key_path');
        $sftp_public_key = $config->get('ssh_public_key_path');
        $sftp_ssh_secret = $config->get('ssh_secret');
        $use_ssh_key = $config->get('use_ssh_key');
        if ($use_ssh_key) {
          if ($ssh->SSH_KEY_AUTH) {
            $ssh = new SSH(DRUPAL_ROOT, $sftp_user, $sftp_pass, $sftp_server, $sftp_port, $sftp_public_key, $sftp_private_key, $sftp_ssh_secret);
            $ssh->checkConnection();
            $ssh->connect();
            $ssh->sftpConnect();
          }
        }
        else {
          if ($ssh->SSH_PASSWORD_AUTH) {
            $ssh = new SSH(DRUPAL_ROOT, $sftp_user, $sftp_pass, $sftp_server, $sftp_port);
            $ssh->checkConnection();
            $ssh->connect();
            $ssh->sftpConnect();
          }
        }
        $stream = @fopen("ssh2.sftp://$ssh->sftp$url", 'w');
        $data_to_send = $_POST['file_content'];
        if (@fwrite($stream, $data_to_send) === false) {
          drupal_set_message("Could not send data from file: $url.");
          @fclose($stream);
        }
      }

      $data = @file_get_contents($url);
      $extension = explode('.', $path);
      $build = [
        '#theme' => 'sceditor',
        '#ext' => end($extension),
        '#link' => urlencode($path),
        '#return' => urlencode(substr(dirname($url), strlen($root) + 1)),
        '#data' => $data,
        '#attached' => [
          'library' => [
            'sceditor/sceditor.styles',
            'sceditor/sceditor.customjs',
          ],
        ],
      ];
      $build['#attached']['drupalSettings']['sceditor']['ext'] = end($extension);
      return $build;
    }

    if ($path) {
      $links[] = [
        'size' => '-',
        'time' => '-',
        'type' => '<i class="fa fa-level-up fa-fw"></i>',
        'link' => urlencode(substr(dirname($url), strlen($root) + 1)),
        'name' => '..',
      ];
    }
    foreach (glob($root . $path . '/*') as $file) {
      $file = realpath($file);
      $link = substr($file, strlen($root) + 1);
      $links[] = [
        'size' => (is_dir($file)) ? '-' : round(filesize($file) / 1024, 2),
        'time' => date("F d Y H:i:s", filemtime($file)),
        'type' => (is_dir($file)) ? '<i class="fa fa-folder fa-fw"></i>' : '<i class="fa fa-file-text fa-fw"></i>',
        'link' => urlencode($link),
        'name' => basename($file),
      ];
    }
    $build = [
      '#theme' => 'sceditor_directories',
      '#links' => $links,
      '#attached' => [
        'library' => [
          'sceditor/sceditor.styles',
        ],
      ],
    ];
    $build['#attached']['drupalSettings']['sceditor']['ext'] = 'dir';
    return $build;

  }

  /**
   * Check if in directory.
   *
   * @param string $file
   *   The filename.
   * @param string $directory
   *   The root directory.
   * @param bool $recursive
   *   Whether recursive or not.
   *
   * @return bool
   *   TRUE is in directory, FALSE if not.
   */
  public function isInDirectory($file, $directory, $recursive = TRUE) {
    $directory = realpath($directory);
    $parent = realpath($file);
    while ($parent) {
      if ($directory == $parent) {
        return TRUE;
      }
      if ($parent == dirname($parent) || !$recursive) {
        break;
      }
      $parent = dirname($parent);
    }
    return FALSE;
  }

  /**
   * Check id the FTP Access is available.
   */
  public function checkFtpStatus() {
    $config = \Drupal::config('sceditor.ftpaccess'); // @codingStandardsIgnoreLine
    $sftp_server = $config->get('host');
    $sftp_user = $config->get('username');
    $sftp_pass = $config->get('password');
    $sftp_port = $config->get('port');
    $ssh = new SSH(DRUPAL_ROOT, $sftp_user, $sftp_pass, $sftp_server, $sftp_port);
    $ssh->checkConnection();
    if (!$ssh->SSH_PASSWORD_AUTH) {
      drupal_set_message($this->t('SFTP is not connected. If you want to edit using SFTP, please check your SFTP Credentials <a href="/admin/config/sceditor/ftpaccess" target="_blank">here</a>.'), 'warning');
    }
  }

}
