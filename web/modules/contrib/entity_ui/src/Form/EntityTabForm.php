<?php

namespace Drupal\entity_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\entity_ui\Plugin\EntityTabContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for editing and creating entity tab entities.
 */
class EntityTabForm extends EntityForm {

  /**
   * The Entity Tab content plugin manager
   *
   * @var \Drupal\entity_ui\Plugin\EntityTabContentManager
   */
  protected $entityTabContentPluginManager;

  /**
   * The menu local task plugin manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $menuLocalTaskPluginManager;

  /**
   * The router builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * The route provider to load routes by name.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface;
   */
  protected $bundleInfoService;

  /**
   * Constructs a new EntityTabForm.
   *
   * @param \Drupal\entity_ui\Plugin\EntityTabContentManager
   *   The entity tab plugin manager.
   */
  public function __construct(
    EntityTabContentManager $entity_tab_content_manager,
    LocalTaskManagerInterface $plugin_manager_menu_local_task,
    RouteBuilderInterface $router_builder,
    RouteProviderInterface $route_provider,
    EntityTypeBundleInfoInterface $bundle_info_service
    ) {
    $this->entityTabContentPluginManager = $entity_tab_content_manager;
    $this->menuLocalTaskPluginManager = $plugin_manager_menu_local_task;
    $this->routerBuilder = $router_builder;
    $this->routeProvider = $route_provider;
    $this->bundleInfoService = $bundle_info_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_ui_tab_content'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('router.builder'),
      $container->get('router.route_provider'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $this->entityType = $this->entityTypeManager->getDefinition($this->entity->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($route_match->getRawParameter($entity_type_id) !== NULL) {
      $entity = $route_match->getParameter($entity_type_id);
    }
    else {
      $values = [];

      // Get the target entity type and plugin ID from the route's parameters.
      $values['target_entity_type'] = $route_match->getParameter('target_entity_type_id');
      $values['content_plugin'] = $route_match->getParameter('plugin_id');

      // Allow the chosen content plugin to suggest default values for the
      // entity tab.
      $content_plugin_definition = $this->entityTabContentPluginManager->getDefinition($values['content_plugin']);
      $values += $content_plugin_definition['class']::suggestedEntityTabValues($content_plugin_definition);

      // Remove the path if it already exists.
      if (isset($values['path']) && $this->getRouteForEntityPath($values['target_entity_type'], $values['path'])) {
        unset($values['path']);
      }

      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $target_entity_type_id = NULL, $plugin_id = NULL) {
    $form = parent::buildForm($form, $form_state);

    if ($this->entity->isNew()) {
      if (empty($target_entity_type_id)) {
        // We can't operate without our additional parameter.
        throw new \Exception('Missing parameter $target_entity_type_id.');
      }

      $target_entity_type = $this->entityTypeManager->getDefinition($this->entity->getTargetEntityTypeID());
      $form['#title'] = $this->t('Add new %label @entity-type', array(
        '%label' => $target_entity_type->getLabel(),
        '@entity-type' => $this->entityType->getLowercaseLabel(),
      ));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity_tab = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_tab->label(),
      '#description' => $this->t("The admin label for the Entity tab."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_tab->id(),
      '#field_prefix' => $entity_tab->isNew() ? $entity_tab->getTargetEntityTypeID() . '.' : '',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '[^a-z0-9_.]+',
      ],
      '#disabled' => !$entity_tab->isNew(),
    ];

    $form['tab_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tab title'),
      '#maxlength' => 255,
      '#default_value' => $entity_tab->get('tab_title'),
      '#description' => $this->t("The label for the tab on the target entity type."),
      '#required' => TRUE,
    ];

    $form['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#maxlength' => 255,
      '#default_value' => $entity_tab->get('page_title'),
      '#description' => $this->t("The page title to show when the entity tab is displayed. Tokens may be used it this field starting with '[entity_ui_tab:target_entity:' for the target entity."),
      '#required' => TRUE,
    ];

    $target_entity_type_id = $entity_tab->getTargetEntityTypeID();
    $target_entity_type = $this->entityTypeManager->getDefinition($target_entity_type_id);
    $target_entity_type_canonical_URL = $target_entity_type->getLinkTemplate('canonical');
    $example_url = str_replace("{{$target_entity_type_id}}", 'ID', $target_entity_type_canonical_URL);
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path component'),
      '#field_prefix' => $example_url . '/',
      '#maxlength' => 16,
      '#size' => 16,
      '#default_value' => $entity_tab->get('path'),
      '#description' => $this->t("The path component to append to the entity's canonical URL to form the URL for this tab."),
      '#required' => TRUE,
    ];

    $bundles = $this->bundleInfoService->getBundleInfo($target_entity_type_id);
    $entity_bundles = [];
    foreach ($bundles as $bundle_id => $bundle_row) {
      $entity_bundles[$bundle_id] = $bundle_row['label'];
    }

    $form['target_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Target bundles'),
      '#options' => $entity_bundles,
      '#default_value' => $entity_tab->get('target_bundles'),
      '#description' => $this->t('The bundles of the target entity type on which this tab is available. Leave empty to show on all bundles.'),
    ];
    // Hide the target bundles element on entity types that don't use bundles.
    if (!$target_entity_type->hasKey('bundle')) {
      $form['target_bundles']['#access'] = FALSE;
    }

    $form['content'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Content options'),
      '#description' => $this->t('The output to show on this tab.'),
      '#tree' => FALSE,
      '#prefix' => '<div id="content-settings-wrapper">',
      '#suffix' => '</div>',
    ];

    $options = [];
    foreach ($this->entityTabContentPluginManager->getDefinitions() as $plugin_id => $definition) {
      if ($definition['class']::appliesToEntityType($target_entity_type, $definition)) {
        $options[$plugin_id] = $definition['label'];
      }
    }
    natcasesort($options);
    $form['content']['content_plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Content'),
      '#options' => $options,
      '#default_value' => $entity_tab->get('content_plugin'),
      '#description' => $this->t("The content provider for this tab."),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateSelectedPluginType',
        'wrapper' => 'content-settings-wrapper',
        'event' => 'change',
        'method' => 'replace',
      ],
    ];

    $form['content']['content_plugin_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#submit' => ['::submitSelectPlugin'],
      '#attributes' => ['class' => ['js-hide']],
    ];

    $form['content']['content_config'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $content_plugin = $entity_tab->getContentPlugin();
    $subform_state = SubformState::createForSubform($form['content']['content_config'], $form, $form_state);
    $form['content']['content_config'] += $content_plugin->buildConfigurationForm($form['content']['content_config'], $subform_state);


    return $form;
  }

  /**
   * Handles switching the configuration type selector.
   */
  public function updateSelectedPluginType($form, FormStateInterface $form_state) {
    return $form['content'];
  }

  /**
   * Handles submit call when content plugin is selected.
   */
  public function submitSelectPlugin(array $form, FormStateInterface $form_state) {
    // Rebuild the entity using the form's new state.
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state->setRebuild();
  }

  /**
   * Determines if the entity tab already exists.
   *
   * Callback for the machine_name form element.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   *
   * @return bool
   *   TRUE if the entity tab exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element) {
    return (bool) $this->entityTypeManager->getStorage('entity_tab')
      ->getQuery()
      ->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate the subform from the content plugin.
    $content_plugin = $this->entity->getContentPlugin();
    $subform_state = SubformState::createForSubform($form['content']['content_config'], $form, $form_state);
    $content_plugin->validateConfigurationForm($form['content']['content_config'], $subform_state);

    // Validate the path doesn't clash with an existing route.
    // This should only be done if the entity is new, or if the path is being
    // changed, as otherwise our own route is already defined and will be found.
    $validate_path = FALSE;
    if ($this->entity->isNew()) {
      $validate_path = TRUE;
    }
    else {
      $original_entity = $this->entityTypeManager->getStorage('entity_tab')->loadUnchanged($this->entity->id());
      if ($form_state->getValue('path') != $original_entity->get('path')) {
        $validate_path = TRUE;
      }
    }

    if ($validate_path) {
      $found_route = $this->getRouteForEntityPath($this->entity->getTargetEntityTypeID(), $form_state->getValue('path'));
      if ($found_route) {
        $form_state->setErrorByName('path', $this->t('The path @path already exists.', [
          '@path' => $found_route->getPath(),
        ]));
      }
    }

    // Sets the entity type ID into the config name for a new entity tab.
    if ($this->entity->isNew()) {
      $form_state->setValueForElement($form['id'], $this->entity->getTargetEntityTypeID() . '.' . $form_state->getValue('id'));
    }

    // Ensure that content_config is at least an array, in the case that the
    // plugin doesn't provide anything for the form.
    if (is_null($form_state->getValue('content_config'))) {
      $form_state->setValue('content_config', []);
    }
  }

  /**
   * Determine whether a route exists for a path component on the target entity.
   *
   * @param $target_entity_type_id
   *  The target entity type ID.
   * @param string $path_component
   *  A path component, i.e. the part of the path that is appended to the
   *  canonical URL.
   *
   * @return \Symfony\Component\Routing\Route|null
   *  The route, or NULL if nothing is found.
   */
  protected function getRouteForEntityPath($target_entity_type_id, $path_component) {
    $target_entity_type_canonical_url = $this->getTargetEntityTypeCanonicalURL($target_entity_type_id);
    $proposed_path = $target_entity_type_canonical_url . '/' . $path_component;
    $found_routes = $this->routeProvider->getRoutesByPattern($proposed_path);
    $route_iterator = $found_routes->getIterator();
    if (count($route_iterator)) {
      $found_route = reset($route_iterator);
      return $found_route;
    }
  }

  /**
   * Gets the canonical URL for the entity tab's target entity type.
   *
   * @param $target_entity_type_id
   *  The target entity type ID.
   *
   * @return string
   *  The canonical URL.
   */
  protected function getTargetEntityTypeCanonicalURL($target_entity_type_id) {
    $target_entity_type = $this->entityTypeManager->getDefinition($target_entity_type_id);
    $target_entity_type_canonical_url = $target_entity_type->getLinkTemplate('canonical');

    return $target_entity_type_canonical_url;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // Filter the checkboxes array.
    $form_state->setValue('target_bundles', array_filter($form_state->getValue('target_bundles')));

    parent::copyFormValuesToEntity($entity, $form, $form_state);

    $values = $form_state->getValues();

    // Repeat part of the work of the parent, as it doesn't set the plugin
    // configuration otherwise, for reasons I don't understand.
    // See https://www.drupal.org/node/2882760.
    // Need to ensure this is an array in the case that the plugin doesn't
    // provide anything for the form.
    $entity->set('content_config', $values['content_config'] ?? []);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_tab = $this->entity;

    $status = $entity_tab->save();

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label Entity tab.', [
          '%label' => $entity_tab->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Entity tab.', [
          '%label' => $entity_tab->label(),
        ]));
    }

    // Redirect to the collection for the tab's target entity type.
    $target_entity_type_id = $entity_tab->getTargetEntityTypeID();
    $form_state->setRedirectUrl(Url::fromRoute("entity_ui.entity_tab.{$target_entity_type_id}.collection"));
  }

}
