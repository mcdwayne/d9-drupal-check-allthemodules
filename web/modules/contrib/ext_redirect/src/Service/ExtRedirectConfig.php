<?php
/**
 * Created by PhpStorm.
 * User: marek.kisiel
 * Date: 18/08/2017
 * Time: 11:47
 */

namespace Drupal\ext_redirect\Service;

class ExtRedirectConfig {

  /*
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  public function __construct(\Drupal\Core\Config\ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->getEditable('ext_redirect.settings');
  }

  public function setPrimaryHost($primaryHost) {
    $this->config->set('primary_host', $primaryHost);
  }

  public function getPrimaryHost() {
    return $this->config->get('primary_host');
  }

  public function setAllowedHostAliasesFromString($aliases) {
    $aliases = str_replace("\r", '', $aliases);
    $arrAliases = explode("\n", $aliases);
    $this->config->set('allowed_host_aliases', $arrAliases);
  }

  public function getAllowedHostAliases() {
    return $this->config->get('allowed_host_aliases');
  }

  public function getAllowedHostAliasesAsString() {
    $allowedHost = $this->config->get('allowed_host_aliases');

    if (!$allowedHost) {
      return '';
    }
    return implode("\n", $allowedHost);
  }

  public function save() {
    $this->config->save();
  }
}