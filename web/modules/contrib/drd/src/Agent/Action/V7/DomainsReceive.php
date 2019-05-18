<?php

namespace Drupal\drd\Agent\Action\V7;

/**
 * Provides a 'DomainsReceive' code.
 */
class DomainsReceive extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $domains = array();

    foreach ($this->readSites() as $uri => $shortname) {
      $file = DRUPAL_ROOT . '/sites/' . $shortname . '/settings.php';
      if (!file_exists($file)) {
        continue;
      }
      if (isset($domains[$shortname])) {
        $domains[$shortname]['aliase'][] = $uri;
      }
      else {
        $domains[$shortname] = array(
          'uri' => $uri,
          'aliase' => array(),
        );
      }
    }

    return $domains;
  }

  /**
   * Determines all available sites/domains in the current Drupal installation.
   *
   * @return array
   *   An array with key/value pairs where key is the domain name and value the shortname of a directory in
   *   DRUPAL_ROOT/sites/ for where to find the settings.php file for that domain.
   */
  private function readSites() {
    $sites = array();
    if (file_exists(DRUPAL_ROOT . '/sites/sites.php')) {
      try {
        include DRUPAL_ROOT . '/sites/sites.php';
      }
      catch (\Exception $e) {
        // Ignore.
      }
    }
    if (empty($sites)) {
      foreach (scandir(DRUPAL_ROOT . '/sites') as $shortname) {
        if (is_dir(DRUPAL_ROOT . '/sites/' . $shortname) &&
          !in_array($shortname, array('.', '..', 'all'))) {
          $file = DRUPAL_ROOT . '/sites/' . $shortname . '/settings.php';
          if (file_exists($file)) {
            list($base_url,) = $this->readSettings($shortname, $file);
            if (empty($base_url)) {
              $this->watchdog('Reading Sites - Failed as url is empty: @shortname', array('@shortname' => $shortname), WATCHDOG_ERROR);
              continue;
            }
            $pos = strpos($base_url, '://');
            if ($pos > 0) {
              $base_url = substr($base_url, $pos + 3);
            }
            $sites[$base_url] = $shortname;
          }
        }
      }
    }
    $this->watchdog('Reading Sites - Found @n entries: <pre>@list</pre>', array('@n' => count($sites), '@list' => print_r($sites, TRUE)));
    return $sites;
  }

  /**
   * Safely read the settings.php file and return the relevant variables.
   *
   * @param $shortname
   * @param $file
   * @return array
   */
  private function readSettings($shortname, $file) {
    $databases = array();
    try {
      $php = php_strip_whitespace($file);
      $php = str_replace('<?php', '', $php);
      $php = str_replace('<?', '', $php);
      $php = str_replace('?>', '', $php);
      $php = str_replace('ini_set', '@ini_set', $php);
      $php = str_replace('@@ini_set', '@ini_set', $php);
      eval($php);
    }
    catch (\Exception $e) {
      // Ignore it
      $this->watchdog('Read Settings - Exception occured:<pre>@exception</pre>', array('@exception' => print_r($e, TRUE)), WATCHDOG_ERROR);
      return array('', '');
    }
    if (empty($base_url)) {
      if ($shortname == 'default') {
        $base_url = $GLOBALS['base_url'];
      }
      else {
        $base_url = $shortname;
      }
    }
    return array($base_url, $databases);
  }

}
