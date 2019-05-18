<?php

namespace Drupal\google_plus_login\Event;

use Drupal\user\UserInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\EventDispatcher\Event;

class GooglePlusLoginEvent extends Event {

  const NAME = 'google_plus_login';

  /**
   * @var UserInterface
   */
  protected $account;

  /**
   * @var GoogleUser
   */
  protected $googleUser;

  /**
   * GooglePlusLoginEvent constructor.
   *
   * @param UserInterface $account
   * @param GoogleUser $googleUser
   */
  public function __construct(UserInterface $account, GoogleUser $googleUser) {
    $this->account = $account;
    $this->googleUser = $googleUser;
  }

  /**
   * @return UserInterface
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * @return GoogleUser
   */
  public function getGoogleUser() {
    return $this->googleUser;
  }

}
