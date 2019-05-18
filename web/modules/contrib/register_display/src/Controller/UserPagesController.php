<?php

namespace Drupal\Register_display\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\register_display\RegisterDisplayServices;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UserPagesController.
 *
 * @package Drupal\Register_display\Controller
 */
class UserPagesController extends ControllerBase {

  protected $services;
  protected $entityTypeManager;
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(RegisterDisplayServices $services,
    EntityTypeManagerInterface $entityTypeManager,
    FormBuilderInterface $formBuilder) {
    $this->services = $services;
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('register_display.services'),
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );

  }

  /**
   * Register page.
   *
   * @param string $roleId
   *   Role Id.
   *
   * @return array
   *   Array form.
   */
  public function registerPage(string $roleId) {
    $registerPageConfig = $this->services->getRegistrationPages($roleId);

    // Be sure definition is up to date.
    $userEntityDefinition = $this->entityTypeManager->getDefinition('user');
    if (!array_key_exists($registerPageConfig['displayId'], $userEntityDefinition->getHandlerClasses()['form'])) {
      $this->entityTypeManager->clearCachedDefinitions();
    }

    $entity = User::create();
    $form_object = $this->entityTypeManager->getFormObject($entity->getEntityTypeId(), $registerPageConfig['displayId']);
    $form_object->setEntity($entity);

    $form_state = (new FormState())->setFormState([]);
    // Add role id value for form state.
    $form_state->setValue('roleId', $roleId);
    $registerForm = $this->formBuilder->buildForm($form_object, $form_state);
    // Set register button text.
    if (!empty($registerPageConfig['registerPageButtonText'])) {
      $registerForm['actions']['submit']['#value'] = $this->t('@registerPageButtonText', [
        '@registerPageButtonText' => $registerPageConfig['registerPageButtonText'],
      ]);
    }
    return $registerForm;
  }

  /**
   * Register page title callback.
   *
   * @param string $roleId
   *   Role ID.
   *
   * @return string
   *   Page title.
   */
  public function registerPageTitle(string $roleId) {
    $registerPageConfig = $this->services->getRegistrationPages($roleId);
    return $registerPageConfig['registerPageTitle'];
  }

  /**
   * Redirect to target registration page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function redirectControl() {
    $roleId = $this->services->getRedirectTarget();
    return $this->redirect('register_display.user_register_page', ['roleId' => $roleId]);
  }

}
