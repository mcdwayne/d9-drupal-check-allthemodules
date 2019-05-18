<?php

namespace Drupal\cloudwords_config_translation;

use Drupal\cloudwords\CloudwordsSourceControllerInterface;
use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Config\Schema\Sequence;
use Drupal\config_translation\Form\ConfigTranslationFormBase;
use Drupal\Core\Render\Element;

class CloudwordsConfigEntitySourceController implements CloudwordsSourceControllerInterface {

  protected $objectInfo = [];
  protected $stringInfo = [];
  protected $bundle = [];
  protected $type;

  /**
   * Implements CloudwordsSourceControllerInterface::__construct().
   */
  public function __construct($type) {
    $this->type = $type;
  }

  /**
   * Implements CloudwordsSourceControllerInterface::typeLabel().
   */
  public function typeLabel() {
    return 'Entity';
  }

  /**
   * Implements CloudwordsSourceControllerInterface::textGroup().
   */
  public function textGroup() {
    return 'entity_bundle';
  }

  /**
   * Implements CloudwordsSourceControllerInterface::textGroupLabel().
   */
  public function textGroupLabel() {
    //TODO need to get the label of the entity bundle
    return 'Entity Bundle Label';
  }

  /**
   * Implements CloudwordsSourceControllerInterface::targetLabel().
   */
  public function targetLabel(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {
    $translations = translation_node_get_translations($translatable->objectid);
    if (isset($translations[$translatable->language])) {
      return $translations[$translatable->language]->title;
    }
    return $translatable->label;
  }

  /*
  * Implements CloudwordsSourceControllerInterface::bundleLabel().
  */
  public function bundleLabel(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {

    return $translatable->getTextGroup();
    // @todo entityManager is deprecated, change
//    $bundles = \Drupal::entityManager()->getAllBundleInfo();
//    if(isset($bundles[$translatable->getTextGroup()]) && isset($bundles[$translatable->getTextGroup()][$translatable->getBundle()])){
//      return $bundles[$translatable->getTextGroup()][$translatable->getBundle()]['label'];
//    }
//    return $translatable->getBundle();
  }

  /**
   * Implements CloudwordsSourceControllerInterface::uri().
   */
  public function uri(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {
    $entity = \Drupal::entityTypeManager()->getStorage($translatable->getType())->load($translatable->getObjectId());
    return [
      'path' =>$entity->url(),
    ];
  }

  /**
   * @param $schema
   */
  protected function extractTranslatableStrings($schema, $config_data, $base_key = '') {
    $data = array();
    foreach ($schema as $key => $element) {
      $element_key = isset($base_key) ? "$base_key.$key" : $key;
      $definition = $element->getDataDefinition();
      if ($element instanceof Mapping || $element instanceof Sequence) {
        $sub_data = $this->extractTranslatableStrings($element, $config_data[$key], $element_key);

        if ($sub_data) {
          $data[$key] = $sub_data;
          $data[$key]['#label'] = $definition->getLabel();
        }
      } else {
        if (!isset($definition['translatable']) || !isset($definition['type']) || empty($config_data[$key])) {
          continue;
        }
        $data[$key] = array(
          '#label' => $definition['label'],
          '#text' => $config_data[$key],
          '#translate' => TRUE,
        );
      }
    }
    return $data;
  }

  /**
   * Converts a translated data structure. We convert it.
   *
   * @param array $data
   *   The translated data structure.
   *
   * @return array
   *   Returns a translation array as expected by
   *   \Drupal\config_translation\FormElement\ElementInterface::setConfig().
   * Converts a translated data structure. We convert it.
   *
   * @param array $data
   *   The translated data structure.
   *
   * @return array
   *   Returns a translation array as expected by
   *   \Drupal\config_translation\FormElement\ElementInterface::setConfig().
   *
   */
  public function convertToTranslation($data) {
    $children = Element::children($data);
    if ($children) {
      $translation = array();
      foreach ($children as $name) {
        $property_data = $data[$name];
        $translation[$name] = $this->convertToTranslation($property_data);
      }
      return $translation;
    }
    elseif (isset($data['#translation']['#text'])) {
      return $data['#translation']['#text'];
    }
  }

  /**
   * Implements CloudwordsSourceControllerInterface::data().
   */
  public function data(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {
    $entity = \Drupal::entityTypeManager()->getStorage($translatable->getType())->load($translatable->getTextGroup());
    $configMapperManager = \Drupal::service('plugin.manager.config_translation.mapper');
    $typedConfig = \Drupal::service('config.typed');
    $config_mapper = $configMapperManager->createInstance($translatable->getType());
    $config_mapper->setEntity($entity);
    $data = array();
    foreach ($config_mapper->getConfigData() as $config_id => $config_data) {
      $schema = $typedConfig->get($config_id);
      $config_id = str_replace('.', '__', $config_id);
      $data[$config_id] = $this->extractTranslatableStrings($schema, $config_data);
    }


    $structure = ['#label' => 'Config Entity'];
    //$structure['entity_title']['#label'] = $translatable->getType();
    //$structure['entity_title']['#label'] = 'Label';
//
    //$entity_type = $entity->getEntityType();
    //$label_key = $entity_type->getKey('label');
//
   // $structure['entity_title']['#text'] = $entity->get($label_key);

    $structure += reset($data);

    return $structure;
  }

  /**
   * Implements CloudwordsSourceControllerInterface::save().
   */
  public function save(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {
    if ($entity = \Drupal::entityTypeManager()->getStorage($translatable->getType())->load($translatable->getTextGroup())) {
      $this->updateEntityTranslation($entity, $translatable->getData(), $translatable->getLanguage(), 1, $translatable->getType());
    }
  }

  /**
   * Updates an entity translation.
   *
   * @param stdClass $entity
   *   The translated entity object (the target).
   * @param array $data
   *   An array with the structured translated data.
   * @param string $language
   *   The target language.
   * @param string $entityStatus
   *   The target entity status.
   *
   * @see CloudwordsTranslatable::getData()
   */
  protected function updateEntityTranslation($entity, array $data, $language, $entityStatus, $entity_type){
    $configMapperManager = \Drupal::service('plugin.manager.config_translation.mapper');
    $typedConfig = \Drupal::service('config.typed');
    $languageManager = \Drupal::service('language_manager');
    $configFactoryManager = \Drupal::service('config.factory');

    $config_mapper = $configMapperManager->createInstance($entity_type);
    $config_mapper->setEntity($entity);

    foreach ($config_mapper->getConfigNames() as $name) {
      $schema = $typedConfig->get($name);

      // Set configuration values based on form submission and source values.
      $base_config = $configFactoryManager->getEditable($name);
      $config_translation = $languageManager->getLanguageConfigOverride($language, $name);

      $element = ConfigTranslationFormBase::createFormElement($schema);

      $element->setConfig($base_config, $config_translation, $this->convertToTranslation($data));

      // If no overrides, delete language specific configuration file.
      $saved_config = $config_translation->get();
      if (empty($saved_config)) {
        $config_translation->delete();
      } else {
        $config_translation->save();
      }
    }
  }
}
