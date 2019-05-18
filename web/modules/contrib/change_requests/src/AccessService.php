<?php

namespace Drupal\change_requests;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AccessService.
 */
class AccessService {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $moduleConfig;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Symfony\Component\HttpFoundation\Request definition.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Symfony\Component\HttpFoundation\Request definition.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentNodeTypeEnabled;

  /**
   * Constructs a new AccessService object.
   */
  public function __construct(ConfigFactory $config_factory, AccountProxy $current_user, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->moduleConfig = $this->configFactory->get('change_requests.config');
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->currentRequest = $this->requestStack->getCurrentRequest();
  }

  /**
   * Get node_type of current request.
   *
   * @return string
   *   Returns the node_type (page, article) or 'none'.
   */
  protected function getNodeType() {
    $node = $this->currentRequest->get('node');
    return ($node instanceof NodeInterface) ? $node->bundle() : '<none>';
  }

  /**
   * If current node type is enabled for CR.
   *
   * @return bool
   *   Returns if node_type is enabled.
   */
  protected function getCurrentNodeTypeEnabled() {
    if ($this->currentNodeTypeEnabled === NULL) {
      $cur_node_type = $this->getNodeType();
      $mod_conf_node_types = $this->moduleConfig->get('node_types');
      $this->currentNodeTypeEnabled = (
        isset($mod_conf_node_types[$cur_node_type])
        && $mod_conf_node_types[$cur_node_type] == $cur_node_type
      );
    }
    return $this->currentNodeTypeEnabled;
  }

  /**
   * Allow users to bypass CR and save nodes directly.
   *
   * @return bool
   *   TRUE if checkbox is to be displayed.
   */
  public function bypassChangeRequest() {
    if ( !$this->getCurrentNodeTypeEnabled() ) {
      return TRUE;
    }

    if ($this->currentUser->hasPermission('bypass patch creation')) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Check if "create new revision" is to be disabled / can not be changed by user.
   *
   * @return bool
   *   If checkbox "create new revision" is to be disabled.
   */
  public function disableCreateNewRevision() {
    if ($this->getCurrentNodeTypeEnabled()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Check finally if checkbox "Create patch from changes" to be displayed.
   *
   * @return bool
   *   TRUE if checkbox is to be displayed.
   */
  public function displayCheckboxCreatePatch() {
    // Check user has permission.
    if (!$this->currentUser->hasPermission('add patch entities')) {
      return FALSE;
    }

    // Check module config if node_type enabled.
    if ( !$this->getCurrentNodeTypeEnabled() ) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * If checkbox "Create change request from changes" is to be disabled.
   *
   * @return bool
   *   TRUE if checkbox is disabled.
   */
  public function disableCheckboxCreatePatch() {
    // Check user has permission.
    if ($this->currentUser->hasPermission('bypass patch creation')) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Return default value for checkbox "Create change request from changes".
   *
   * @return bool
   *   TRUE if checkbox is enabled.
   */
  public function defaultValueCheckboxCreatePatch() {
    // Check user has permission.
    if (!$this->currentUser->hasPermission('bypass patch creation')) {
      return TRUE;
    }

    if($this->moduleConfig->get('enable_checkbox_node_form')) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Check finally if log message shall be a required value.
   *
   * @return bool
   *   Restult.
   */
  public function isLogMessageRequired() {
    if (!$this->moduleConfig->get('log_message_required')) {
      return FALSE;
    }

    if ( !$this->getCurrentNodeTypeEnabled() ) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Allow starting of patch creation.
   *
   * @return bool
   *   TRUE if patch creation can be started.
   */
  public function startPatchCreateProcess() {
    // Skip patch creation if it is not a node.
    if (!$this->currentRequest->get('_route') == 'entity.node.edit_form') {
      return FALSE;
    }

    // Node type is disabled.
    if (!$this->getCurrentNodeTypeEnabled()) {
      return FALSE;
    }

    // User has no permisson to create patches.
    if (!$this->currentUser->hasPermission('add patch entities')) {
      return FALSE;
    }

    // Listen to checkbox value.
    if ($this->currentRequest->get('create_patch') === "0") {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks all conditions for overwrite the title of Log message field.
   *
   * @return string|false
   *   Returns title if overriding is allowed.
   */
  public function allowOverrideLogMessageTitle() {

    // Check module config if node_type enabled.
    if ( !$this->getCurrentNodeTypeEnabled() ) {
      return FALSE;
    }

    if ($log_message_title = $this->moduleConfig->get('log_message_title')) {
      return (string) $log_message_title;
    }

    return FALSE;
  }

}
