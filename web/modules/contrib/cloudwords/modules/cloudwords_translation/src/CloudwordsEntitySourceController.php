<?php

namespace Drupal\cloudwords_translation;

use Drupal\cloudwords\CloudwordsSourceControllerInterface;

class CloudwordsEntitySourceController implements CloudwordsSourceControllerInterface {

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
    // @todo entityManager is deprecated, change
    $bundles = \Drupal::entityManager()->getAllBundleInfo();
    if(isset($bundles[$translatable->getTextGroup()]) && isset($bundles[$translatable->getTextGroup()][$translatable->getBundle()])){
      return $bundles[$translatable->getTextGroup()][$translatable->getBundle()]['label'];
    }
    return $translatable->getBundle();
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
   * Implements CloudwordsSourceControllerInterface::data().
   */
  public function data(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {
    $entity = \Drupal::entityTypeManager()->getStorage($translatable->getType())->load($translatable->getObjectId());

    // Get all the fields that can be translated and arrange their values into
    // a specific structure.
    $structure = ['#label' => $translatable->typeLabel()];
    //$structure['entity_title']['#label'] = $translatable->getType();
    $structure['entity_title']['#label'] = 'Title';

    $entity_type = $entity->getEntityType();
    $label_key = $entity_type->getKey('label');

    $structure['entity_title']['#text'] = $entity->get($label_key)->value;
    $structure += cloudwords_field_get_source_data($translatable->getType(), $entity, TRUE);
    return $structure;
  }

  /**
   * Implements CloudwordsSourceControllerInterface::save().
   */
  public function save(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {
    if ($entity = \Drupal::entityTypeManager()->getStorage($translatable->getType())->load($translatable->getObjectId())) {
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
  protected function updateEntityTranslation($entity, array $data, $language, $entityStatus, $entity_type) {
    if (isset($data['entity_title']['#translation']['#text'])) {
      $entity_title = $data['entity_title']['#translation']['#text'];
      unset($data['entity_title']);
    }

    //@todo update translation status

    //@todo add presave hook
    //cloudwords_translation_before_entity_save($entity, $data, $entity->language, 'entity_translation');


    $translated_fields = [];
    cloudwords_field_populate_entity($entity, $data, $language, $entity->language()->getId(), $translated_fields);

    if(!$entity->hasTranslation($language)){
      $entity->addTranslation($language);
    }
    $translation = $entity->getTranslation($language);

    // set the label - different between entity types
    $entity_type = $entity->getEntityType();
    $label_key = $entity_type->getKey('label');
    $translation->set($label_key, $entity_title);

    foreach($translated_fields as $name => $value) {
      $translation->$name = $value;
    }
    $translation->save();

  //@todo handle attached entities managed by entity revisions such as paragraphs and field collections
    cloudwords_field_populate_attached_entities($entity_type, $entity, $data, $language, $entity->language()->getId());
  }
}
