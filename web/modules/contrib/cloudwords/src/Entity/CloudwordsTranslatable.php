<?php

namespace Drupal\cloudwords\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\NestedArray;

/**
 * Defines the Cloudwords translatable entity.
 *
 * @ingroup cloudwords
 *
 * @ContentEntityType(
 *   id = "cloudwords_translatable",
 *   label = @Translation("Cloudwords translatable"),
 *   module = "cloudwords",
 *   handlers = {
 *     "views_data" = "Drupal\cloudwords\Entity\CloudwordsTranslatableViewsData",
 *     "access" = "Drupal\cloudwords\CloudwordsTranslatableAccessControlHandler",
 *   },
 *   base_table = "cloudwords_translatable",
 *   admin_permission = "administer cloudwords translatable entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uid" = "user_id",
 *   }
 * )
 */
class CloudwordsTranslatable extends ContentEntityBase implements CloudwordsTranslatableInterface {

  use EntityChangedTrait;


/*  public $type, $textgroup, $ctid, $objectid,
    $language, $status, $translation_status, $label, $uid, $translated_document_id, $last_import;*/
  protected $defaultLabel;
  protected $translatableInfo;
  protected $sourceController;
  protected $data = [];
  protected $settings = [];
  /**
   * Overrides Entity::__construct().
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = []) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->translatableInfo = cloudwords_translatable_info($this->getType());
    $this->sourceController = cloudwords_get_source_controller($this->getType());
  }


  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->get('id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($name) {
    $this->set('type', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationModule() {
    return $this->get('translation_module')->value;
  }


  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}getDefinition
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  //@TODO documentation and proper formatters for getters, not just raw values;
  /**
   * {@inheritdoc}
   */
  public function getTextGroup() {
    return $this->get('textgroup')->value;
  }
  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->get('bundle')->value;
  }
  /**
   * {@inheritdoc}
   */
  public function getObjectId() {
    // @ content and interface translation returns and int whereas config is a string
    if($this->getTranslationModule() == CLOUDWORDS_CONFIG_TRANSLATION_TYPE){
      return $this->get('config_objectid')->value;
    }else{
      return $this->get('objectid')->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage() {
    return $this->get('language')->value;
  }


  public function getEditLink(){
    return $this->get('name')->value;
  }

  public function getTextGroupLabel(){
    return $this->get('textgroup')->value;
  }

  public function getTypeLabel(){
    return $this->get('type')->value;
  }

  public function getTranslatedDocumentId(){
    return $this->get('translated_document_id')->value;
  }

  public function getLastImport(){
    return $this->get('last_import')->value;
  }

  public function getTranslationStatusLabel(){
    $translation_statuses = cloudwords_exists_options_list();
    if (isset($translation_statuses[$this->get('translation_status')->value])) {
      return $translation_statuses[$this->get('translation_status')->value];
    }
    return $this->get('translation_status')->value;
  }

  public function textGroupLabel() {
    return $this->sourceController->textGroupLabel($this);
  }

  public function typeLabel() {
    return $this->sourceController->typeLabel($this);
  }

  public function bundleLabel() {
    return $this->sourceController->bundleLabel($this);
  }

  public function targetLabel() {
    return $this->sourceController->targetLabel($this);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Cloudwords translatable entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['textgroup'] = BaseFieldDefinition::create('string')
      ->setLabel(t('textgroup'))
      ->setDescription(t(''))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ]);

    $fields['objectid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('objectid'))
      ->setDescription(t(''))
      ->setSettings([
        'max_length' => 50,
      ]);

    $fields['config_objectid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Config Object ID'))
      ->setDescription(t(''))
      ->setSettings([
        'max_length' => 128,
        'text_processing' => 0,
      ]);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('type'))
      ->setDescription(t('Object type for this string'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ]);

    $fields['bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity bundle'))
      ->setDescription(t('The Entity bundle'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ]);

    $fields['language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('language'))
      ->setDescription(t('The language to translate to'))
      ->setSettings([
        'max_length' => 12,
      ]);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('status'))
      ->setDescription(t('The status of a queue item'))
      ->setDefaultValue(0)
      ->setSettings([
      'max_length' => 2,
    ]);

    $fields['translation_module'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Translation module'))
      ->setDescription(t('The translation module used to translate items'))
      ->setSettings([
        'max_length' => 25,
      ]);

    $fields['translation_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('translation_status'))
      ->setDescription(t(''))
      ->setDefaultValue(CLOUDWORDS_TRANSLATION_STATUS_NOT_TRANSLATED);

    $fields['translated_document_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('translated_document_id'))
      ->setDescription(t('Cloudwords translated document id.'))
      ->setSettings([
        'max_length' => 50,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['last_import'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Changed'))
      ->setDescription(t('The last import of this translatable from Cloudwords.'));

    return $fields;
  }

  public function cloudwordsLanguage() {
    module_load_include('inc', 'cloudwords', 'cloudwords.languages');
    $map = _cloudwords_map_drupal_cloudwords();
    return $map[$this->getLanguage()];
  }

  public function setProjectTranslationStatus($project, $status) {
    \Drupal::database()->merge('cloudwords_content')
      ->key([
        'pid' => $project->getId(),
        'ctid' => $this->id(),
      ])
      ->fields([
        'status' => $status,
      ])
      ->execute();
  }

  /**
   * Array of the data to be translated.
   *
   * The structure is similar to the form API in the way that it is a possibly
   * nested array with the following properties whose presence indicate that the
   * current element is a text that might need to be translated.
   *
   * - #text: The text to be translated.
   * - #label: (Optional) The label that might be shown to the translator.
   * - #comment: (Optional) A comment with additional information.
   * - #translate: (Optional) If set to FALSE the text will not be translated.
   * - #translation: The translated data. Set by the translator plugin.
   *
   * The key can be an alphanumeric string.
   * @param $key
   *   If present, only the subarray identified by key is returned.
   * @param $index
   *   Optional index of an attribute below $key.
   *
   * @return array
   *   A structured data array.
   */
  public function getData(array $key = [], $index = null) {
    //@todo use construct
    $this->translatableInfo = cloudwords_translatable_info($this->getType());
    $this->sourceController = cloudwords_get_source_controller($this->getType());

    if (empty($this->data)) {
      $this->data = $this->sourceController->data($this);
    }

    if (empty($key)) {
      return $this->data;
    }
    if ($index) {
      $key = array_merge($key, [$index]);
    }
    //return drupal_array_get_nested_value($this->data, $key);
    return NestedArray::getValue($this->data, $key);
  }

  public function saveData(array $data) {
    $this->addTranslatedDataRecursive($data);
    $this->sourceController->save($this);
  }

  public function setPreviewData(array $data) {
    $this->addTranslatedDataRecursive($data);
  }

  protected function addTranslatedDataRecursive($translation, array $key = []) {
    if (isset($translation['#text'])) {
      $values = [
        '#translation' => $translation,
      ];
      $this->updateData($key, $values);
      return;
    }
    foreach (Element::children($translation) as $item) {
      $this->addTranslatedDataRecursive($translation[$item], array_merge($key, [$item]));
    }
  }

  /**
   * Updates the values for a specific substructure in the data array.
   *
   * The values are either set or updated but never deleted.
   *
   * @param $key
   *   Key pointing to the item the values should be applied.
   *   The key can be either be an array containing the keys of a nested array
   *   hierarchy path or a string with '][' or '|' as delimiter.
   * @param $values
   *   Nested array of values to set.
   */
  public function updateData($key, $values = []) {
    foreach ($values as $index => $value) {
      // In order to preserve existing values, we can not aplly the values array
      // at once. We need to apply each containing value on its own.
      // If $value is an array we need to advance the hierarchy level.
      if (is_array($value)) {
        $this->updateData(array_merge(cloudwords_ensure_keys_array($key), [$index]), $value);
      }
      // Apply the value.
      else {
        //drupal_array_set_nested_value($this->data, array_merge(cloudwords_ensure_keys_array($key), array($index)), $value);
        NestedArray::setValue($this->data, array_merge(cloudwords_ensure_keys_array($key), [$index]), $value);
      }
    }
  }

}
