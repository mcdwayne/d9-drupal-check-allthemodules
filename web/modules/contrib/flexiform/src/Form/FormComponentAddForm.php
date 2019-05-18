<?php

namespace Drupal\flexiform\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\flexiform\FlexiformEntityFormDisplayInterface;
use Drupal\flexiform\FormComponentTypePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides a form for adding new form components.
 */
class FormComponentAddForm extends FormBase {

  /**
   * The form display.
   *
   * @var \Drupal\flexiform\FlexiformEntityFormDisplay
   */
  protected $formDisplay;

  /**
   * The form component plugin manager.
   *
   * @var \Drupal\flexiform\FormComponentTypePluginManager
   */
  protected $pluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The router.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * Constructor.
   */
  public function __construct(FormComponentTypePluginManager $plugin_manager, EntityTypeManagerInterface $entity_type_manager, RouterInterface $router) {
    $this->pluginManager = $plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->router = $router;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.flexiform.form_component_type'),
      $container->get('entity_type.manager'),
      $container->get('router')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flexiform_form_component_add';
  }

  /**
   * Get the form entity manager.
   *
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   *   The form entity manager.
   */
  protected function formEntityManager() {
    return $this->formDisplay->getFormEntityManager();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FlexiformEntityFormDisplayInterface $form_display = NULL, $component_type = '') {
    $this->formDisplay = $form_display;
    $this->componentType = $this->pluginManager->createInstance($component_type);
    $this->componentType->setFormDisplay($form_display);

    $form['admin_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administration Label'),
      '#description' => $this->t('A label for this component. This will only be used administrativly'),
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Component Name'),
      '#description' => $this->t('The name of this component. This must be unique in the form.'),
      '#machine_name' => [
        'source' => ['admin_label'],
        'exists' => [$this, 'nameExists'],
      ],
    ];
    $form['region'] = [
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#options' => $this->formDisplay->getRegionOptions(),
    ];
    unset($form['region']['#options']['hidden']);
    $form['options'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#parents' => ['options'],
    ];
    $form['options'] += $this->componentType->addComponentForm($form['options'], $form_state);

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Add Component'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($this->componentType)) {
      $this->componentType->addComponentFormValidate($form['options'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    $options = [
      'component_type' => $this->componentType->getPluginId(),
      'region' => $form_state->getValue('region'),
      'admin_label' => $form_state->getValue('admin_label'),
    ];
    $this->componentType->addComponentFormSubmit($form['options'], $form_state);
    if ($plugin_options = $form_state->getValue('options')) {
      $options += $plugin_options;
    }

    $this->formDisplay->setComponent($name, $options);
    $this->formDisplay->save();

    $params = [
      'form_mode_name' => $this->formDisplay->get('mode'),
    ];
    $entity_type_id = $this->formDisplay->get('targetEntityType');
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if ($route_name = $entity_type->get('field_ui_base_route')) {
      $route = $this->router->getRouteCollection()->get($route_name);
      $path = $route->getPath();

      if (strpos($path, '{' . $entity_type->getBundleEntityType() . '}') !== FALSE) {
        $params[$entity_type->getBundleEntityType()] = $this->formDisplay->get('bundle');
      }
      elseif (strpos($path, '{bundle}') !== FALSE) {
        $params['bundles'] = $this->formDisplay->get('bundle');
      }
    }
    $form_state->setRedirect(
      "entity.entity_form_display.{$entity_type_id}.form_mode",
      $params
    );
  }

  /**
   * Ajax the plugin selection.
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    // Prepare redirect parameters.
    $params = [
      'form_mode_name' => $this->formDisplay->get('mode'),
    ];
    $entity_type_id = $this->formDisplay->get('targetEntityType');
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if ($route_name = $entity_type->get('field_ui_base_route')) {
      $route = $this->router->getCollection()->get($route_name);
      $path = $route->getPath();

      if (strpos($path, '{' . $entity_type->getBundleEntityType() . '}') !== FALSE) {
        $params[$entity_type->getBundleEntityType()] = $this->formDisplay->get('bundle');
      }
      elseif (strpos($path, '{bundle}') !== FALSE) {
        $params['bundles'] = $this->formDisplay->get('bundle');
      }
    }

    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new RedirectCommand((new Url(
        "entity.entity_form_display.{$entity_type_id}.form_mode",
        $params
      ))->toString()
    ));
    return $response;
  }

  /**
   * Check whether the namespace already exists.
   */
  public function nameExists($name, $element, FormStateInterface $form_state) {
    return ($this->formDisplay->getComponent($name) !== NULL);
  }

}
