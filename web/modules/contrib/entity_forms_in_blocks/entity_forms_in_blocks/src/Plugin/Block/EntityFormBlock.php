<?php

namespace Drupal\entity_forms_in_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;

/**
 * Provides a block for entity forms.
 *
 * @Block(
 *   id = "entity_forms_in_blocks",
 *   admin_label = @Translation("Entity form in block")
 * )
 */
class EntityFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface.
   */
  protected $entityFormBuilder;

  /**
   * Constructs a new EntityFormBlock plugin
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManger
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder
   *   The entity form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entityManager, EntityFormBuilderInterface $entityFormBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);

    $this->entityManager = $entityManager;
    $this->entityFormBuilder = $entityFormBuilder;
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
      $container->get('entity.form_builder')
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::settings().
   */
  public function defaultConfiguration() {
    return array(
      'entity_type' => NULL,
      'bundle' => NULL,
      'form_mode' => NULL,
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form_state->setAlwaysProcess(TRUE);
    $form['entity_type'] = [
      '#title' => $this->t('Entity type'),
      '#description' => $this->t('Select the entity type whose form will be shown in the block.'),
      '#type' => 'select',
      '#options' => $this->getSupportedEntityOptions(),
      '#default_value' => $this->configuration['entity_type'],
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxBundleCallback'],
        'wrapper' => 'formblock-bundle-container',
      ],
    ];
    $form['entity_type_configuration'] = array(
      '#type' => "hidden",
      '#value' => $this->configuration['entity_type'],
    );
    if ($form_state->hasValue('settings') && isset($form_state->getValue('settings')['entity_type'])) {
      $bundles = $this->getBundlesForEntity($form_state->getValue('settings')['entity_type']);
      if (isset($form_state->getValue('settings')['bundle'])) {
        $form_modes = self::filterFormModeByEntityType($form_state->getValue('settings')['entity_type'], $form_state->getValue('settings')['bundle']);
      }

    }
    else {
      if (!is_null($this->configuration['entity_type'])) {
        $bundles = $this->getBundlesForEntity($this->configuration['entity_type']);
        if (!is_null($this->configuration['bundle'])) {
          $form_modes = self::filterFormModeByEntityType($this->configuration['entity_type'], $this->configuration['bundle']);

        }
      }
      else {
        $bundles = $operations = [];
      }
    }


    $form['entity_dependent'] = [
      '#prefix' => '<div id="formblock-bundle-container">',
      '#suffix' => '</div>'
    ];

    $form['entity_dependent']['bundle'] = [
      '#title' => t('Bundle'),
      '#type' => 'select',
      '#options' => $bundles,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxFormModeCallback'],
        'wrapper' => 'formblock-form-mode-container',
      ],
      '#default_value' => $this->configuration['bundle'],
    ];


    $form['bundle_dependent'] = [
      '#prefix' => '<div id="formblock-form-mode-container">',
      '#suffix' => '</div>'
    ];

    $form['bundle_dependent']['form_mode'] = [
      '#title' => t('Form mode'),
      '#type' => 'select',
      '#options' => $form_modes,
      '#default_value' => $this->configuration['form_mode'],
    ];
    $form_state->setAlwaysProcess(TRUE);
    unset($form['#validate']);
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['entity_type'] = $form_state->getValue('entity_type');
    $this->configuration['bundle'] = $form_state->getValue('entity_dependent')['bundle'];
    $this->configuration['form_mode'] = $form_state->getValue('bundle_dependent')['form_mode'];
  }

  /**
   * Static callback for ajax form rebuild.
   * @param $form
   * @param FormStateInterface $form_state
   * @return mixed
   */
  public function ajaxBundleCallback($form, FormStateInterface $form_state) {
    //Load entity type by triggering elements.
    $entity_type = $form_state->getTriggeringElement()['#value'];
    $form['settings']['entity_dependent']['bundle']['#options'] = self::getBundlesForEntity($entity_type);
    unset($form['#validate']);
    return $form['settings']['entity_dependent'];

  }
  /**
   * Static callback for ajax form rebuild.
   * @param $form
   * @param FormStateInterface $form_state
   * @return mixed
   */
  public static function ajaxFormModeCallback($form, FormStateInterface $form_state) {
    //Load node by triggering elements.
    $bundle = $form_state->getTriggeringElement()['#value'];
    $entity = self::getEntityTypeFromBundle($bundle);
    $form['settings']['bundle_dependent']['form_mode']['#options'] = self::filterFormModeByEntityType($entity, $bundle);
    return $form['settings']['bundle_dependent'];

  }

  /**
   * Get the allowed operations for this form.
   */
  public function getFormOperations($entity_type) {
    $definition = $this->entityManager->getDefinition($entity_type);
    $handlers = $definition->getHandlerClasses();
    $form_operations = array_combine(array_keys($handlers['form']), array_keys($handlers['form']));
    $allowed_operations = $this->supportedOperations();
    return array_filter($form_operations, function ($operation) use ($allowed_operations) {
      return in_array($operation, $allowed_operations);
    });
  }

  /**
   * Return an array of form operations that this block does supports.
   */
  protected function supportedOperations() {
    return array('default', 'add', 'register');
  }

  /**
   * Get a list of bundles for a specific entity type.
   *
   * @param $entity_type
   *   The entity type.
   *
   * @return array
   *   A list of bundle labels keyed by bundle ID.
   */
  public static function getBundlesForEntity($entity_type) {
    $bundles = \Drupal::entityManager()->getBundleInfo($entity_type);

    return array_map(function ($bundle) {
      return $bundle['label'];
    }, $bundles);
  }

  /**
   * Get a list entity types that have form classes.
   *
   * @return \Drupal\Core\Entity\EntityType[]
   *   An array of entity types.
   */
  protected function getSupportedEntityTypes() {
    $definitions = $this->entityManager->getDefinitions();
    return array_filter($definitions, function ($entity_type) {
      return $entity_type->hasFormClasses();
    });
  }
  /**
   * Get a list of supported
   *
   * @return array
   *   List of entity type labels keyed by entity type ID.
   */
  protected function getSupportedEntityOptions() {
    $types = $this->getSupportedEntityTypes();

    $options = [];
    foreach ($types as $type) {
      $options[$type->get('id')] = $type->get('label');
    }
    asort($options);
    return $options;
  }


  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $values = [];
    //Load form mode selected.
    global $current_context;
    $current_context['entity_type'] = $this->configuration['entity_type'];
    $current_context['bundle'] = $this->configuration['bundle'];
    $current_context['display_name'] = $this->configuration['form_mode'];

    $definition = $this->entityManager->getDefinition($this->configuration['entity_type']);
    $bundle_key = $definition->get('entity_keys')['bundle'];
    if (!empty($bundle_key)) {
      $values[$bundle_key] = $this->configuration['bundle'];
      $values["form_mode"] = $this->configuration['form_mode'];
    }
    $entity = $this->entityManager->getStorage($definition->get('id'))
      ->create($values);
    return $this->entityFormBuilder->getForm($entity);
  }


  /**
   * Filter form modes which have permission by entity type, bundle.
   * @param $entity_type_id
   * @param $bundle_id
   * @return array
   *
   */
  static function  filterFormModeByEntityType($entity_type_id, $bundle_id) {
    //Load configuration.
    $storage = \Drupal::entityManager()->getStorage('entity_form_display');
    //Load all form modes.
    $form_modes_ids = $storage->loadMultiple();
    //Initialisation of an array to add form mode searched.
    $id_form_mode_searched = array();
    foreach ($form_modes_ids as $form_modes_id => $form_mode_configuration) {
      $aux = explode(".", $form_modes_id);
      $entity_type_to_filter = $aux[0];
      $bundle_id_to_filter = $aux[1];
      // TODO: verify if add default display name or no.
      if ($entity_type_to_filter == $entity_type_id && $bundle_id_to_filter == $bundle_id) {
        if ($form_mode_configuration->status()) {
          $id_form_mode_searched[$aux[2]] = $aux[2];
        }
      }
    }
    return ($id_form_mode_searched);
  }

  /**
   * Get Entity type from bundle
   * @param $bundle_id
   * @return int|string
   */
  static function getEntityTypeFromBundle($bundle_id) {
    foreach (\Drupal::entityManager()
               ->getAllBundleInfo() as $entity_type => $bundles) {
      foreach ($bundles as $id_bundle => $label) {
        if ($bundle_id == $id_bundle) {
          return $entity_type;
          break;
        }
      }
    }
  }
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }
}

