<?php

namespace Drupal\form_mode_user_roles_assign;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates Form Alter information.
 *
 * This class contains primarily bridged hooks for form.
 */
class FormAlter implements ContainerInjectionInterface {

  /**
   * The Regex pattern to contextualize process by route path.
   *
   * @var string
   */
  const REGISTER_PATH_CONTEXT_REGEX = '/(^.*?\/user\/register\/.*)|(^.*?\/admin\/people\/create\/.*)/';

  /**
   * The entity display repository.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * FormAlter constructor.
   *
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   */
  public function __construct(FormModeManagerInterface $form_mode_manager, RouteMatchInterface $route_match, ConfigFactoryInterface $config_factory) {
    $this->formModeManager = $form_mode_manager;
    $this->routeMatch = $route_match;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_mode.manager'),
      $container->get('current_route_match'),
      $container->get('config.factory')
    );
  }

  /**
   * Automatically assign user roles of register/create routes using FMM.
   *
   * This is an alter hook bridge.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   * @param string $formId
   *   The Form ID.
   *
   * @see hook_form_alter()
   */
  public function formAlter(array &$form, FormStateInterface $formState, $formId) {
    if (!$this->candidateToAlterForm()) {
      return;
    }

    /** @var \Symfony\Component\Routing\Route $routeObject */
    $routeObject = $this->routeMatch->getRouteObject();
    $operationName = $routeObject->getDefault('_entity_form');
    $dynamicFormId = str_replace('.', '_', $operationName) . '_form';

    if (!$this->isUserRegistrationRoute()) {
      return;
    }

    $form_mode_name = $this->formModeManager->getFormModeMachineName($operationName);
    $roles = $this->configFactory
      ->get('form_mode_user_roles_assign.settings')
      ->get("form_modes.user_{$form_mode_name}.assign_roles");

    if (empty($roles)) {
      return;
    }

    if ($this->doPopulateUserRoles($formId, $dynamicFormId, $roles)) {
      foreach ($roles as $roles_key => $roles_value) {
        $form['account']['roles']['#default_value'][] = $roles_key;
      }
    }
  }

  /**
   * Evaluate if FormModeManager do applies formAlter method.
   *
   * @return bool
   *   True if user_registration is enabled and this user route are FMM route.
   */
  public function candidateToAlterForm() {
    $routeObject = $this->routeMatch->getRouteObject();
    if (NULL === $routeObject) {
      return FALSE;
    }

    return $routeObject->getOption('_form_mode_manager_entity_type_id') === "user";
  }

  /**
   * Evaluate if current route is a register/create path.
   *
   * @return bool
   *   True if route match with register pattern or False if not.
   */
  private function isUserRegistrationRoute() {
    /** @var \Symfony\Component\Routing\Route $routeObject */
    $routeObject = $this->routeMatch->getRouteObject();

    return preg_match(self::REGISTER_PATH_CONTEXT_REGEX, $routeObject->getPath()) === 1;
  }

  /**
   * Evaluate if current form do pre populate user of not.
   *
   * @param string $formId
   *   The Form ID.
   * @param string $dynamicFormId
   *   The Form ID calculated via routing entity_form parameters.
   * @param array $roles
   *   Roles to automatically assign to new user to register.
   *
   * @return bool
   *   True if current register form can assign roles from configuration.
   */
  public function doPopulateUserRoles($formId, $dynamicFormId, array $roles) {
    return ($formId == $dynamicFormId) && !empty($roles);
  }

}
