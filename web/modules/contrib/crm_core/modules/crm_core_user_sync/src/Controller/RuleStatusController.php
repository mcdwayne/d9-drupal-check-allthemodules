<?php

namespace Drupal\crm_core_user_sync\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for CRM Core User Synchronization routes.
 */
class RuleStatusController extends ControllerBase {

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs the controller object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(Request $request, ConfigFactoryInterface $configFactory) {
    $this->request = $request;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('config.factory')
    );
  }

  /**
   * Enable rule.
   */
  public function enable() {
    $rule_key = $this->request->get('rule_key');
    $rules = $this->configFactory->getEditable('crm_core_user_sync.settings')->get('rules');
    $rules[$rule_key]['enabled'] = TRUE;
    $this->configFactory->getEditable('crm_core_user_sync.settings')->set('rules', $rules)->save();

    return $this->redirect('crm_core_user_sync.config');
  }

  /**
   * Disable rule.
   */
  public function disable() {
    $rule_key = $this->request->get('rule_key');
    $rules = $this->configFactory->getEditable('crm_core_user_sync.settings')->get('rules');
    $rules[$rule_key]['enabled'] = FALSE;
    $this->configFactory->getEditable('crm_core_user_sync.settings')->set('rules', $rules)->save();

    return $this->redirect('crm_core_user_sync.config');
  }

}
