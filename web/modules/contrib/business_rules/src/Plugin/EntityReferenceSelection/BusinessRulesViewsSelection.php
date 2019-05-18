<?php

namespace Drupal\business_rules\Plugin\EntityReferenceSelection;

use Drupal\business_rules\Ajax\UpdateOptionsCommand;
use Drupal\business_rules\Util\BusinessRulesUtil;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin override of the 'selection' entity_reference.
 *
 * @package Drupal\business_rules\Plugin\EntityReferenceSelection
 *
 * @EntityReferenceSelection(
 *   id = "business_rules_views",
 *   label = @Translation("Business Rules: Make field dependent using views"),
 *   group = "business_rules_views",
 *   weight = 0
 * )
 */
class BusinessRulesViewsSelection extends PluginBase implements SelectionInterface, ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Business Rules Util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  protected $util;

  /**
   * The loaded View object.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * Constructs a new SelectionBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\business_rules\Util\BusinessRulesUtil $util
   *   The BusinessRulesUtil.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, BusinessRulesUtil $util) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->util = $util;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('business_rules.util')
    );
  }

  /**
   * Element validate; Check View is valid.
   */
  public static function settingsFormValidate($element, FormStateInterface $form_state, $form) {
    // Split view name and display name from the 'view_and_display' value.
    if (!empty($element['view_and_display']['#value'])) {
      list($view, $display) = explode(':', $element['view_and_display']['#value']);
    }
    else {
      $form_state->setError($element, t('The views entity selection mode requires a view.'));

      return;
    }

    // Explode the 'arguments' string into an actual array. Beware, explode()
    // turns an empty string into an array with one empty string. We'll need an
    // empty array instead.
    $arguments_string = trim($element['arguments']['#value']);
    if ($arguments_string === '') {
      $arguments = [];
    }
    else {
      // array_map() is called to trim whitespaces from the arguments.
      $arguments = array_map('trim', explode(',', $arguments_string));
    }

    $value = [
      'view_name' => $view,
      'display_name' => $display,
      'arguments' => $arguments,
      'parent_field' => $element['parent_field']['#value'],
    ];
    $form_state->setValueForElement($element, $value);
  }

  /**
   * Update the dependent field options.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   The updated field.
   */
  public static function updateDependentField(array $form, FormStateInterface $form_state) {

    $entity = $form_state->getFormObject()->getEntity();
    $trigger_field = $form_state->getTriggeringElement();

    // Update children.
    $children = $trigger_field['#ajax']['br_children'];
    $response = new AjaxResponse();
    foreach ($children as $child) {
      $field_definition = $entity->getFieldDefinitions();
      if ($field_definition[$child]->getSetting('handler') == 'business_rules_views') {
        $handle_settings = $field_definition[$child]->getSetting('handler_settings');

        $parent_field_value = $trigger_field['#value'];
        if ($trigger_field['#type'] === 'entity_autocomplete' && preg_match('/\((\d+)\)$/', $parent_field_value, $matches)) {
          // If the field widget is entity autocomplete, the returned value is a
          // string which contains the entity id.
          $parent_field_value = $matches[1];
        }
        // If we have an array with values we should implode those values and
        // enable Allow multiple values into our contextual filter.
        if (is_array($parent_field_value)) {
          $parent_field_value = implode(",", $parent_field_value);
        }
        $arguments = $handle_settings['business_rules_view']['arguments'];
        $args = !empty($parent_field_value) ? [$parent_field_value] + $arguments : $arguments;
        $view_id = $handle_settings['business_rules_view']['view_name'];
        $display_id = $handle_settings['business_rules_view']['display_name'];

        // Get values from the view.
        $view = Views::getView($view_id);
        $view->setArguments($args);
        $view->setDisplay($display_id);
        $view->preExecute();
        $view->build();

        $options = [];

        if ($view->execute()) {
          $renderer = \Drupal::service('renderer');
          $render_array = $view->style_plugin->render();
          foreach ($render_array as $key => $value) {
            $rendered_value = (string) $renderer->render($value);
            $options[] = [
              'key' => $key,
              'value' => strip_tags($rendered_value),
            ];
          }
        }

        uasort($options, function ($a, $b) {
          return $a['value'] < $b['value'] ? -1 : 1;
        });

        array_unshift($options, [
          'key' => '_none',
          'value' => t('-Select-'),
        ]);

        $form_field = $form[$child];
        $form_field['widget']['#options'] = $options;
        $html_field_id = explode('-wrapper-', $form_field['#id'])[0];

        // Fix html_field_id last char when it ends with _.
        $html_field_id = substr($child, strlen($child) - 1, 1) == '_' ? $html_field_id . '-' : $html_field_id;

        $response->addCommand(new UpdateOptionsCommand($html_field_id, $options));

      }
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $handler_settings = $this->getHandlerSettings();
    $view_settings = !empty($handler_settings['business_rules_view']) ? $handler_settings['business_rules_view'] : [];
    $displays = Views::getApplicableViews('entity_reference_display');
    // Filter views that list the entity type we want, and group the separate
    // displays by view.
    $entity_type = $this->entityManager->getDefinition($this->configuration['target_type']);
    $view_storage = $this->entityManager->getStorage('view');

    $options = [];
    foreach ($displays as $data) {
      list($view_id, $display_id) = $data;
      $view = $view_storage->load($view_id);
      if (in_array($view->get('base_table'), [
        $entity_type->getBaseTable(),
        $entity_type->getDataTable(),
      ])) {
        $display = $view->get('display');
        $options[$view_id . ':' . $display_id] = $view_id . ' - ' . $display[$display_id]['display_title'];
      }
    }

    // The value of the 'view_and_display' select below will need to be split
    // into 'view_name' and 'view_display' in the final submitted values, so
    // we massage the data at validate time on the wrapping element (not
    // ideal).
    $form['business_rules_view']['#element_validate'] = [
      [
        get_called_class(),
        'settingsFormValidate',
      ],
    ];

    if ($options) {

      $form['business_rules_view']['help']['#markup'] = t('This plugin do not works for autocomplete form widget. Make sure you have selected "Select list" or "Check boxes/radio buttons" at "Manage form display" tab.');

      $default = !empty($view_settings['view_name']) ? $view_settings['view_name'] . ':' . $view_settings['display_name'] : NULL;

      $form['business_rules_view']['view_and_display'] = [
        '#type' => 'select',
        '#title' => $this->t('View used to select the entities'),
        '#required' => TRUE,
        '#options' => $options,
        '#default_value' => $default,
        '#description' => '<p>' . $this->t('Choose the view and display that select the entities that can be referenced.<br />Only views with a display of type "Entity Reference" are eligible.') . '</p>',
      ];

      /** @var \Drupal\field\Entity\FieldConfig $field_config */
      $field_config = $this->util->request->get('field_config');
      $entity_type = $field_config->getTargetEntityTypeId();
      $bundle = $field_config->getTargetBundle();
      $fields = $this->util->getBundleEditableFields($entity_type, $bundle);

      $fields_options = [];
      if (count($fields)) {
        foreach ($fields as $key => $name) {
          // Do not include the dependent field itself.
          if ($key !== 'title' && $key !== $field_config->getName()) {
            $fields_options[$key] = $name;
          }
        }
      }

      $default = !empty($view_settings['parent_field']) ? $view_settings['parent_field'] : NULL;
      $form['business_rules_view']['parent_field'] = [
        '#type' => 'select',
        '#title' => t('Parent field'),
        '#options' => $fields_options,
        '#required' => TRUE,
        '#description' => t('The field which this field depends. When the parent field value is changed, the available options for this field will be updated using the parent field value as the first argument followed by any particular other argument imputed in the "Views arguments".'),
        '#default_value' => $default,
      ];

      $default = !empty($view_settings['arguments']) ? implode(', ', $view_settings['arguments']) : '';
      $form['business_rules_view']['arguments'] = [
        '#type' => 'textfield',
        '#title' => $this->t('View arguments'),
        '#default_value' => $default,
        '#required' => FALSE,
        '#description' => $this->t('Provide a comma separated list of arguments to pass to the view.'),
      ];
    }
    else {
      if ($this->currentUser->hasPermission('administer views') && $this->moduleHandler->moduleExists('views_ui')) {
        $form['business_rules_view']['no_view_help'] = [
          '#markup' => '<p>' . $this->t('No eligible views were found. <a href=":create">Create a view</a> with an <em>Entity Reference</em> display, or add such a display to an <a href=":existing">existing view</a>.',
              [
                ':create' => Url::fromRoute('views_ui.add')->toString(),
                ':existing' => Url::fromRoute('entity.view.collection')
                  ->toString(),
              ]) . '</p>',
        ];
      }
      else {
        $form['business_rules_view']['no_view_help']['#markup'] = '<p>' . $this->t('No eligible views were found.') . '</p>';
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Initializes a view.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   * @param int $limit
   *   Limit the query to a given number of items. Defaults to 0, which
   *   indicates no limiting.
   * @param array|null $ids
   *   Array of entity IDs. Defaults to NULL.
   *
   * @return bool
   *   Return TRUE if the view was initialized, FALSE otherwise.
   */
  protected function initializeView($match = NULL, $match_operator = 'CONTAINS', $limit = 0, $ids = NULL) {
    $handler_settings = $this->getHandlerSettings();
    $view_name = $handler_settings['business_rules_view']['view_name'];
    $display_name = $handler_settings['business_rules_view']['display_name'];

    // Check that the view is valid and the display still exists.
    $this->view = Views::getView($view_name);
    if (!$this->view || !$this->view->access($display_name)) {
      drupal_set_message(t('The reference view %view_name cannot be found.', ['%view_name' => $view_name]), 'warning');

      return FALSE;
    }
    $this->view->setDisplay($display_name);

    // Pass options to the display handler to make them available later.
    $entity_reference_options = [
      'match' => $match,
      'match_operator' => $match_operator,
      'limit' => $limit,
      'ids' => $ids,
    ];
    $this->view->displayHandlers->get($display_name)
      ->setOption('entity_reference_options', $entity_reference_options);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $handler_settings = $this->getHandlerSettings();
    $display_name = $handler_settings['business_rules_view']['display_name'];
    $arguments = $handler_settings['business_rules_view']['arguments'];
    $parent_field_value = $this->getParentFieldValue($this->configuration['entity']);
    if (is_array($parent_field_value) && !empty($parent_field_value['target_id']) && preg_match('/\((\d+)\)$/', $parent_field_value['target_id'], $matches)) {
      // If the field widget is entity autocomplete, the returned value is a
      // string which contains the entity id.
      $parent_field_value = $matches[1];
    }
    // If we have an array with values we should implode those values and enable
    // Allow multiple values into our contextual filter.
    if (is_array($parent_field_value)) {
      $parent_field_value = implode(",", $parent_field_value);
    }
    $arguments = !empty($parent_field_value) ? [$parent_field_value] + $arguments : $arguments;
    $result = [];
    if ($this->initializeView($match, $match_operator, $limit)) {
      // Get the results.
      $result = $this->view->executeDisplay($display_name, $arguments);
    }

    $return = [];
    if ($result) {
      foreach ($this->view->result as $row) {
        $entity = $row->_entity;
        $return[$entity->bundle()][$entity->id()] = $entity->label();
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $this->getReferenceableEntities($match, $match_operator);

    return $this->view->pager->getTotalItems();
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $handler_settings = $this->getHandlerSettings();
    $display_name = $handler_settings['business_rules_view']['display_name'];
    $arguments = $handler_settings['business_rules_view']['arguments'];
    $parent_field_value = $this->getParentFieldValue();
    // If we have an array with values we should implode those values and enable
    // Allow multiple values into our contextual filter.
    if (is_array($parent_field_value)) {
      $parent_field_value = implode(",", $parent_field_value);
    }
    $arguments = !empty($parent_field_value) ? [$parent_field_value] + $arguments : $arguments;
    $result = [];
    $ids = $this->getValidIds($parent_field_value);
    if ($this->initializeView(NULL, 'CONTAINS', 0, $ids)) {
      // Get the results.
      $entities = $this->view->executeDisplay($display_name, $arguments);
      $result = is_array($entities) ? array_keys($entities) : [];
    }

    return $result;
  }

  /**
   * Return valid ids for validation.
   *
   * @param string $parent_field_value
   *   The parent field value.
   *
   * @return array
   *   Array with valid ids.
   */
  private function getValidIds($parent_field_value) {
    $handler_settings = $this->getHandlerSettings();
    $display_name = $handler_settings['business_rules_view']['display_name'];
    $arguments = $handler_settings['business_rules_view']['arguments'];
    $arguments = !empty($parent_field_value) ? [$parent_field_value] + $arguments : $arguments;
    $result = [];
    if ($this->initializeView(NULL, 'CONTAINS', 0)) {
      // Get the results.
      $result = $this->view->executeDisplay($display_name, $arguments);
    }
    $return = [];
    if ($result) {
      foreach ($this->view->result as $row) {
        $entity = $row->_entity;
        $return[] = $entity->id();
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {}

  /**
   * Get the parent field value.
   *
   * @param \Drupal\Core\Entity\Entity|NULL $entity
   *   The fallback entity to extract the value from.
   *
   * @return mixed
   *   The parent field value.
   */
  protected function getParentFieldValue(EntityInterface $entity = NULL) {
    $handler_settings = $this->getHandlerSettings();
    $field = $handler_settings['business_rules_view']['parent_field'];
    $value = $this->util->request->get($field);

    if (!$value && $entity) {
      $value = $entity->get($field)->getString();
    }
    if (is_array($value) && !empty($value[0]['target_id']) && preg_match('/\((\d+)\)$/', $value[0]['target_id'], $matches)) {
      // If the field widget is entity autocomplete, the returned value is a
      // string which contains the entity id.
      $value = $matches[1];
    }

    return $value;
  }

  /**
   * Get the handler settings.
   *
   * @return array
   *   The handler settings.
   */
  protected function getHandlerSettings() {
    // The ['handler_settings'] was removed on Drupal 8.4. the code bellow is
    // kept for back compatibility. @see https://www.drupal.org/node/2870971
    if (isset($this->configuration['handler_settings'])) {
      return $this->configuration['handler_settings'];
    }

    return $this->configuration;
  }

}
