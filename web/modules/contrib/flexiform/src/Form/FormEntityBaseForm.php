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
use Drupal\flexiform\FlexiformFormEntityPluginManager;
use Drupal\flexiform\FormEntity\FlexiformFormEntityInterface;
use Drupal\flexiform\MultipleEntityFormState;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides a base class for entity forms.
 */
abstract class FormEntityBaseForm extends FormBase {

  /**
   * The form display.
   *
   * @var \Drupal\flexiform\FlexiformEntityFormDisplay
   */
  protected $formDisplay;

  /**
   * The form entity.
   *
   * @var \Drupal\flexiform\FormEntity\FlexiformFormEntityInterface
   */
  protected $formEntity;

  /**
   * The form entity plugin manager.
   *
   * @var \Drupal\flexiform\FlexiformFormEntityPluginManager
   */
  protected $pluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The router service.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * FormEntityBaseForm constructor.
   *
   * @param \Drupal\flexiform\FlexiformFormEntityPluginManager $plugin_manager
   *   The form entity plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\Routing\RouterInterface $router
   *   The router service.
   */
  public function __construct(FlexiformFormEntityPluginManager $plugin_manager, EntityTypeManagerInterface $entity_type_manager, RouterInterface $router) {
    $this->pluginManager = $plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->router = $router;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.flexiform_form_entity'),
      $container->get('entity_type.manager'),
      $container->get('router')
    );
  }

  /**
   * Get the form entity manager.
   *
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   *   The form entity manager.
   */
  protected function formEntityManager(FormStateInterface $form_state) {
    return $this->formDisplay->getFormEntityManager($form_state);
  }

  /**
   * Build the plugin configuration form.
   */
  protected function buildConfigurationForm(array $form, FormStateInterface $form_state, FlexiformFormEntityInterface $form_entity = NULL, $namespace = '') {
    $form_state = MultipleEntityFormState::createForForm($form, $form_state);
    $this->formEntity = $form_entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('A label for this entity. This is only used in the admin UI.'),
      '#required' => TRUE,
      '#default_value' => $form_entity->getLabel(),
    ];

    if (empty($namespace)) {
      $form['namespace'] = [
        '#type' => 'machine_name',
        '#title' => $this->t('Namespace'),
        '#description' => $this->t('Internal namespace for this entity and its fields.'),
        '#machine_name' => [
          'exists' => [$this, 'namespaceExists'],
          'label' => $this->t('Namespace'),
        ],
      ];
    }
    else {
      $form['namespace'] = [
        '#type' => 'value',
        '#value' => $namespace,
      ];
    }

    $form['configuration'] = [
      '#type' => 'container',
      '#parents' => ['configuration'],
      '#tree' => TRUE,
    ];
    $form['configuration'] += $form_entity->configurationForm($form['configuration'], $form_state);

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save Configuration'),
        '#validate' => [[$this, 'validateForm']],
        '#submit' => [[$this, 'submitForm']],
      ],
      '#ajax' => [
        'callback' => [$this, 'ajaxSubmit'],
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FlexiformEntityFormDisplayInterface $form_display = NULL) {
    $this->formDisplay = $form_display;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state = MultipleEntityFormState::createForForm($form, $form_state);
    if (!empty($this->formEntity)) {
      $this->formEntity->configurationFormValidate($form['configuration'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state = MultipleEntityFormState::createForForm($form, $form_state);
    $namespace = $form_state->getValue('namespace');
    $configuration = [
      'label' => $form_state->getValue('label'),
      'plugin' => $this->formEntity->getPluginId(),
    ];
    $this->formEntity->configurationFormSubmit($form['configuration'], $form_state);
    if ($plugin_conf = $form_state->getValue('configuration')) {
      $configuration += $plugin_conf;
    }

    $this->formDisplay->getFormEnhancer('multiple_entities')->addFormEntityConfig($namespace, $configuration);
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
  public function namespaceExists($namespace, $element, FormStateInterface $form_state) {
    $entities = $this->formDisplay->getFormEntityConfig();
    return !empty($entities[$namespace]);
  }

}
