<?php

/**
 * @file
 * Contains \Drupal\quickscript\Entity\QuickScript.
 */

namespace Drupal\quickscript\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\quickscript\QuickScriptInterface;
use Drupal\user\UserInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines the Quick Script entity.
 *
 * @ingroup quickscript
 *
 * @ContentEntityType(
 *   id = "quickscript",
 *   label = @Translation("Quick Script"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\quickscript\QuickScriptListBuilder",
 *
 *     "form" = {
 *       "default" = "Drupal\quickscript\Form\QuickScriptForm",
 *       "add" = "Drupal\quickscript\Form\QuickScriptForm",
 *       "edit" = "Drupal\quickscript\Form\QuickScriptForm",
 *       "delete" = "Drupal\quickscript\Form\QuickScriptDeleteForm",
 *     },
 *     "access" = "Drupal\quickscript\QuickScriptAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\quickscript\QuickScriptHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "quickscript",
 *   admin_permission = "administer quick script entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/development/quickscript/add",
 *     "execute" = "/admin/config/development/quickscript/{quickscript}/run",
 *     "edit-form" =
 *   "/admin/config/development/quickscript/{quickscript}/edit",
 *     "delete-form" =
 *   "/admin/config/development/quickscript/{quickscript}/delete",
 *     "collection" = "/admin/config/development/quickscript",
 *   },
 *   field_ui_base_route = "quickscript.settings"
 * )
 */
class QuickScript extends ContentEntityBase implements QuickScriptInterface {

  use EntityChangedTrait;

  const CRON_NEVER = 0;

  const CRON_EVERY_TIME = 1;

  const CRON_EVERY_1HOUR = 2;

  const CRON_EVERY_3HOURS = 3;

  const CRON_EVERY_6HOURS = 4;

  const CRON_EVERY_12HOURS = 5;

  const CRON_EVERY_1DAY = 6;

  public $yaml_errors;

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
   * Loads a quick script by it's machine name.
   *
   * @param string $machine_name
   *
   * @return QuickScript[]
   */
  public static function loadByMachineName($machine_name) {
    $entities = \Drupal::entityTypeManager()
      ->getStorage('quickscript')
      ->loadByProperties(['machine_name' => $machine_name]);
    if (is_array($entities) && count($entities) === 1) {
      return reset($entities);
    }
    return $entities;
  }

  /**
   * Loads all cron enabled scripts for processing.
   *
   * @return QuickScript[]
   */
  public static function loadCronEnabled() {
    $query = \Drupal::entityTypeManager()
      ->getStorage('quickscript')
      ->getQuery();
    $query->condition('cron_run', 0, '>');
    $result = $query->execute();
    if (is_array($result) && count($result)) {
      return self::loadMultiple($result);
    }
    return FALSE;
  }

  /**
   * Loads all QuickScripts.
   *
   * return QuickScript[]
   */
  public static function loadAll() {
    $query = \Drupal::entityTypeManager()
      ->getStorage('quickscript')
      ->getQuery();
    $result = $query->execute();
    return self::loadMultiple($result);
  }

  /**
   * Gets the code returned and decrypts it if necessary.
   *
   * @return string
   */
  public function getCode() {
    if ($this->encrypted->value) {
      return $this->decrypt();
    }
    return $this->code->value;
  }

  /**
   * Gets the parsed form YAML.
   *
   * @return array
   */
  public function getFormYaml() {
    $string = $this->form_yaml->value;
    return Yaml::parse($string);
  }

  /**
   * Encrypts the code.
   *
   * @return string|bool
   */
  public function encrypt() {
    $service = $this->getEncryptionService();
    if ($method = $this->getEncryptionMethod()) {
      return $service->encrypt($this->code->value, $this->getEncryptionMethod());
    }
    return FALSE;
  }

  /**
   * Decrypts the code.
   *
   * @return string|bool
   */
  public function decrypt() {
    $service = $this->getEncryptionService();
    if ($method = $this->getEncryptionMethod()) {
      return $service->decrypt($this->code->value, $this->getEncryptionMethod());
    }
    return FALSE;
  }

  /**
   * Gets the encryption services.
   *
   * @return \Drupal\encrypt\EncryptServiceInterface
   */
  private function getEncryptionService() {
    return \Drupal::service('encryption');
  }

  /**
   * Gets the encryption method.
   *
   * @return \Drupal\encrypt\EncryptionMethodInterface
   */
  private function getEncryptionMethod() {
    $config = \Drupal::config('quickscript.settings');
    if ($pid = $config->get('encrypt_code_profile')) {
      return \Drupal::entityTypeManager()
        ->getStorage('encryption_profile')
        ->load($pid);
    }
    return FALSE;
  }

  /**
   * Replaces "quickscript_include()" functions with the loaded code.
   *
   * @param string $code
   *
   * @return string
   *
   * @see quickscript_include()
   */
  public function replaceIncludes($code) {
    $matches = [];
    $num = preg_match_all('/quickscript_include\([\'|"](.*)[\'|"]\);/', $code, $matches);

    if (!$num) {
      return $code;
    }

    for ($i = 0; $i < $num; $i++) {
      $included_script = quickscript_load($matches[1][$i]);
      $included_code = $included_script->getCode();
      $included_code = $included_script->replaceIncludes($included_code);
      $code = str_replace($matches[0][$i], "\r\n" . $included_code . "\r\n", $code);
    }

    return $code;
  }

  /**
   * Executes the code in the code field and captures output.
   */
  public function execute() {
    ob_start();
    $this->evalCode();
    return ob_get_clean();
  }

  /**
   * Evals the code field.
   */
  public function evalCode() {
    $code = $this->getCode();
    $code = '$_QS=$_GET["qs"];unset($_GET["qs"]);' . PHP_EOL . $code;
    $code = $this->replaceIncludes($code);
    $result = eval($code);
    // Update the last_run timestamp.
    $this->last_run = \Drupal::time()->getRequestTime();
    $this->save();
    return $result;
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
   * {@inheritdoc}
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

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Quick Script entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Quick Script entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Quick Script entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
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
      ->setDescription(t('The name of this Quick Script.'))
      ->setSettings([
        'max_length' => 50,
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

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine Name'))
      ->setDescription(t('The machine name of this Quick Script.'))
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

    $fields['code'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Code'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -30,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -2,
        'rows' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['form_yaml'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('YAML Form Config'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -30,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -2,
        'rows' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['public_access'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Public Access'))
      ->setDescription(t('Whether this script is publicly accessible via an access token.'))
      ->setDefaultValue(FALSE);

    $fields['access_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Access Token'))
      ->setDescription(t('The access token needed to run the script.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('');

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Quick Script is published.'))
      ->setDefaultValue(TRUE);

    $fields['encrypted'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Encrypted'))
      ->setDescription(t('Whether this Quick Script is encrypted.'))
      ->setDefaultValue(FALSE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Quick Script entity.'))
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['cron_run'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cron Run'))
      ->setDescription(t('A number indicating the cron run status of the Quick Script.'))
      ->setDefaultValue(0);

    $fields['last_run'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Run'))
      ->setDescription(t('The time that the script was last run.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
