<?php

namespace Drupal\reference_map\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\reference_map\Entity\ReferenceMapConfigInterface;
use Drupal\reference_map\Plugin\ReferenceMapTypeManagerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Reference Map add and edit forms.
 */
class ReferenceMapConfigForm extends ValidationEntityForm {

  /**
   * An instance of the plugin associated with this form.
   *
   * @var \Drupal\reference_map\Plugin\ReferenceMapTypeBase
   */
  protected $pluginInstance = NULL;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Reference Map Type Manager.
   *
   * @var \Drupal\reference_map\Plugin\ReferenceMapTypeManagerInterface
   */
  protected $referenceMapTypeManager;

  /**
   * ReferenceMapConfigForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager.
   * @param \Drupal\reference_map\Plugin\ReferenceMapTypeManagerInterface $reference_map_type_manager
   *   The Reference Map Type Manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ReferenceMapTypeManagerInterface $reference_map_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->referenceMapTypeManager = $reference_map_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.reference_map_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\reference_map\Entity\ReferenceMapConfigInterface $reference_map */
    $reference_map = $this->entity;
    $wrapper = Html::cleanCssIdentifier($this->getFormId()) . '-ajax-wrapper';
    $instance = $this->getPluginInstance($form_state, TRUE);

    // Wrap the form for ajax updating.
    $form['#prefix'] = '<div id="' . $wrapper . '">';
    $form['#suffix'] = '</div>';

    // Get the plugin types as the type field.
    $options = [];
    $definitions = $this->referenceMapTypeManager->getDefinitions();
    foreach ($definitions as $definition) {
      $options[$definition['id']] = $definition['title']->__toString();
    }

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#description' => $instance ? $instance->getPluginDefinition()['help'] : '',
      '#options' => $options,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $instance ? $instance->getPluginId() : NULL,
      '#required' => TRUE,
      '#disabled' => !$reference_map->isNew(),
      '#ajax' => [
        'callback' => '::typeCallback',
        'event' => 'change',
        'wrapper' => $wrapper,
      ],
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $reference_map->label(),
      '#description' => $this->t("Label for the Reference Map."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $reference_map->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$reference_map->isNew(),
    ];

    $form['map'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Map'),
      '#description' => $this->t('The map as yaml.'),
      '#default_value' => $reference_map->__toString(),
      '#rows' => 5,
      '#required' => TRUE,
    ];

    // Let plugins change the form.
    if ($instance) {
      $instance->configFormAlter($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure the map field contains valid yaml and convert it to a PHP array.
    try {
      $form_state->setValue('map', Yaml::parse($form_state->getValue('map')));
    }
    catch (ParseException $e) {
      $this->form_state->setErrorByName('map', "The map doesn't contain valid yaml. Error: %error", [
        '%error' => $e->getMessage(),
      ]);
    }

    parent::validateForm($form, $form_state);

    // Let plugins validate the form.
    if ($instance = $this->getPluginInstance($form_state)) {
      $instance->configFormValidate($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.reference_map_config.collection');

    // Let plugins presave the map.
    $instance = $this->getPluginInstance($form_state);
    if ($instance) {
      $instance->configFormPreSave($form, $form_state);
    }

    // Save the reference map config entity.
    $this->entity->save();

    drupal_set_message($this->t('Saved the %label Reference Map.', [
      '%label' => $this->entity->label(),
    ]));
  }

  /**
   * Checks to see if an entity exists with the given machine name.
   *
   * This method is used to validate the machine name given to new entities.
   *
   * @param string $id
   *   The machine name to check.
   *
   * @return bool
   *   Whether the machine name is valid.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function exist($id) {
    $entity = $this->entityTypeManager
      ->getStorage('reference_map_config')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function getTitle(ReferenceMapConfigInterface $reference_map_config = NULL) {
    if ($reference_map_config) {
      // As this runs statically, load this service directly.
      $definition = \Drupal::service('plugin.manager.reference_map_type')
        ->getDefinition($reference_map_config->get('type'));
      return t('Edit %type Reference Map %map', [
        '%type' => $definition['title'],
        '%map' => $reference_map_config->label(),
      ]);
    }

    return t('Create New Reference Map');
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // Allow plugins to change the actions.
    if ($instance = $this->getPluginInstance($form_state)) {
      $instance->configFormActions($form, $form_state, $actions);
    }

    return $actions;
  }

  /**
   * Procures an instance of the plugin that uses this config entity.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param bool $reset
   *   Whether or not to get a new plugin instance.
   *
   * @return \Drupal\reference_map\Plugin\ReferenceMapTypeBase|null
   *   The plugin instance.
   */
  protected function getPluginInstance(FormStateInterface $form_state, $reset = FALSE) {
    if (!$this->pluginInstance || $reset) {
      // Try to get the Reference Map Type plugin id, first from the form's
      // entity, and second from from the form state's values, and exit if it
      // couldn't be found.
      $plugin_id = $this->entity->type ?: $form_state->getValue('type');
      if (!$plugin_id) {
        return;
      }

      $configuration = [
        'plugin_id' => $plugin_id,
      ];

      // Try to get the Reference Map Config entity id from the form's entity.
      if (!$this->entity->isNew()) {
        $configuration['map_id'] = $this->entity->id();
      }

      $this->pluginInstance = $this
        ->referenceMapTypeManager
        ->getInstance($configuration);
    }

    return $this->pluginInstance;
  }

  /**
   * Ajax type callback.
   *
   * @param array $form
   *   The form to return.
   *
   * @return array
   *   The form.
   */
  public function typeCallback(array $form) {
    return $form;
  }

}
