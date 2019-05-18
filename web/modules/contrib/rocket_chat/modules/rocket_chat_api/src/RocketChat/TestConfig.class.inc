<?php

namespace Drupal\rocket_chat_api\RocketChat {

  /*
   * Created by PhpStorm.
   * User: lawri
   * Date: 6/25/17
   * Time: 3:41 PM
   */

  /**
   * Class Test7Config For Testing.
   *
   * @package Drupal\rocket_chat_api\RocketChat
   */
  class Test7Config extends Drupal8Config {

    /**
     * User Id.
     *
     * @var string
     *    Id.
     */
    private $userId;

    /**
     * User Token.
     *
     * @var string
     *    Secret.
     */
    private $userToken;

    /**
     * Rocket chat Server.
     *
     * @var string
     *   Server.
     */
    private $server = "http://127.0.0.1:3000";

    /**
     * {@inheritdoc}
     */
    public function getElement($elementName, $default = NULL) {
      switch ($elementName) {
        case "rocket_chat_uid":
          return $this->userId;

        case "rocket_chat_uit":
          return $this->userToken;

        case "rocket_chat_url":
          return $this->server;

        default:
          return $default;
      }
    }

    /**
     * {@inheritdoc}
     */
    public function setElement($elementName, $newValue) {
      switch ($elementName) {
        case "rocket_chat_uid": $this->userId = $newValue;
          break;

        case "rocket_chat_uit": $this->userToken = $newValue;
          break;

        case "rocket_chat_url": $this->server = $newValue;
          break;

        default: fwrite(STDERR, "TEST SET [$elementName=>$newValue]\n");
      }
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug() {
      return FALSE;
    }

    /**
     * {@inheritdoc}
     */
    public function notify($message, $type) {
      return fwrite(STDERR, "Message [$type=>$message]\n");
    }

  }

}

namespace {

  /**
   * {@inheritdoc}
   */
  function drupal_json_decode($json) {
    return \GuzzleHttp\json_decode($json, TRUE);
  }

}
