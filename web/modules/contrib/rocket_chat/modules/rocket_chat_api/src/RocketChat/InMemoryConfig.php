<?php

namespace Drupal\rocket_chat_api\RocketChat {

  /*
   * Created by PhpStorm.
   * User: lawri
   * Date: 05/09/17
   * Time: 12:07
   */

  /**
   * Class InMemoryConfig keeps the config in memory.
   *
   * @package RocketChat
   */
  class InMemoryConfig implements RocketChatConfigInterface {

    /**
     * Server URL.
     *
     * @var string
     *   Store's  the Server URL.
     */
    private $url;

    /**
     * User name.
     *
     * @var string
     *   Store's  the user name.
     */
    private $user;

    /**
     * User password.
     *
     * @var string
     *   Store's  the password.
     */
    private $password;

    /**
     * User ID.
     *
     * @var string
     *   Store's  the user ID.
     */
    private $uid;

    /**
     * User Access Token.
     *
     * @var string
     *   Store's the user token.
     */
    private $utk;

    /**
     * Debugging flag.
     *
     * @var bool
     *   Are we Debugging?
     */
    private $debug;

    /**
     * A Config type that holds stored values.
     *
     * @var RocketChatConfigInterface
     *   Hold a reference to a Interface that actually does store data.
     */
    private $superConfig;

    /**
     * InMemoryConfig constructor.
     *
     * @param \Drupal\rocket_chat_api\RocketChat\RocketChatConfigInterface $storedConfig
     *   Stored Config Object (READ ONLY).
     * @param string $user
     *   User name.
     * @param string $password
     *   User Password.
     */
    public function __construct(RocketChatConfigInterface &$storedConfig, &$user, &$password) {
      $this->superConfig = $storedConfig;

      $this->url = $storedConfig->getElement('server', "http://localhost:3000");
      $this->user = $user;
      $this->password = $password;
      $this->uid = $storedConfig->getElement('rocket_chat_uid');
      $this->utk = $storedConfig->getElement('rocket_chat_uit');
    }

    /**
     * {@inheritdoc}
     */
    public function getElement($elementName, $default = NULL) {
      switch ($elementName) {
        case 'rocket_chat_url':
        case 'server':
          return $this->url;

        case 'rocket_chat_uid':
          return $this->uid;

        case 'rocket_chat_uit':
          return $this->utk;

        default:
          throw new \InvalidArgumentException("[$elementName] not found", 144);
      }
    }

    /**
     * {@inheritdoc}
     */
    public function setElement($elementName, $newValue) {
      switch ($elementName) {
        case 'rocket_chat_url':
        case 'server':
          return $this->url = $newValue;

        case 'rocket_chat_uid':
          return $this->uid = $newValue;

        case 'rocket_chat_uit':
          return $this->utk = $newValue;

        default:
          throw new \InvalidArgumentException("[$elementName] not found", 144);
      }
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug() {
      return $this->debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getJsonDecoder() {
      return $this->superConfig->getJsonDecoder();
    }

    /**
     * {@inheritdoc}
     */
    public function notify($message, $type) {
      return $this->superConfig->notify($message, $type);
    }

  }

}
