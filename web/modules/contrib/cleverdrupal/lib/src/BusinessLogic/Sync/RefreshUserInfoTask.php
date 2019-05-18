<?php

namespace CleverReach\BusinessLogic\Sync;

/**
 *
 */
class RefreshUserInfoTask extends BaseSyncTask {
  /**
   * @var string
   */
  private $accessToken;

  /**
   *
   */
  public function __construct($accessToken) {
    $this->accessToken = $accessToken;
  }

  /**
   * @inheritdoc
   */
  public function serialize() {
    return serialize($this->accessToken);
  }

  /**
   * @inheritdoc
   */
  public function unserialize($serialized) {
    $this->accessToken = unserialize($serialized);
  }

  /**
   * Runs task logic.
   *
   * @throws \Exception
   */
  public function execute() {
    $userInfo = $this->getUserInfo();
    if (empty($userInfo)) {
      $this->getConfigService()->setAccessToken(NULL);
      $this->getConfigService()->setUserInfo(NULL);
    }
    else {
      $this->getConfigService()->setAccessToken($this->accessToken);
      $this->getConfigService()->setUserInfo($userInfo);
    }

    $this->reportProgress(100);
  }

  /**
   * Get user info from CleverReach.
   *
   * @return array
   *
   * @throws \Exception
   */
  public function getUserInfo() {
    try {
      return $this->getProxy()->getUserInfo($this->accessToken);
    }
    catch (\Exception $ex) {
      // Catch any exception to clear access token and user info and rethrow exception.
      $this->getConfigService()->setAccessToken(NULL);
      $this->getConfigService()->setUserInfo(NULL);
      throw $ex;
    }
  }

}
