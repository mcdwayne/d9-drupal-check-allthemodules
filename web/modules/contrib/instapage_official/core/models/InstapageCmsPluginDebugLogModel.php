<?php

/**
 * Class responsible for storing the data in debug log.
 */
class InstapageCmsPluginDebugLogModel {

  /**
   * @var object Class instance.
   */
  private static $debugLogModel = null;

  /**
   * Gets the class instance.
   *
   * @return object Class instance.
   */
  public static function getInstance() {
     if (self::$debugLogModel === null) {
      self::$debugLogModel = new InstapageCmsPluginDebugLogModel();
    }

    return self::$debugLogModel;
  }

  /**
   * Checks if Diagnostic mode is on.
   *
   * @return bool True if Diagnostic mode is on.
   */
  public function isDiagnosticMode() {
    return InstapageCmsPluginHelper::getOption('diagnostics', false);
  }

  /**
   * Wtites an entry in the debug log.
   *
   * @param string $value Message to be written in the log.
   * @param string $name Additional name for the written value. Default: ''.
   * @param bool $addCaller Do you want to include the stack trace to an antry? Default: true.
   */
  public function write($value, $name = '', $addCaller = true) {
    try {
      if (is_array($value) || is_object($value)) {
        $value = print_r($value, true);
      }

      $caller = '';

      if ($addCaller) {
        $trace = debug_backtrace();
        $traceLength = 3;
        $callerArr = array();

        for ($i = 1; $i <= $traceLength; ++$i) {
          $caller = isset($trace[$i]) ? $trace[$i] : null;
          $callerFunction = isset($caller['function']) ? $caller['function'] : null;

          if ($callerFunction == 'writeLog' || $callerFunction == 'writeDiagnostics') {
            $traceLength = 4;

            continue;
          }

          $callerClass = isset($caller['class']) ? $caller['class'] . ' :: ' : null;

          if ($caller === null) {
            break;
          }

          $callerArr[] = $callerClass . $callerFunction;
        }
      }

      $caller = implode("\r\n", $callerArr);
      $db = InstapageCmsPluginDBModel::getInstance();
      $sql = 'INSERT INTO ' . $db->debugTable . ' VALUES(NULL, %s, %s, %s, %s)';
      $db->query($sql, date('Y-m-d H:i:s'), $value, $caller, $name);
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  /**
   * Clears the debug log.
   */
  public function clear() {
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'DELETE FROM ' . $db->debugTable;
    $db->query($sql);
  }

  /**
   * Gets the entries from debug log.
   *
   * @return array List of entries.
   */
  public function read() {
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'SELECT * FROM ' . $db->debugTable;
    $results = $db->getResults($sql);

    return $results;
  }

  /**
   * Gets the HTML with debug log. Template for th og is in /templates/log.php file.
   *
   * @return string Log in HTML format.
   */
  public function getLogHTML() {
    if (InstapageCmsPluginConnector::currentUserCanManage() && $this->isDiagnosticMode()) {
      try {
        $pluginsHtml = InstapageCmsPluginConnector::getSelectedConnector()->getPluginsDebugHTML();
        $optionsHtml = InstapageCmsPluginConnector::getSelectedConnector()->getOptionsDebugHTML();
        $phpinfoHtml = $this->getPhpInfoHTML();
        $rows = $this->read();
        $view = InstapageCmsPluginViewModel::getInstance();
        $view->init(INSTAPAGE_PLUGIN_PATH . '/templates/log.php');
        $view->rows = $rows;
        $view->currentDate = date("Ymd_His");
        $view->pluginsHtml = $pluginsHtml;
        $view->optionsHtml = $optionsHtml;
        $view->phpinfoHtml = $phpinfoHtml;
        $html = $view->fetch();

        return $html;

      } catch (Exception $e) {
        throw $e;
      }
    } else {
      throw new Exception(__('Instapage log can be downloaded only in diagnostic mode.'));
    }
  }

  /**
   * Gets phpinfo and formats it.
   *
   * @return string Info about PHP in HTML format.
   */
  private function getPhpInfoHTML() {
    ob_start();
    phpinfo(INFO_GENERAL | INFO_CREDITS | INFO_CONFIGURATION | INFO_MODULES | INFO_ENVIRONMENT | INFO_VARIABLES);
    $contents = ob_get_contents();
    ob_end_clean();

    $pattern = '/<style.*?style>/s';
    $contents = preg_replace($pattern, '', $contents);
    $contents = '<div class="phpinfo">' . $contents . '</div>';

    return $contents;
  }
}
