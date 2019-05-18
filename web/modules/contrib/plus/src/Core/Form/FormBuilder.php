<?php

namespace Drupal\plus\Core\Form;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilder as CoreFormBuilder;
use Drupal\Core\Form\FormCacheInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormSubmitterInterface;
use Drupal\Core\Form\FormValidatorInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\plus\FormAlterPluginManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class FormBuilder extends CoreFormBuilder {

  /**
   * The Form Alter Plugin Manager service.
   *
   * @var \Drupal\plus\FormAlterPluginManager
   */
  protected $formAlterPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(FormValidatorInterface $form_validator, FormSubmitterInterface $form_submitter, FormCacheInterface $form_cache, ModuleHandlerInterface $module_handler, EventDispatcherInterface $event_dispatcher, RequestStack $request_stack, ClassResolverInterface $class_resolver, ElementInfoManagerInterface $element_info, ThemeManagerInterface $theme_manager, CsrfTokenGenerator $csrf_token = NULL, FormAlterPluginManager $form_alter_plugin_manager = NULL) {
    parent::__construct($form_validator, $form_submitter, $form_cache, $module_handler, $event_dispatcher, $request_stack, $class_resolver, $element_info, $theme_manager, $csrf_token);
    $this->formAlterPluginManager = $form_alter_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareForm($form_id, &$form, FormStateInterface &$form_state) {
    parent::prepareForm($form_id, $form, $form_state);
    $this->formAlterPluginManager->alter($form, $form_state, $form_id);
  }

}
