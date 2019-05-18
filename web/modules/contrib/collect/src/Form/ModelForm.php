<?php
/**
 * @file
 * Contains \Drupal\collect\Form\ModelForm.
 */

namespace Drupal\collect\Form;

use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\Model\ModelInterface;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit form for model entities.
 */
class ModelForm extends EntityForm {

  /**
   * The injected model storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $storage;

  /**
   * The injected model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a ModelForm.
   */
  public function __construct(ConfigEntityStorageInterface $storage, ModelManagerInterface $plugin_manager) {
    $this->storage = $storage;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('collect_model'),
      $container->get('plugin.manager.collect.model')
    );
  }

  /**
   * Returns a title for the form.
   *
   * @param \Drupal\collect\Model\ModelInterface $collect_model
   *   The model being edited.
   *
   * @return string
   *   The form title.
   */
  public static function title(ModelInterface $collect_model) {
    return t('Edit %label model', ['%label' => $collect_model->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\collect\Entity\Model $model */
    $model = $this->getEntity();
    $model_plugin = $this->pluginManager->createInstanceFromConfig($model);

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $model->label(),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $model->id(),
      '#disabled' => !$model->isNew(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
      ),
    );

    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable model'),
      '#default_value' => $model->status(),
    );

    if ($model->isLocked()) {
      $form['locked_note'] = array(
        '#type' => 'item',
        '#title' => $this->t('Locked'),
        '#markup' => $this->t('This model is locked, possibly because it was added by default. It cannot be deleted and some basic settings can not be changed.'),
      );
    }

    $form['uri_pattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URI pattern'),
      '#description' => $this->t('Determines for which containers this model should be applied. Wildcards are not supported, but if a container matches more than one model, the one with the longest pattern is selected.'),
      '#default_value' => $model->getUriPattern(),
      '#required' => !$model->isLocked(),
      '#disabled' => $model->isLocked(),
      '#size' => 120,
    );

    // Filter out hidden plugins. The callback returns FALSE for definitions
    // with "hidden" = TRUE, unless it is the current value.
    $plugin_definitions = array_filter($this->pluginManager->getDefinitions(), function(array $definition) use ($model) {
      return !isset($definition['hidden']) || !$definition['hidden'] || $definition['id'] == $model->getPluginId();
    });
    $plugin_options = array_map(function(array $definition) {
      return $definition['label'];
    }, $plugin_definitions);

    $form['plugin_id'] = array(
      '#type' => 'select',
      '#title' => $this->t('Plugin'),
      '#description' => $this->t('The model plugin determines how to interpret the data in a container. It defines methods to address single values and affects how they are displayed.'),
      '#options' => $plugin_options,
      '#default_value' => $model->getPluginId(),
      '#required' => !$model->isLocked(),
      '#disabled' => $model->isLocked(),
      '#ajax' => array(
        'callback' => '::replacePluginInfo',
        'wrapper' => 'collect-model-form-plugininfo',
      ),
    );

    if ($model_plugin) {
      // Display plugin info.
      $form['plugin_info'] = [
        '#type' => 'details',
        '#title' => $this->t('Plugin info'),
      ];

      // Inner wrapper for ajax replacement.
      $form['plugin_info']['plugin'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'collect-model-form-plugininfo',
        ],
        '#tree' => TRUE,
      ];

      // Basic description.
      $form['plugin_info']['plugin']['description'] = [
        '#markup' => $model_plugin->getDescription(),
      ];

      // Extended help.
      $form['plugin_info']['plugin']['help'] = $model_plugin->help();

      // Suggested URI patterns.
      if ($model_plugin->getPatterns()) {
        $form['plugin_info']['plugin']['patterns'] = [
          '#type' => 'item',
          '#title' => $this->t('Suggested for these schema URI patterns'),
          'patterns' => array_map(function($pattern) {
            return ['#markup' => "<div><code>$pattern</code></div>"];
          }, $model_plugin->getPatterns()),
        ];
      }
    }

    $form['container_revision'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable container revisions'),
      '#description' => $this->t('Save new containers as revisions of existing containers, if they share origin and schema URI.'),
      '#default_value' => $model->isContainerRevision(),
    );

    $form['properties_container'] = array(
      '#type' => 'details',
      '#title' => $this->t('Properties'),
      '#open' => TRUE,
      '#description' => $this->t('These properties are exposed to processors and to Drupal in general. Properties suggested by the model plugin can be added automatically, and cannot be modified. If you know more about what values are available, you can expose them by adding custom properties.'),
    );

    $form['properties_container']['properties'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Name'),
        $this->t('Query'),
        $this->t('Type'),
        $this->t('Label & description'),
        $this->t('Operations'),
      ),
      '#empty' => $this->t('No properties defined.'),
    );

    // Populate properties table.
    foreach ($model_plugin->getTypedData()->getPropertyDefinitions() as $name => $definition) {
      $form['properties_container']['properties'][$name] = $this->buildPropertiesTableRow($name, $definition);
    }

    if (!$this->getEntity()->isNew()) {

      $suggested_properties_options = $this->buildSuggestedPropertiesOptions($model_plugin->getTypedData());

      $form['properties_container']['suggested'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Add suggested property'),
        '#description' => $this->t('The selected model plugin may offer properties that are known to exist in the data. Some plugins offer extra properties found in already stored data.'),
      ];

      $form['properties_container']['suggested']['property_name'] = [
        '#type' => 'select',
        '#title' => $this->t('Suggested property'),
        '#title_display' => 'hidden',
        '#options' => $suggested_properties_options,
        '#disabled' => empty($suggested_properties_options),
        '#empty_option' => $this->t('- Select -'),
      ];

      $form['properties_container']['suggested']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add'),
        '#submit' => ['::submitAddSuggestedProperty'],
        '#limit_validation_errors' => [['property_name']],
        '#disabled' => empty($suggested_properties_options),
        '#validate' => ['::validateAddSuggestedProperty'],
      ];

      $form['properties_container']['add_custom'] = [
        '#type' => 'link',
        '#attributes' => [
          'class' => ['button'],
        ],
        '#title' => $this->t('Add custom property'),
        '#url' => $model->urlInfo('property-add-form'),
      ];
    }

    return $form;
  }

  /**
   * Builds a row for the properties table.
   *
   * @param string $name
   *   Name of property.
   * @param \Drupal\collect\Model\PropertyDefinition $property_definition
   *   Property definition to represent in the row.
   *
   * @return array
   *   Table row with values.
   */
  protected function buildPropertiesTableRow($name, PropertyDefinition $property_definition) {
    $data_definition = $property_definition->getDataDefinition();
    // Format the data type string.
    $type = $data_definition->getDataType();
    if ($data_definition instanceof ListDataDefinitionInterface) {
      $type = $this->t('list of @type', ['@type' => $data_definition->getItemDefinition()->getDataType()]);
    }

    // Operation links.
    $operations = ['#type' => 'dropbutton'];
    if (!$this->getEntity()->isNew()) {
      if (!array_key_exists($name, $this->pluginManager->suggestProperties($this->getEntity()))) {
        $operations['#links']['edit'] = [
          'title' => $this->t('Edit'),
          'url' => $this->getEntity()->urlInfo('property-edit-form')->setRouteParameter('property_name', $name),
        ];
      }
      $operations['#links']['remove'] = [
        'title' => $this->t('Remove'),
        'url' => $this->getEntity()->urlInfo('property-remove')->setRouteParameter('property_name', $name),
      ];
    }

    // Return table row.
    return [
      ['#markup' => $name],
      ['#markup' => '<code>' . $property_definition->getQuery() . '</code>'],
      ['#markup' => $type],
      [
        '#type' => 'item',
        '#markup' => $data_definition->getLabel(),
        '#description' => SafeMarkup::format($data_definition->getDescription(), []),
      ],
      $operations,
    ];
  }

  /**
   * Builds options for the suggested property selector.
   *
   * @return array
   *   Generated property suggestions.
   */
  protected function buildSuggestedPropertiesOptions() {
    $definitions = $this->pluginManager->suggestProperties($this->getEntity());

    // Remove suggestions with names that already exist on the model.
    $definitions = array_diff_key($definitions, $this->getEntity()->getProperties());

    // Return labels.
    return array_map(function(PropertyDefinition $definition) {
      return $definition->getDataDefinition()->getLabel();
    }, $definitions);
  }

  /**
   * Validate the suggested property add selector.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function validateAddSuggestedProperty(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('property_name')) {
      $form_state->setError($form['properties_container']['suggested']['property_name'], $this->t('No suggested property selected.'));
    }
  }

  /**
   * Submit handler for the suggested property add button.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function submitAddSuggestedProperty(array &$form, FormStateInterface $form_state) {
    $suggested_properties = $this->pluginManager->suggestProperties($this->getEntity());
    $property_name = $form_state->getValue('property_name');
    $this->getEntity()->setTypedProperty($property_name, $suggested_properties[$property_name]);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\collect\Model\ModelInterface $entity */
    // Make sure the static, value-less property table does not overwrite the
    // stored property definitions.
    $form_state->unsetValue('properties');
    // For new models, add suggested properties.
    if ($entity->isNew()) {
      $entity->setTypedProperties($this->pluginManager->suggestProperties($entity));
    }
    parent::copyFormValuesToEntity($entity, $form, $form_state);
  }

  /**
   * Determines if the model already exists.
   *
   * @param string $id
   *   The model ID.
   *
   * @return bool
   *   TRUE if the plugin config exists, FALSE otherwise.
   */
  public function exists($id) {
    return (!is_null($this->storage->load($id)));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message($this->t('Model %label has been saved.', ['%label' => $this->getEntity()->label()]));
    if ($container = $form_state->get('collect_container')) {
      /** @var \Drupal\collect\CollectContainerInterface $container */
      $form_state->setRedirectUrl($container->urlInfo());
    }
    else {
      $form_state->setRedirect('entity.collect_model.collection');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (\Drupal::entityQuery('collect_model')->condition('uri_pattern', $form_state->getValue('uri_pattern'))->execute() && $this->getEntity()->isNew()) {
      $form_state->setErrorByName('uri_pattern', $this->t('The URI pattern must be unique!'));
    }
  }

  /**
   * Ajax callback to replace the plugin info box.
   */
  public function replacePluginInfo(array &$form, FormStateInterface $form_state) {
    return $form['plugin_info']['plugin'];
  }

  /**
   * Returns the edited model.
   *
   * @return \Drupal\collect\Model\ModelInterface
   *   The model that the form applies to.
   */
  public function getEntity() {
    // Override just to modify documentation.
    return parent::getEntity();
  }

}
