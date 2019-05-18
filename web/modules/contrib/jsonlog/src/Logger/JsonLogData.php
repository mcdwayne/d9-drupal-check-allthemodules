<?php

namespace Drupal\jsonlog\Logger;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class JsonLogData {

  private $message;

  private $message_id;

  private $site_id;

  private $canonical;

  private $method;

  private $tags;

  private $type;

  private $subtype;

  private $severity;

  private $request_uri;

  private $referer;

  private $uid;

  private $username;

  private $client_ip;

  private $link;

  private $code;

  private $trunc;

  /**
   * JsonLogData constructor.
   *
   * @param string $site_id
   * @param string $canonical
   */
  function __construct($site_id, $canonical) {
    $this->trunc = '';
    $this->message_id = uniqid($site_id, TRUE);
    $this->site_id = $site_id;
    $this->canonical = $canonical;
    $this->type = 'drupal';
    $this->{'@version'} = 1;

    $this->setTimestamp();
  }

  /**
   * @return string json representation of this class's data
   */
  public function getJson() {
    return Json::encode(get_object_vars($this));
  }

  /**
   * @return array representation of this class's data
   */
  public function getData() {
    return get_object_vars($this);
  }

  /**
   * @param string|TranslatableMarkup $entry
   * @param int $truncate
   * @param array $variables
   */
  public function setMessage($entry, $truncate = FALSE, $variables = []) {
    if ($truncate) {
      // Kb to bytes.
      $truncate *= 1024;
      // Substract estimated max length of everything but message content.
      $truncate -= 768;
      // Message will get longer when JSON encoded, because of hex encoding of
      // <>&" chars.
      $truncate *= 7 / 8;
    }

    if (($entry)) {
      if ($entry instanceof TranslatableMarkup) {
        /** @var TranslatableMarkup $entry */
        $this->message = $entry->getUntranslatedString();
      } else {
        /** @var string message */
        $this->message = empty($variables) ? $entry : strtr($entry, $variables);
      }

      // Strip tags if message starts with < (Inspect logs in tag).
      if ($this->message{0} === '<') {
        $this->message = strip_tags($this->message);
      }

      // Escape null byte.
      $this->message = str_replace("\0", '_NUL_', $this->message);

      // Truncate message.
      // Deliberately multi-byte length.
      if ($truncate && ($le = strlen($this->message)) > $truncate) {
        // Truncate multi-byte safe until ASCII length is
        // equal to/less than max byte length.
        $this->message = Unicode::truncateBytes($this->message, (int) $truncate);
        $this->trunc = [
          $le,
          strlen($this->message),
        ];
      }
    }
  }

  /**
   * @param int $level
   */
  public function setSeverity($level) {
    $this->severity = RfcLogLevel::getLevels()[$level];
  }

  /**
   * @param string $channel
   */
  public function setSubType($channel) {
    $this->subtype = mb_substr($channel, 0, 64);

  }

  /**
   * @param string $realMethod
   */
  public function setMethod($realMethod) {
    $this->method = $realMethod;
  }

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  public function setAccount($account) {
    if(isset($account)) {
      $this->uid = $account->id();
      $this->username = $account->getAccountName();
    } else {
      $this->uid = 0;
      $this->username = '';
    }
  }

  /**
   * @param string $request_uri
   */
  public function setRequest_uri($request_uri) {
    $this->request_uri = $request_uri;
  }

  /**
   * @param string $referer
   */
  public function setReferer($referer) {
    $this->referer = $referer;
  }

  /**
   * @param string $ip
   */
  public function setClient_ip($ip) {
    $this->client_ip = mb_substr($ip, 0, 128);
  }

  /**
   * @param mixed $link
   */
  public function setLink($link) {
    // If link is an integer (or 'integer') it may be an event/error code;
    // the Inspect module exploits link for that purpose.
    if (!$link) {
      $this->link = NULL;
      $this->code = 0;
    }
    elseif (ctype_digit($link)) {
      $this->link = NULL;
      $this->code = (int) $link;
    }
    else {
      $this->link = strip_tags($link);
      $this->code = 0;
    }
  }

  /**
   * @param string $tags_server
   * @param string $tags_site
   */
  public function setTags($tags_server, $tags_site) {
    if ($tags_server) {
      $tags = $tags_server;
      if ($tags_site) {
        $tags .= ',' . $tags_site;
      }
    }
    else {
      $tags = $tags_site;
    }
    if ($tags) {
      $this->tags = explode(',', $tags);
    }
  }

  /**
   * Helper function to set timestamp in milliseconds
   */
  private function setTimestamp() {
    $millis = round(microtime(TRUE) * 1000);
    $seconds = (int) floor($millis / 1000);
    $millis -= $seconds * 1000;
    $millis = str_pad($millis, 3, '0', STR_PAD_LEFT);

    $this->{'@timestamp'} = substr(gmdate('c', $seconds), 0, 19) . '.' . $millis . 'Z';
  }

  /**
   * For testing purposes we also add individual getters
   * JsonLog uses $this->getData() and $this->getJson()
   */

  /**
   * @return string
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * @return string
   */
  public function getMessageId() {
    return $this->message_id;
  }

  /**
   * @return string
   */
  public function getSiteId() {
    return $this->site_id;
  }

  /**
   * @return string
   */
  public function getCanonical() {
    return $this->canonical;
  }

  /**
   * @return string
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * @return array
   */
  public function getTags() {
    return $this->tags;
  }

  /**
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @return string
   */
  public function getSubtype() {
    return $this->subtype;
  }

  /**
   * @return string
   */
  public function getSeverity() {
    return $this->severity;
  }

  /**
   * @return string
   */
  public function getRequestUri() {
    return $this->request_uri;
  }

  /**
   * @return string
   */
  public function getReferer() {
    return $this->referer;
  }

  /**
   * @return integer
   */
  public function getUid() {
    return $this->uid;
  }

  /**
   * @return string
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * @return string
   */
  public function getClientIp() {
    return $this->client_ip;
  }

  /**
   * @return string
   */
  public function getLink() {
    return $this->link;
  }

  /**
   * @return integer
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * @return string
   */
  public function getTrunc() {
    return $this->trunc;
  }


}