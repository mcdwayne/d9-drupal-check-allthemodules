<?php

/**
 * Class responsible for managing the landing pages.
 */
class InstapageCmsPluginServicesModel {

  /**
   * @var object Class instance.
   */
  private static $servicesModel = null;

  /**
   * Gets the class instance.
   *
   * @return object Class instance.
   */
  public static function getInstance() {
    if (self::$servicesModel === null) {
      self::$servicesModel = new InstapageCmsPluginServicesModel();
    }

    return self::$servicesModel;
  }

  /**
   * Checks if current request should be processed by a proxy service.
   *
   * @return boolean
   */
  public function isServicesRequest() {
    if (strpos($_SERVER['REQUEST_URI'], 'instapage-proxy-services') !== false) {
      InstapageCmsPluginHelper::writeDiagnostics($_SERVER['REQUEST_URI'], 'Proxy services URL');

      return true;
    }

    return false;
  }

  /**
   * Strips the slashes.
   *
   * @param string &$$value Value to strip slashes from.
   */
  public function stripSlashesGpc(&$value) {
    $value = stripslashes($value);
  }

  /**
   * Processes the proxy request.
   */
  public function processProxyServices() {
    $api = InstapageCmsPluginAPIModel::getInstance();
    $url = @InstapageCmsPluginHelper::getVar($_GET['url'], '');

    if (strpos($url, 'ajax/pageserver/email') === false) {
      throw new Exception('Unsupported endpoint: ' . $url);
    }

    $url = INSTAPAGE_PROXY_ENDPOINT . $url;

    array_walk_recursive($_POST, array($this, 'stripSlashesGpc'));

    if (isset($_POST) && !empty($_POST)) {
      $_POST['user_ip'] = $_SERVER['REMOTE_ADDR'];
    }

    $data = $_POST;
    $data['ajax'] = 1;
    $response = $api->remotePost($url, $data);

    if (isset($response['response']['code']) && $response['response']['code'] !== 200) {
      $this->disableCrossOriginProxy();
      $matches = array();
      $pattern = '/email\/(\d*)/';
      preg_match($pattern, $url, $matches);
    }

    InstapageCmsPluginHelper::writeDiagnostics($url, 'Proxy services URL');
    InstapageCmsPluginHelper::writeDiagnostics($data, 'Proxy data');
    InstapageCmsPluginHelper::writeDiagnostics($response, 'Proxy response');

    $status = @InstapageCmsPluginHelper::getVar($response->status);
    $responseCode = @InstapageCmsPluginHelper::getVar($response['response']['code'], 200);

    if ($status === 'ERROR') {
      $errorMessage = @InstapageCmsPluginHelper::getVar($response->message);

      if (!empty($errorMessage)) {
        throw new Exception($errorMessage);
      }
      else {
        throw new Exception('500 Internal Server Error');
      }
    }

    ob_start();
    ob_end_clean();
    header('Content-Type: text/json; charset=UTF-8');
    echo trim(@InstapageCmsPluginHelper::getVar($response['body'], ''));
    status_header($responseCode);

    exit;
  }

  /**
   * Disables the Cross Origin Proxy option in plugin's settings.
   */
  private function disableCrossOriginProxy() {
    $options = InstapageCmsPluginHelper::getOptions();
    $options->config->crossOrigin = 0;
    InstapageCmsPluginHelper::updateOptions($options);
  }
}
