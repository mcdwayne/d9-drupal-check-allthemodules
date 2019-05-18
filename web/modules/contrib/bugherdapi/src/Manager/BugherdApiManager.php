<?php

namespace Drupal\bugherdapi\Manager;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;

/**
 * Class BugherdApiManager.
 *
 * @package Drupal\bugherdapi\Manager
 */
class BugherdApiManager {

  protected $currentUser;
  protected $adminContext;
  protected $currentRouteMatch;
  protected $config;

  /**
   * Constructor for BugherdApiManager.
   */
  public function __construct(AccountProxy $currentUser, AdminContext $adminContext, CurrentRouteMatch $currentRouteMatch, ConfigFactoryInterface $config) {
    $this->currentUser = $currentUser;
    $this->adminContext = $adminContext;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->config = $config->get('bugherdapi.settings');
  }

  /**
   * Checks if the current page needs bugherd error reporting.
   *
   * @return bool
   */
  public function pageApplies() {

    $project_key = $this->config->get('project_key', FALSE);

    // Check if the api key was set properly.
    if ($project_key === FALSE) {
      drupal_set_message('The Bugherd project API key is not set in the bugherd configuration form.', 'warning');
      return FALSE;
    }

    // Check if the user has access to bugherd.
    if (!$this->currentUser->hasPermission('access bugherd')) {
      return FALSE;
    }

    $disable_on_admin = $this->config->get('disable_on_admin', FALSE);

    // Check if bugherd should be loaded for admin pages.
    if ($this->adminContext->isAdminRoute($this->currentRouteMatch->getRouteObject()) && $disable_on_admin) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns JS settings.
   *
   * @return array
   */
  public function getJsSettings() {
    return [
      'api_key' => $this->config->get('project_key'),
    ];
  }

}
