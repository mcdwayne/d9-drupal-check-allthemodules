<?php

namespace Drupal\drd\Agent\Action\V6;

/**
 * Provides a 'Database' download file.
 */
class Database extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    global $db_url, $db_prefix;
    $databases = array(
      'default' => array(
        'default' => array(),
      ),
    );
    $urls = is_array($db_url) ? $db_url : array('default' => $db_url);
    $key = 'default';

    exec('mysqldump --version', $output, $ret);
    $mysql = ($ret == 0);
    if ($mysql) {
      foreach ($urls as $target => $url) {
        $config = parse_url($url);
        $config['user'] = urldecode($config['user']);
        $config['pass'] = isset($config['pass']) ? urldecode($config['pass']) : '';
        $config['host'] = urldecode($config['host']);
        $config['database'] = trim(urldecode($config['path']), '/');
        if (!isset($config['port'])) {
          $config['port'] = 3306;
        }
        $file = tempnam(file_directory_temp(), implode('-', array('drd', 'db', $target, $key, ''))) . '.sql';
        $credentialsfile = drupal_realpath(drupal_tempnam('temporary://', 'mysqldump'));

        $cmd = [
          'mysqldump',
          '--defaults-extra-file=' . $credentialsfile,
          $config['database'],
          '>' . $file,
        ];
        $credentials = [
          '[mysqldump]',
          'host = ' . $config['host'],
          'port = ' . $config['port'],
          'user = ' . $config['user'],
          'password = "' . $config['pass'] . '"',
        ];

        file_put_contents($credentialsfile, implode("\n", $credentials));
        chmod($credentialsfile, 0600);
        exec(implode(' ', $cmd), $output, $ret);
        unlink($credentialsfile);

        $databases[$key][$target] = $config;
        $databases[$key][$target]['url'] = $url;
        $databases[$key][$target]['file'] = $file;
      }
      $databases[$key]['default']['prefix'] = $db_prefix;
      return $databases;
    }
    return FALSE;
  }

}
