<?php

namespace Drupal\myjdownloader;

/**
 * My.jdownloader.org API.
 */
class MyJDAPI {


  private $apiUrl = "https://api.jdownloader.org";
  private $ridCounter;
  private $appkey = "MyJDAPI_php_drupal";
  private $apiVer = 1;
  private $devices;
  private $loginSecret;
  private $deviceSecret;
  private $sessiontoken;
  private $regaintoken;
  private $serverEncryptionToken;
  private $deviceEncryptionToken;
  private $serverDomain = "server";
  private $deviceDomain = "device";
  private $deviceName = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct() {

    $config = MyJdHelper::getConfig();
    $this->ridCounter = time();

    $res = $this->connect($config->get("email"), $config->get("password"));
    if ($res === FALSE) {
      return FALSE;
    }

    $this->setDeviceName($config->get("device"));
  }

  /**
   * Set device name.
   */
  public function setDeviceName($deviceName) {
    if (!is_null($deviceName) && is_string($deviceName)) {
      $this->deviceName = $deviceName;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get device name.
   */
  public function getDeviceName() {
    return $this->deviceName;
  }

  /**
   * Connect to api.jdownloader.org.
   *
   * @param string $email
   *   Email.
   * @param string $password
   *   Password.
   *
   * @return bool
   *   result
   */
  public function connect($email, $password) {
    $this->loginSecret = $this->createSecret($email, $password, $this->serverDomain);
    $this->deviceSecret = $this->createSecret($email, $password, $this->deviceDomain);
    $query = "/my/connect?email=" . urlencode($email) . "&appkey=" . urlencode($this->appkey);
    $res = $this->callServer($query, $this->loginSecret);
    if ($res === FALSE) {
      return FALSE;
    }
    // Set values.
    $content_json = json_decode($res, TRUE);
    $this->sessiontoken = $content_json["sessiontoken"];
    $this->regaintoken = $content_json["regaintoken"];
    $this->serverEncryptionToken = $this->updateEncryptionToken($this->loginSecret, $this->sessiontoken);
    $this->deviceEncryptionToken = $this->updateEncryptionToken($this->deviceSecret, $this->sessiontoken);
    return TRUE;
  }

  /**
   * Reconnect to api.jdownloader.org.
   */
  public function reconnect() {
    $query = "/my/reconnect?appkey=" . urlencode($this->appkey) . "&sessiontoken=" . urlencode($this->sessiontoken) . "&regaintoken=" . urlencode($this->regaintoken);
    $res = $this->callServer($query, $this->serverEncryptionToken);
    if ($res === FALSE) {
      return FALSE;
    }
    // Set values.
    $content_json = json_decode($res, TRUE);
    $this->sessiontoken = $content_json["sessiontoken"];
    $this->regaintoken = $content_json["regaintoken"];
    $this->serverEncryptionToken = $this->updateEncryptionToken($this->serverEncryptionToken, $this->sessiontoken);
    $this->deviceEncryptionToken = $this->updateEncryptionToken($this->deviceSecret, $this->sessiontoken);
    return TRUE;
  }

  /**
   * Disconnect from api.jdownloader.org.
   */
  public function disconnect() {
    $query = "/my/disconnect?sessiontoken=" . urlencode($this->sessiontoken);
    $res = $this->callServer($query, $this->serverEncryptionToken);
    if ($res === FALSE) {
      return FALSE;
    }
    // Cleanup.
    $this->sessiontoken = "";
    $this->regaintoken = "";
    $this->serverEncryptionToken = "";
    $this->deviceEncryptionToken = "";
    return TRUE;
  }

  /**
   * Enumerate Devices connected to my.jdownloader.org.
   */
  public function enumerateDevices() {
    $query = "/my/listdevices?sessiontoken=" . urlencode($this->sessiontoken);
    $res = $this->callServer($query, $this->serverEncryptionToken);
    if ($res === FALSE) {
      return FALSE;
    }
    // Setup devices, call getDirectConnectionInfos to setup devices.
    $content_array = json_decode($res, TRUE);
    $this->devices = $content_array["list"];
    $res = $this->getDirectConnectionInfos();
    if ($res === FALSE) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Call action "/device/getDirectConnectionInfos" for each devices.
   */
  public function getDirectConnectionInfos() {
    foreach ($this->devices as $i => &$ivalue) {
      $res = $this->callAction($this->devices[$i]["name"], "/device/getDirectConnectionInfos");
      if ($res === FALSE) {
        return FALSE;
      }
      // If success - setup devices with infos.
      $content_array = json_decode($res, TRUE);
      $this->devices[$i]["infos"] = $content_array["data"]["infos"];
    }
    return TRUE;
  }

  /**
   * Send links to device using action /linkgrabberv2/addLinks.
   *
   * @param string|array $links
   *   A link or links list.
   * @param array|null $settings
   *   Additional settings.
   *
   * @return bool
   *   Result
   *
   * @throws \Exception
   *   If link is empty or bad type.
   */
  public function addLinks($links, array $settings = []) {

    $settings_default = [
      // (boolean|null).
      "assignJobID" => NULL,
      // (boolean|null).
      "autoExtract" => NULL,
      // (boolean|null).
      "autostart" => TRUE,
      // (String[]).
      "dataURLs" => NULL,
      // (boolean|null).
      "deepDecrypt" => NULL,
      // (String).
      "destinationFolder" => NULL,
      // (String).
      "downloadPassword" => NULL,
      // (String).
      "extractPassword" => NULL,
      // (boolean|null).
      "overwritePackagizerRules" => NULL,
      // (String).
      "packageName" => NULL,
      // (Priority).
      "priority" => 'DEFAULT',
      // (String).
      "sourceUrl" => NULL,
    ];

    $settings = array_merge($settings_default, $settings);
    if (!is_array($this->devices)) {
      $this->enumerateDevices();
    }

    if (is_array($links)) {
      $links = implode("\\r\\n", $links);
    }
    elseif (is_string($links) && !empty($links)) {
      // OK.
    }
    else {
      throw new \Exception("Bad link type, must be a string or an array");
    }
    $settings['links'] = $links;

    // Call action.
    $res = $this->callAction("/linkgrabberv2/addLinks", $settings);
    if ($res === FALSE) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Retrieve links.
   */
  public function queryLinks($params = []) {
    $params_default = [
      "bytesTotal" => TRUE,
      "comment" => TRUE,
      "status" => TRUE,
      "enabled" => TRUE,
      "maxResults" => -1,
      "startAt" => 0,
      "packageUUIDs" => NULL,
      "host" => TRUE,
      "url" => TRUE,
      "bytesLoaded" => TRUE,
      "speed" => TRUE,
      "eta" => TRUE,
      "finished" => TRUE,
      "priority" => TRUE,
      "running" => TRUE,
      "skipped" => TRUE,
      "extractionStatus" => TRUE,
    ];

    $params = array_merge($params_default, $params);

    $res = $this->callAction("/downloadsV2/queryLinks", $params);
    return $res;
  }

  /**
   * Make a call to my.jdownloader.org.
   *
   * @return bool|mixed|string
   *   Result from server or false.
   */
  private function callServer($query, $key, $params = FALSE) {
    if ($params != "") {
      if ($key != "") {
        $params = $this->encrypt($params, $key);
      }
      $rid = $this->ridCounter;
    }
    else {
      $rid = $this->getUniqueRid();
    }
    if (strpos($query, "?") !== FALSE) {
      $query = $query . "&";
    }
    else {
      $query = $query . "?";
    }
    $query = $query . "rid=" . $rid;
    $signature = $this->sign($key, $query);
    $query = $query . "&signature=" . $signature;
    $url = $this->apiUrl . $query;
    if ($params != "") {
      $res = $this->postQuery($url, $params, $key);
    }
    else {
      $res = $this->postQuery($url, "", $key);
    }
    if ($res === FALSE) {
      return FALSE;
    }
    $content_json = json_decode($res, TRUE);
    if ($content_json["rid"] != $this->ridCounter) {
      return FALSE;
    }
    return $res;
  }

  /**
   * Make a call to API function on my.jdownloader.org.
   *
   * @return bool|mixed|string
   *   result from server or false.
   */
  public function callAction($action, $params = FALSE) {
    if (!is_array($this->devices)) {
      $this->enumerateDevices();
    }

    if (!is_array($this->devices) || (count($this->devices) == 0)) {
      return FALSE;
    }

    foreach ($this->devices as $i => &$ivalue) {
      if ($this->devices[$i]["name"] == $this->getDeviceName()) {
        $device_id = $this->devices[$i]["id"];
      }
    }
    if (!isset($device_id)) {
      return FALSE;
    }
    $query = "/t_" . urlencode($this->sessiontoken) . "_" . urlencode($device_id) . $action;
    if ($params != "") {
      if (is_array($params)) {
        $params = str_replace('"', '\"', substr(json_encode($params), 1, -1));
      }
      $json_data = '{"url":"' . $action . '","params":["{' . $params . '}"],"rid":' . $this->getUniqueRid() . ',"apiVer":' . $this->apiVer . '}';
    }
    else {
      $json_data = '{"url":"' . $action . '","rid":' . $this->getUniqueRid() . ',"apiVer":' . $this->apiVer . '}';
    }
    $json_data = $this->encrypt($json_data, $this->deviceEncryptionToken);
    $url = $this->apiUrl . $query;
    $res = $this->postQuery($url, $json_data, $this->deviceEncryptionToken);
    if ($res === FALSE) {
      return FALSE;
    }
    $content_json = json_decode($res, TRUE);
    if ($content_json["rid"] != $this->ridCounter) {
      return FALSE;
    }
    return $res;
  }

  /**
   * Genarate new unique rid.
   */
  public function getUniqueRid() {
    $this->ridCounter++;
    return $this->ridCounter;
  }

  /**
   * Return current ridCounter.
   */
  public function getRid() {
    return $this->ridCounter;
  }

  /**
   * CreateSecret.
   */
  private function createSecret($username, $password, $domain) {
    return hash("sha256", strtolower($username) . $password . strtolower($domain), TRUE);
  }

  /**
   * Sign.
   */
  private function sign($key, $data) {
    return hash_hmac("sha256", $data, $key);
  }

  /**
   * Decrypt.
   */
  private function decrypt($data, $iv_key) {
    $iv = substr($iv_key, 0, strlen($iv_key) / 2);
    $key = substr($iv_key, strlen($iv_key) / 2);
    return openssl_decrypt(base64_decode($data), "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv);
  }

  /**
   * Encrypt.
   */
  private function encrypt($data, $iv_key) {
    $iv = substr($iv_key, 0, strlen($iv_key) / 2);
    $key = substr($iv_key, strlen($iv_key) / 2);
    return base64_encode(openssl_encrypt($data, "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv));
  }

  /**
   * Update Encryption Token.
   */
  private function updateEncryptionToken($oldToken, $updateToken) {
    return hash("sha256", $oldToken . pack("H*", $updateToken), TRUE);
  }

  /**
   * Make Get or Post Request to $url ( $postfields).
   *
   * @return bool|mixed|string
   *   Send Payload data if $postfields not null
   *   return plain response or decrypted response if $iv_key not null
   */
  private function postQuery($url, $postfields = FALSE, $iv_key = FALSE) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if ($postfields) {
      $headers[] = "Content-Type: application/aesjson-jd; charset=utf-8";
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
      curl_setopt($ch, CURLOPT_HEADER, TRUE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = [];
    $response["text"] = curl_exec($ch);
    $response["info"] = curl_getinfo($ch);
    $response["code"] = $response["info"]["http_code"];
    if ($response["code"] != 200) {
      return FALSE;
    }
    if ($postfields) {
      $response["body"] = substr($response["text"], $response["info"]["header_size"]);
    }
    else {
      $response["body"] = $response["text"];
    }
    if ($iv_key) {
      $response["body"] = $this->decrypt($response["body"], $iv_key);
    }
    curl_close($ch);
    return $response["body"];

  }

}
