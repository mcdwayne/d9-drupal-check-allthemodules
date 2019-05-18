<?php

namespace Drupal\config_form\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\Schema\TypedConfigInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;


/**
 * Provides a form for exporting a single configuration file.
 */
class ConfigForm extends FormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Tracks the valid config entity type definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $definitions = [];

  /**
   * Constructs a new ConfigSingleImportForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage.
   */
  public function __construct(EntityManagerInterface $entity_manager, StorageInterface $config_storage) {
    $this->entityManager = $entity_manager;
    $this->configStorage = $config_storage;
    $this->definitions = $this->definitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('config.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_form_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config_type = NULL, $config_name = NULL) {
    $form['config_forms'] = [
      '#type' => 'vertical_tabs',
      // '#default_tab' => 'edit-publication',
    ];
    /** @var TypedConfigInterface $config_typed */
    $config_typed = \Drupal::getContainer()->get('config.typed');
    /* @var ConfigFactoryInterface $config_factory */
    $config_factory = \Drupal::getContainer()->get('config.factory');
    foreach ($this->getFormConfig() as $id => $config_name) {
      $elements = [];
      $config_values = $config_factory->get($id);
      $scheme = $config_typed->get($config_name);
      $this->createFormElement($scheme, $config_values, $elements);
      $form[$id] = $elements;
    }
    return $form;
  }

  /**
   * Creates a form element builder.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $schema
   *   Schema definition of configuration.
   *
   * @return array
   *   The element builder object if possible.
   */
  public static function createFormElement(TypedDataInterface $schema, $values, array &$element = [], $group = 'config_forms') {
    $definition = $schema->getDataDefinition();
    $name = $schema->getName();
    if ($name == '_core') {
      return NULL;
    }
    switch ($definition['class']) {
      case 'Drupal\Core\Config\Schema\Sequence':
      case 'Drupal\Core\Config\Schema\Mapping':
        $element[$name] = [
          '#type' => 'details',
          '#title' => $definition['label'],
          '#group' => $group,
        ];
        $properties = $schema->getProperties();
        foreach ($properties as $property) {
          if (is_object($values)) {
            $element_values = $values->get($name);
          }
          else {
            $element_values = $values[$name];
          }
          self::createFormElement($property, $element_values, $element[$name], $name);
        }
        break;

      case 'Drupal\Core\TypedData\Plugin\DataType\BooleanData':
        $element[$name] = [
          '#type' => 'checkbox',
          '#title' => $definition['label'],
          '#group' => $group,
          '#default_value' => $values[$name],
        ];
        break;

      case 'Drupal\Core\TypedData\Plugin\DataType\Email':
        $element[$name] = [
          '#type' => 'email',
          '#title' => $definition['label'],
          '#group' => $group,
          '#default_value' => $values[$name],
        ];
        break;

      case 'Drupal\Core\TypedData\Plugin\DataType\Any':
      case 'Drupal\Core\TypedData\Plugin\DataType\IntegerData':
      case 'Drupal\Core\TypedData\Plugin\DataType\StringData':
      case 'Drupal\Core\TypedData\Plugin\DataType\FloatData':
        $element[$name] = [
          '#type' => 'textfield',
          '#title' => $definition['label'],
          '#default_value' => $values[$name],
          '#group' => $group,
        ];
        break;
    }
  }

  /**
   * Helper to get entity definitions.
   *
   * @return array
   *   An array of entity types.
   */
  protected function definitions() {
    $definitions = [];
    foreach ($this->entityManager->getDefinitions() as $entity_type => $definition) {
      if ($definition->entityClassImplements(ConfigEntityInterface::class)) {
        $definitions[$entity_type] = $definition;
      }
    }
    return $definitions;
  }

  /**
   * @return array
   */
  protected function getFormConfig() {
    // Gather the config entity prefixes.
    $config_prefixes = array_map(function (EntityTypeInterface $definition) {
      return $definition->getConfigPrefix() . '.';
    }, $this->definitions);
    // Find all config, and then filter our anything matching a config prefix.
    $names = $this->configStorage->listAll();
    $names = array_combine($names, $names);
    foreach ($names as $config_name) {
      foreach ($config_prefixes as $config_prefix) {
        if (strpos($config_name, $config_prefix) === 0 || strpos($config_name, 'core.') === 0) {
          unset($names[$config_name]);
        }
      }
    }
    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

}
