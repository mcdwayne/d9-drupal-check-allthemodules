<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\form_mode_manager\Routing\EventSubscriber\FormModesSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates Form Alter information.
 *
 * This class contains primarily bridged hooks for form.
 */
class FormAlter implements ContainerInjectionInterface {

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
   * FormAlter constructor.
   *
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(FormModeManagerInterface $form_mode_manager, RouteMatchInterface $route_match, ModuleHandlerInterface $module_handler) {
    $this->formModeManager = $form_mode_manager;
    $this->routeMatch = $route_match;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_mode.manager'),
      $container->get('current_route_match'),
      $container->get('module_handler')
    );
  }

  /**
   * Allow to use user_registrationpassword form_alter with FormModeManager.
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

    $this->userRegistrationPasswordFormAlter($form, $formState, $formId);
  }

  /**
   * Applies the user_register_form_alter on form_mode_manager register routes.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   * @param string $formId
   *   The Form ID.
   */
  public function userRegistrationPasswordFormAlter(array &$form, FormStateInterface $formState, $formId) {
    /** @var \Symfony\Component\Routing\Route $routeObject */
    $routeObject = $this->routeMatch->getRouteObject();
    $dynamicFormId = str_replace('.', '_', $routeObject->getDefault('_entity_form')) . '_form';

    // Prevent cases of register users create/register operations.
    if (FormModesSubscriber::isEditRoute($routeObject)) {
      return;
    }

    if ($this->appliesUserRegistrationPasswordFormAlter($formId, $dynamicFormId)) {
      user_registrationpassword_form_user_register_form_alter($form, $formState);
    }
  }

  /**
   * Evaluate if current form can applies the user_registrationpassword hook.
   *
   * @param string $formId
   *   The Form ID.
   * @param string $dynamicFormId
   *   The Form ID calculated via routing entity_form parameters.
   *
   * @return bool
   *   True if the hook can be applied of False if not.
   */
  public function appliesUserRegistrationPasswordFormAlter($formId, $dynamicFormId) {
    $routeObject = $this->routeMatch->getRouteObject();
    $formModeMachineName = $this->formModeManager->getFormModeMachineName($routeObject->getDefault('_entity_form'));

    return $this->formModeManager->isActive('user', NULL, $formModeMachineName) && $dynamicFormId === $formId;
  }

  /**
   * Evaluate if FormModeManager do applies this formAlter for registration.
   *
   * @return bool
   *   True if user_registration is enabled and this user route are FMM route.
   */
  public function candidateToAlterForm() {
    $routeObject = $this->routeMatch->getRouteObject();
    if (NULL === $routeObject) {
      return FALSE;
    }

    return $this->moduleHandler->moduleExists('user_registrationpassword') && $routeObject->getOption('_form_mode_manager_entity_type_id') === "user";
  }

}
