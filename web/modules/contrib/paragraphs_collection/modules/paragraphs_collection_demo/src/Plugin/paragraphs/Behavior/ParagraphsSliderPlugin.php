<?php

namespace Drupal\paragraphs_collection_demo\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\slick\Entity\Slick;
use Drupal\slick\SlickManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Slider plugin.
 *
 * @ParagraphsBehavior(
 *   id = "slider",
 *   label = @Translation("Slider"),
 *   description = @Translation("Content slider for paragraphs."),
 *   weight = 0
 * )
 */
class ParagraphsSliderPlugin extends ParagraphsBehaviorBase implements ContainerFactoryPluginInterface {

  /**
   * The slick manager.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $slickManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * ParagraphsSliderPlugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\slick\SlickManagerInterface $slick_manager
   *   The slick manager service.
   * @param \Drupal\core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user for permissions scope.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager, SlickManagerInterface $slick_manager, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);
    $this->slickManager = $slick_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('slick.manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $paragraphs_type = $form_state->getFormObject()->getEntity();

    if ($paragraphs_type->isNew()) {
      return [];
    }

    $field_name_options = $this->getFieldsByCardinalityGreaterOne($paragraphs_type);

    // Show field mapping select form only if this entity has at least
    // one mappable field.
    if ($field_name_options) {
      $form['field_name'] = [
        '#type' => 'select',
        '#title' => $this->t('Slider field'),
        '#description' => $this->t('Choose the field to be used as slider items.'),
        '#options' => $field_name_options,
        '#default_value' => $this->configuration['field_name'],
      ];
    }
    else {
      $form['message'] = [
        '#type' => 'container',
        '#markup' => $this->t('There are no fields available with the cardinality greater than one. Please add at least one in the <a href=":link">Manage fields</a> page.', [
          ':link' => Url::fromRoute("entity.{$paragraphs_type->getEntityType()->getBundleOf()}.field_ui_fields", [$paragraphs_type->getEntityTypeId() => $paragraphs_type->id()])
            ->toString(),
        ]),
        '#attributes' => [
          'class' => ['messages messages--error'],
        ],
      ];

    }

    $form['slick_slider'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Slick Optionsets'),
      '#description' => $this->getOptionsetDescription($this->getAllOptionSet()),
      '#options' => $this->getAllOptionSet(),
      '#default_value' => $this->configuration['slick_slider'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('field_name')) {
      $form_state->setErrorByName('message', $this->t('The Slider plugin cannot be enabled if there is no field to be mapped.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['field_name'] = $form_state->getValue('field_name');

    // Only save the selected checkbox options to the config
    // and convert it to ['machine_name' => 'Label'].
    $slider_optionsets = array_intersect_key($this->getAllOptionSet(), array_flip($form_state->getValue('slick_slider')));

    $this->configuration['slick_slider'] = array_keys($slider_optionsets);
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraphs_entity, array &$form, FormStateInterface $form_state) {
    $options = $this->getAllOptionSet();
    $slider_optionset = $this->getConfiguration()['slick_slider'];
    $default_value = $paragraphs_entity->getBehaviorSetting($this->getPluginId(), 'slick_slider');

    if (!empty($slider_optionset)) {
      // Filter optionsets with preselected optionsets.
      $options = array_intersect_key($options, array_flip($slider_optionset));
    }
    elseif (count($options) === 1 && empty($default_value)) {
      // Preselect the only possible option.
      $keys = array_keys($options);
      $default_value = $keys[0];
    }

    $form['slick_slider'] = [
      '#type' => 'select',
      '#title' => $this->t('Slider'),
      '#options' => $options,
      '#description' => $this->t('Slider effect used to display the paragraph.'),
      '#default_value' => $default_value,
      '#required' => TRUE,
    ];

    if (!in_array($default_value, array_keys($options))) {
      $form['slick_slider']['#empty_option'] = $this->t('- None -');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $paragraph->setBehaviorSettings($this->getPluginId(), $form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $elements = [];
    $field_name = $this->getConfiguration()['field_name'];
    $slider_optionset = $paragraph->getBehaviorSetting($this->getPluginId(), 'slick_slider');

    if (isset($build[$field_name])) {
      $field_slides = Element::children($build[$field_name]);

      foreach ($field_slides as $delta) {
        $elements['items'][]['slide'] = $build[$field_name][$delta];
      }
    }

    if ($slider_optionset) {
      $elements['options'] = $this->getOptionSet($slider_optionset);
    }

    $build[$field_name] = $this->slickManager->build($elements);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $slider_optionset = $paragraph->getBehaviorSetting('slider', 'slick_slider');
    $all_optionset = $this->getAllOptionSet();
    $summary = [];

    if (in_array($slider_optionset, array_flip($all_optionset))) {
      $summary = [
        [
          'label' => $this->t('Slider settings'),
          'value' => $all_optionset[$slider_optionset]
        ]
      ];
    }

    return $summary;
  }

  /**
   * Returns an array of all available slider options.
   *
   * @return array
   *   All available optionsets, e.g. ['machine_name' => 'Label'].
   */
  public function getAllOptionSet() {
    /** @var \Drupal\slick\Entity\Slick[] $entities */
    $entities = $this->slickManager->entityLoadMultiple('slick');
    $option_sets = [];

    foreach ($entities as $options_set_id => $option_set) {
      $option_sets[$option_set->id()] = $option_set->label();
    }

    return $option_sets;
  }

  /**
   * Returns the optionset that matches given name.
   *
   * @param string $name
   *   The optionset name to be found.
   *
   * @return array
   *   The requested optonset.
   */
  public function getOptionSet($name) {
    /** @var \Drupal\slick\Entity\Slick $entity */
    $entity = $this->slickManager->entityLoad($name, 'slick');

    return $entity ? $entity->getSettings() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'field_name' => '',
      'slick_slider' => [],
    ];
  }

  /**
   * Returns all fields that have cardinality greater than one.
   *
   * @param \Drupal\paragraphs\Entity\ParagraphsType $paragraphs_type
   *
   * @return array
   *   A list of fields of the paragraph type,
   *   e.g. ['field_slides' => 'Slides', 'field_texts' => 'Texts'].
   */
  protected function getFieldsByCardinalityGreaterOne(ParagraphsType $paragraphs_type) {
    $fields = [];
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('paragraph', $paragraphs_type->id());

    foreach ($field_definitions as $name => $definition) {
      if ($field_definitions[$name] instanceof FieldConfigInterface) {
        $cardinality = $definition->getFieldStorageDefinition()->getCardinality();

        if ($cardinality === 1) {
          continue;
        }

        $fields[$name] = $definition->getLabel();
      }
    }

    return $fields;
  }

  /**
   * Returns description about slick optionsets module.
   *
   * @param array $slick_optionset_options
   *   Available slick optionsets.
   *
   * @return string
   *   Description for slick_slider field.
   */
  protected function getOptionsetDescription($slick_optionset_options) {
    if (empty($slick_optionset_options)) {
      return [$this->t('There are no Slick optionsets available.')];
    }

    $description = $this->t('Select none, to show all.');
    $enable_link = Url::fromRoute('system.modules_list');
    $slick_link = Url::fromRoute('entity.slick.collection');

    if ($this->moduleHandler->moduleExists('slick_ui')) {
      if ($slick_link->access($this->currentUser)) {
        $description =
          $this->t('Select none, to show all. To have more options, go to the <a href=":link">Slick UI config page</a> and add items there.', [
            ':link' => $slick_link->toString(),
          ]);
      }
    }
    else {
      if ($enable_link->access($this->currentUser)) {
        $description =
          $this->t('Select none, to show all. Enable the <a href=":link">Slick UI</a> from the module list to create more options.', [
            ':link' => $enable_link->toString(),
          ]);
      }
    }

    return $description;
  }

}
