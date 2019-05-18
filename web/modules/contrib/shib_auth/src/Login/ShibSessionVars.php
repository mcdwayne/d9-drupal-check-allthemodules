<?php

namespace Drupal\shib_auth\Login;

/**
 *
 */
class ShibSessionVars {

  /**
   * @var string
   */
  private $session_id;

  /**
   * @var string
   */
  private $targeted_id;

  /**
   * @var string
   */
  private $email;

  /**
   * @var string
   */
  private $idp;

  /**
   * @var string
   */
  private $entitlement;

  /**
   * @var string
   */
  private $affiliation;

  /**
   * ShibSessionVars constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   */
  public function __construct($config) {
    $this->session_id = self::fixModRewriteIssues('Shib-Session-ID');
    $this->targeted_id = self::fixModRewriteIssues($config->get('server_variable_username'));
    $this->email = self::fixModRewriteIssues($config->get('server_variable_email'));
    $this->idp = self::fixModRewriteIssues('Shib-Identity-Provider');
    $this->entitlement = self::fixModRewriteIssues('entitlement');
    $this->affiliation = self::fixModRewriteIssues('affiliation');
  }

  /**
   * @return string
   */
  public function getSessionId() {
    return $this->session_id;
  }

  /**
   * @return string
   */
  public function getTargetedId() {
    return $this->targeted_id;
  }

  /**
   * @return string
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * @return string
   */
  public function getIdp() {
    return $this->idp;
  }

  /**
   * @return string
   */
  public function getEntitlement() {
    return $this->entitlement;
  }

  /**
   * @return string
   */
  public function getAffiliation() {
    return $this->affiliation;
  }

  /**
   * Get environment variables that may have been modified by mod_rewrite.
   *
   * @param $var
   *
   * @return string or null
   */
  private static function fixModRewriteIssues($var) {

    if (!$var) {
      return NULL;
    }
    // foo-bar.
    if (array_key_exists($var, $_SERVER)) {
      return $_SERVER[$var];
    }

    // FOO-BAR.
    $var = strtoupper($var);
    if (array_key_exists($var, $_SERVER)) {
      return $_SERVER[$var];
    }

    // REDIRECT_foo_bar.
    $var = "REDIRECT_" . str_replace('-', '_', $var);
    if (array_key_exists($var, $_SERVER)) {
      return $_SERVER[$var];
    }

    // HTTP_FOO_BAR.
    $var = strtoupper($var);
    $var = preg_replace('/^REDIRECT/', 'HTTP', $var);
    if (array_key_exists($var, $_SERVER)) {
      return $_SERVER[$var];
    }

    return NULL;
  }

}
