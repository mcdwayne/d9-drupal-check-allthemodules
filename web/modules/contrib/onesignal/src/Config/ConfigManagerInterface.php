<?php

namespace Drupal\onesignal\Config;

/**
 * Interface for manage Onesignal module configuration.
 *
 * @package Drupal\onesignal\Config
 */
interface ConfigManagerInterface {
  
  /**
   * Provides Onesignal App id.
   *
   * @return string
   */
  public function getAppId();
  
  /**
   * Provides original Onesignal App id.
   *
   * @return string
   */
  public function getOriginalAppId();
  
  /**
   * Provides Onesignal Safari web id.
   *
   * @return string
   */
  public function getSafariWebId();
  
  /**
   * Provides original Onesignal Safari web id.
   *
   * @return string
   */
  public function getOriginalSafariWebId();
  
  /**
   * Provides Onesignal REST Api key.
   *
   * @return string
   */
  public function getRestApiKey();
  
  /**
   * Provides auto register value.
   *
   * @return integer|string
   */
  public function getAutoRegister();
  
  /**
   * Provides notify button visibility value.
   *
   * @return integer|string
   */
  public function getNotifyButton();
  
  /**
   * Provides value of development setting 'Localhost secure origin'.
   *
   * @return integer|string
   */
  public function getLocalhostSecure();
  
  /**
   * Provides text of the invitation to signup.
   *
   * @return string
   */
  public function getActionMessage();
  
  /**
   * Provides text of the Accept button.
   *
   * @return string
   */
  public function getAcceptButtonText();
  
  /**
   * Provides text of the Cancel button.
   *
   * @return string
   */
  public function getCancelButtonText();
  
  /**
   * Provides text of the welcome notification title.
   *
   * @return string
   */
  public function getWelcomeTitle();
  
  /**
   * Provides text of the welcome notification message.
   *
   * @return string
   */
  public function getWelcomeMessage();
  
}
