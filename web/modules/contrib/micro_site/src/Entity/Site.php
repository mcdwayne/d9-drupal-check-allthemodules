<?php

namespace Drupal\micro_site\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\file\FileInterface;
use Drupal\micro_site\CssFileStorage;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\UserInterface;
use Drupal\system\Entity\Menu;
use Drupal\micro_site\SiteUsers;

/**
 * Defines the Site entity.
 *
 * @ingroup micro_site
 *
 * @ContentEntityType(
 *   id = "site",
 *   label = @Translation("Site"),
 *   label_collection = @Translation("Sites"),
 *   label_singular = @Translation("Site item"),
 *   label_plural = @Translation("Site items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count site item",
 *     plural = "@count site items"
 *   ),
 *   bundle_label = @Translation("Site type"),
 *   handlers = {
 *     "storage" = "Drupal\micro_site\SiteStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\micro_site\SiteListBuilder",
 *     "views_data" = "Drupal\micro_site\Entity\SiteViewsData",
 *     "translation" = "Drupal\micro_site\SiteTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\micro_site\Form\SiteForm",
 *       "add" = "Drupal\micro_site\Form\SiteForm",
 *       "edit" = "Drupal\micro_site\Form\SiteForm",
 *       "delete" = "Drupal\micro_site\Form\SiteDeleteForm",
 *     },
 *     "access" = "Drupal\micro_site\SiteAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\micro_site\SiteHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "site",
 *   data_table = "site_field_data",
 *   revision_table = "site_revision",
 *   revision_data_table = "site_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer site entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *     "status" = "status",
 *     "registered" = "registered",
 *   },
 *   constraints = {
 *     "SiteUrlField" = {}
 *   },
 *   links = {
 *     "canonical" = "/site/{site}",
 *     "add-page" = "/site/add",
 *     "add-form" = "/site/add/{site_type}",
 *     "edit-form" = "/site/{site}/edit",
 *     "delete-form" = "/site/{site}/delete",
 *     "version-history" = "/site/{site}/revisions",
 *     "revision" = "/site/{site}/revisions/{site_revision}/view",
 *     "collection" = "/admin/content/site",
 *   },
 *   bundle_entity_type = "site_type",
 *   field_ui_base_route = "entity.site_type.edit_form"
 * )
 */
class Site extends RevisionableContentEntityBase implements SiteInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

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
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the site owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }

    // Register the complete url site for sub-domain.
    if ($this->isNew()) {
      $site_url = $this->getSiteUrl();
      if ($this->getTypeUrl() == 'subdomain') {
        $base_url = \Drupal::config('micro_site.settings')->get('base_url');
        $this->setSiteUrl($site_url . '.' . $base_url);
      }
      $site_url = $this->getSiteUrl();
      // Check a last time if there is not yet an existing site with the same url.
      $existing = $storage->loadByProperties(['site_url' => $site_url]);
      $existing = reset($existing);
      if ($existing) {
        throw new \Exception("The site url ($site_url) is already registered.");
      }
    }

    // Populate the site_menu id created for the first time in
    // the hook_entity_insert by calling the getter.
    if ($this->hasMenu()) {
      $this->getSiteMenu();
    }

    if ($this->hasVocabulary()) {
      $this->getSiteVocabulary();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {

    $asset_file_storage = new CssFileStorage($this);
    if ($this->isNew()) {
      $asset_file_storage->createFile();
    }
    else {
      $asset_file_storage->updateFile();
    }

    parent::postSave($storage, $update);

    // @TODO Create taxonomy associated, and default content for the front page.

    // Invalidate cache tags relevant to site.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['rendered', 'url.site']);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach ($entities as $entity) {
      // Delete asset files.
      $asset = new CssFileStorage($entity);
      $asset->deleteFiles();

      // Delete the menu if exists.
      if (!$entity->hasMenu()) {
        continue;
      }
      $menu = Menu::load('site-' . $entity->id());
      if ($menu) {
        $menu->delete();
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getHostBaseUrl() {
    $base_url = \Drupal::config('micro_site.settings')->get('base_url');
    if (empty($base_url)) {
      // Fallback to a pseudo hostname.
      // @Todo check if we can remove this.
      return 'micro.site';
    }
    return $base_url;
  }

  /**
   * {@inheritdoc}
   */
  public static function getHostPublicUrl() {
    $public_url = \Drupal::config('micro_site.settings')->get('public_url');
    if (empty($public_url)) {
      // Fallback to a pseudo hostname.
      // @Todo check if we can remove this.
      return 'www.micro.site';
    }
    return $public_url;
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
  public function getEmail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($mail) {
    $this->get('mail')->value = $mail;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlogan() {
    return $this->get('slogan')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSlogan($name) {
    $this->set('slogan', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogo() {
    return $this->get('logo')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setLogo(FileInterface $logo) {
    $this->set('logo', $logo->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFavicon() {
    return $this->get('favicon')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setFavicon(FileInterface $logo) {
    $this->set('favicon', $logo->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeUrl() {
    return $this->get('type_url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTypeUrl($type_url) {
    $this->set('type_url', $type_url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteUrl() {
    return $this->get('site_url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPiwikId($piwik_id) {
    $this->set('piwik_id', $piwik_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPiwikId() {
    return $this->get('piwik_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPiwikUrl($piwik_url) {
    $this->set('piwik_url', $piwik_url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPiwikUrl() {
    return $this->get('piwik_url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSiteUrl($site_url) {
    $this->set('site_url', $site_url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteMenu() {
    if (!$this->hasMenu()) {
      return NULL;
    }
    $site_menu = $this->get('site_menu')->value;
    if (empty($site_menu)) {
      $menu = Menu::load('site-' . $this->id());
      if ($menu) {
        $this->setSiteMenu($menu->id());
        return $menu->id();
      }
    }
    return $site_menu;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMenu() {
    $type = $this->bundle();
    $site_type = $this->entityTypeManager()->getStorage('site_type')->load($type);
    return $site_type->getMenu();
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteVocabulary() {
    if (!$this->hasVocabulary()) {
      return NULL;
    }
    $site_vocabulary = $this->get('site_vocabulary')->value;
    if (empty($site_vocabulary)) {
      $vocabulary = Vocabulary::load('site_' . $this->id());
      if ($vocabulary) {
        $this->setSiteVocabulary($vocabulary->id());
        return $vocabulary->id();
      }
    }
    return $site_vocabulary;
  }

  /**
   * {@inheritdoc}
   */
  public function hasVocabulary() {
    $type = $this->bundle();
    $site_type = $this->entityTypeManager()->getStorage('site_type')->load($type);
    return $site_type->getVocabulary();
  }

  /**
   * {@inheritdoc}
   */
  public function setSiteMenu($site_menu) {
    $this->set('site_menu', $site_menu);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSiteVocabulary($site_vocabulary) {
    $this->set('site_vocabulary', $site_vocabulary);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteScheme() {
    return ($this->get('site_scheme')->value) ? 'https' : 'http';
  }

  /**
   * {@inheritdoc}
   */
  public function setSiteScheme($site_scheme) {
    $https = ($site_scheme == 'https') ? 1 : 0;
    $this->set('site_scheme', $https);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteShield() {
    return $this->get('site_shield')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSiteShield($site_shield) {
    $this->set('site_shield', $site_shield);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteShieldUser() {
    return $this->get('shield_user')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSiteShieldUser($shield_user) {
    $this->set('shield_user', $shield_user);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteShieldPassword() {
    return $this->get('shield_password')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSiteShieldPassword($shield_password) {
    $this->set('shield_password', $shield_password);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSitePath() {
    return $this->getSiteScheme() . '://' . $this->getSiteUrl();
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
  public function getCss() {
    return $this->get('css')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCss($css) {
    $this->set('css', $css);
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
  public function isRegistered() {
    return (bool) $this->getEntityKey('registered');
  }

  /**
   * {@inheritdoc}
   */
  public function setRegistered($registered = NULL) {
    $this->set('registered', $registered ? TRUE : FALSE);
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
  public function setPublished($published = NULL) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers($field_name = SiteUsers::MICRO_SITE_ADMINISTRATOR) {
    if (!$this->hasField($field_name)) {
      return [];
    }
    return $this->get($field_name)->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getUsersId($field_name = SiteUsers::MICRO_SITE_ADMINISTRATOR, $return_entity = FALSE) {
    if (!$this->hasField($field_name)) {
      return [];
    }
    $bool = $return_entity ? 'true' : 'false';
    $list = &drupal_static(__FUNCTION__ . '_' . $field_name . '_' . $bool);
    if (is_null($list)) {
      $list = [];
      // Get the values of an entity.
      $values = $this->get($field_name);
      // Must be at least one item.
      if (!empty($values)) {
        foreach ($values as $item) {
          if ($target = $item->getValue()) {
            if ($user = $this->entityTypeManager()->getStorage('user')->load($target['target_id'])) {
              $list[$user->id()] = ($return_entity) ? $user : $user->id();
            }
          }
        }
      }
    }

    return $list;
  }

  /**
 * {@inheritdoc}
 */
  public function getAdminUsersId($return_entity = FALSE) {
    $bool = $return_entity ? 'true' : 'false';
    $list = &drupal_static(__FUNCTION__ . '_' . $bool);
    if (is_null($list)) {
      // Add the site owner.
      $list[$this->getOwnerId()] = $return_entity ? $this->getOwner() : $this->getOwnerId();
      // And check all the users field on the site.
      $field_names = [
        SiteUsers::MICRO_SITE_ADMINISTRATOR,
      ];
      foreach ($field_names as $field_name) {
        $list += $this->getUsersId($field_name, $return_entity);
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getManagerUsersId($return_entity = FALSE) {
    $bool = $return_entity ? 'true' : 'false';
    $list = &drupal_static(__FUNCTION__ . '_' . $bool);
    if (is_null($list)) {
      $list = [];
      $field_names = [
        SiteUsers::MICRO_SITE_MANAGER,
      ];
      foreach ($field_names as $field_name) {
        $list += $this->getUsersId($field_name, $return_entity);
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getContributorUsersId($return_entity = FALSE) {
    $bool = $return_entity ? 'true' : 'false';
    $list = &drupal_static(__FUNCTION__ . '_' . $bool);
    if (is_null($list)) {
      $list = [];
      $field_names = [
        SiteUsers::MICRO_SITE_CONTRIBUTOR,
      ];
      foreach ($field_names as $field_name) {
        $list += $this->getUsersId($field_name, $return_entity);
      }
    }
    return $list;
  }


  /**
   * {@inheritdoc}
   */
  public function getAllUsersId($return_entity = FALSE) {
    $bool = $return_entity ? 'true' : 'false';
    $list = &drupal_static(__FUNCTION__ . '_' . $bool);
    if (is_null($list)) {
      $list = [];
      // Add the site owner.
      $list[$this->getOwnerId()] = $return_entity ? $this->getOwner() : $this->getOwnerId();
      // And check all the users field on the site.
      $field_names = [
        SiteUsers::MICRO_SITE_ADMINISTRATOR,
        SiteUsers::MICRO_SITE_MANAGER,
        SiteUsers::MICRO_SITE_CONTRIBUTOR,
        SiteUsers::MICRO_SITE_MEMBER,
      ];
      foreach ($field_names as $field_name) {
        $list += $this->getUsersId($field_name, $return_entity);
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function cssInternalFileUri() {
    $storage = new CssFileStorage($this);
    return $storage->createFile();
  }

  /**
   * {@inheritdoc}
   */
  public function cssFilePathRelativeToDrupalRoot() {
    // @todo See if we can simplify this via file_url_transform_relative().
    $path = parse_url(file_create_url($this->cssInternalFileUri()), PHP_URL_PATH);
    $path = str_replace(base_path(), '/', $path);
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getData($key, $default = NULL) {
    $data = [];
    if (!$this->get('data')->isEmpty()) {
      $data = $this->get('data')->first()->getValue();
    }
    return isset($data[$key]) ? $data[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($key, $value) {
    $this->get('data')->__set($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $cache_tags = parent::getCacheTagsToInvalidate();
    $site_users = $this->getAllUsersId(TRUE);
    foreach ($site_users as $user) {
      $cache_tags = Cache::mergeTags($cache_tags, $user->getCacheTags());
    }
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The Site owner.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['registered'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Registered'))
      ->setDescription(t('You must register the site to be able to manage menu, content, etc. on it. The hostname DNS must be configured and valid to be able to register a site.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->addConstraint('RegisteredField')
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 119,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDescription(t('The hostname DNS must be configured and valid to be able to publish a site.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->addConstraint('StatusField')
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 120,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The site name.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 128,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The site email.'))
      ->setDefaultValue('')
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['slogan'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Slogan'))
      ->setDescription(t('The site slogan.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(FALSE)
      ->setSettings([
        'max_length' => 250,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['logo'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Logo'))
      ->setDescription(t('The site logo.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('file_directory', 'logo/[date:custom:Y]')
      ->setSetting('alt_field', FALSE)
      ->setSetting('file_extensions', 'png jpg jpeg')
      ->setSetting('max_resolution', '400x400')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'image',
        'weight' => 0,
        'settings' => [
          'image_style' => '',
          'image_link' => 'content',
        ],
      ))
      ->setDisplayOptions('form', array(
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => -10,
        'settings' => [
          'preview_image_style' => 'thumbnail',
          'progress_indicator' => 'throbber',
        ],
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['favicon'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Favicon'))
      ->setDescription(t('The site favicon.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('file_directory', 'favicon/[date:custom:Y]')
      ->setSetting('alt_field', FALSE)
      ->setSetting('file_extensions', 'ico png jpg jpeg')
      ->setSetting('max_resolution', '80x80')
      ->setDisplayOptions('form', array(
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => -10,
        'settings' => [
          'preview_image_style' => 'thumbnail',
          'progress_indicator' => 'throbber',
        ],
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['type_url'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Url Type'))
      ->setDescription(t('The url is a subdomain or a full domain.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'domain' => t('Domain')
        ],
      ])
      ->setDefaultValue('subdomain')
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['site_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site URL'))
      ->setDescription(t('The site URL. For sub-domain, fill only the sub-domain to use (ex: <b>mysite</b>.@base_url). For a full domain, fill the complete hostname to use (ex: <b>www.example.org</b>).', ['@base_url' => self::getHostBaseUrl()]))
      ->setRequired(TRUE)
      ->setRevisionable(FALSE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 254,
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
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['site_menu'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site Menu'))
      ->setDescription(t('The menu machine name associated with the site entity.'))
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setSettings([
        'max_length' => 254,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE);

    $fields['site_vocabulary'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site vocabulary'))
      ->setDescription(t('The vocabulary machine name associated with the site entity.'))
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setSettings([
        'max_length' => 254,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE);

    $fields['site_scheme'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Use https'))
      ->setDescription(t('The scheme to use.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['site_shield'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Shield activated'))
      ->setDescription(t('Protect your site under construction with a shield.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['shield_user'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Shield user'))
      ->setDescription(t('The user name to pass the shield. Without user name set, the shield will be inactive.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['shield_password'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Shield password'))
      ->setDescription(t('The shield user password.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['piwik_id'] = BaseFieldDefinition::create('integer')
      ->setLabel('Piwik Id')
      ->setSetting('unsigned', TRUE)
      ->setSetting('min', 1)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['piwik_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Piwik URL'))
      ->setDescription(t('The piwik URL without the scheme and with a trailing slash (ex: stat.example.com/).'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(FALSE)
      ->setSettings([
        'max_length' => 250,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['css'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('CSS'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 30,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    return $fields;
  }

}
