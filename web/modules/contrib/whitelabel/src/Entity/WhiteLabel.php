<?php

namespace Drupal\whitelabel\Entity;

use Drupal\Core\Asset\CssOptimizer;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_reference_revisions\EntityNeedsSaveTrait;
use Drupal\file\FileInterface;
use Drupal\user\UserInterface;
use Drupal\whitelabel\WhiteLabelInterface;

/**
 * Defines the white label entity class.
 *
 * @ContentEntityType(
 *   id = "whitelabel",
 *   label = @Translation("White label"),
 *   label_collection = @Translation("White labels"),
 *   label_singular = @Translation("white label"),
 *   label_plural = @Translation("white labels"),
 *   label_count = @PluralTranslation(
 *     singular = "@count white label",
 *     plural = "@count white labels"
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\whitelabel\WhiteLabelAccessControlHandler",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   list_cache_contexts = { "whitelabel" },
 *   admin_permission = "administer white label settings",
 *   base_table = "whitelabel",
 *   data_table = "whitelabel_field_data",
 *   revision_table = "whitelabel_revision",
 *   revision_data_table = "whitelabel_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "wid",
 *     "revision" = "vid",
 *     "label" = "token",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   field_ui_base_route = "entity.whitelabel.admin_form",
 *   common_reference_revisions_target = TRUE,
 *   default_reference_revision_settings = {
 *     "field_storage_config" = {
 *       "cardinality" = 1,
 *       "settings" = {
 *         "target_type" = "whitelabel"
 *       }
 *     },
 *     "field_config" = {
 *       "settings" = {
 *         "handler" = "default:whitelabel"
 *       }
 *     },
 *     "entity_form_display" = {
 *       "type" = "entity_reference_whitelabel"
 *     },
 *     "entity_view_display" = {
 *       "type" = "entity_reference_revisions_entity_view"
 *     }
 *   }
 * )
 */
class WhiteLabel extends ContentEntityBase implements WhiteLabelInterface, RevisionLogInterface {

  use EntityNeedsSaveTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  protected $cacheContexts = ['whitelabel'];

  /**
   * {@inheritdoc}
   *
   * Next to the default entity-type:id cache tag, also assign these.
   *
   * whitelabel_view is used to invalidate all white labels on a configuration
   * update.
   *
   * @see: Drupal\whitelabel\EventSubscriber\WhiteLabelCacheConfigInvalidator
   */
  protected $cacheTags = ['whitelabel_view'];

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->get('token')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setToken($token) {
    $this->set('token', $token);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNameDisplay() {
    return $this->get('name_display')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNameDisplay($value) {
    $this->set('name_display', $value);
    return $this;
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
  public function getSlogan() {
    return $this->get('slogan')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSlogan($slogan) {
    $this->set('slogan', $slogan);
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
  public function setLogo(FileInterface $file) {
    $this->set('logo', $file->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return $this->get('theme')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTheme($theme) {
    $this->set('theme', $theme);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPalette() {
    $value = $this->get('palette')->getValue();
    return isset($value[0]['palette']) ? reset($value)['palette'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setPalette(array $palette) {
    $this->set('palette', ['palette' => $palette]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStylesheets() {
    $value = $this->get('stylesheets')->getValue();
    return isset($value[0]['stylesheets']) ? reset($value)['stylesheets'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setStylesheets(array $stylesheets) {
    $this->set('stylesheets', ['stylesheets' => $stylesheets]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The username of the content author.'))
      ->setReadOnly(TRUE)
      ->setConstraints([
        'UniqueField' => [],
      ])
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\whitelabel\Entity\WhiteLabel::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token'))
      ->setDescription(t('The unique token that will be appended to your white label URLs. Do not use you username! WARNING: Changing this later will break all existing urls.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setConstraints([
        'WhiteLabelTokenUnique' => [],
        'WhiteLabelNotUsername' => [],
      ])
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -100,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -100,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site name'))
      ->setDescription(t('The name of the site.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name_display'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Display site name'))
      ->setDescription(t('Allows hiding the site name in case it is already embedded in the logo.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['slogan'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site slogan'))
      ->setDescription(t('The slogan of the site.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['logo'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Site logo'))
      ->setDescription(t('Custom site logo'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'file_directory' => 'whitelabel',
        'alt_field' => FALSE,
        'alt_field_required' => FALSE,
        'file_extensions' => 'png gif jpg jpeg svg',
        'max_resolution' => '640x480',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'default',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['theme'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Site theme'))
      ->setDescription(t('Custom site theme'))
      ->setCardinality(1)
      ->setRevisionable(TRUE)
      ->setSettings([
        'allowed_values' => whitelabel_load_available_themes(),
        'default_value' => '',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['palette'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Color palette'))
      ->setDescription(t('The color palette of this white label.'))
      ->setRevisionable(TRUE)
      ->setCardinality(1);

    $fields['stylesheets'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Stylesheets'))
      ->setDescription(t('The stylesheets this white label requires.'))
      ->setRevisionable(TRUE)
      ->setCardinality(1);

    // Add the revision metadata fields.
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Generate CSS if the color palette has changed.
    if (isset($this->original) && $this->getPalette() != $this->original->getPalette()) {
      $theme = $this->getTheme() ?: \Drupal::config('system.theme')->get('default');
      $info = color_get_info($theme);

      if (isset($info['css'])) {
        // Delete old files.
        $files = $this->getStylesheets();
        if (isset($files)) {
          foreach ($files as $file) {
            @\Drupal::service('file_system')->unlink($file);
          }
        }
        if (isset($file) && $file = dirname($file)) {
          @\Drupal::service('file_system')->unlink($file);
        }

        // Prepare target locations for generated files.
        $id = $this->getToken();
        $paths['color'] = 'public://whitelabel';
        $paths['target'] = $paths['color'] . '/' . $id;
        foreach ($paths as $path) {
          file_prepare_directory($path, FILE_CREATE_DIRECTORY);
        }
        $paths['target'] = $paths['target'] . '/';
        $paths['id'] = $id;
        $paths['source'] = drupal_get_path('theme', $theme) . '/';
        $paths['files'] = $paths['map'] = [];

        // Rewrite theme stylesheets.
        $css = [];
        foreach ($info['css'] as $stylesheet) {
          // Build a temporary array with CSS files.
          $files = [];
          if (file_exists($paths['source'] . $stylesheet)) {
            $files[] = $stylesheet;
          }

          foreach ($files as $file) {
            $css_optimizer = new CssOptimizer();
            // Aggregate @imports recursively for each configured top level CSS
            // file without optimization. Aggregation and optimization will be
            // handled by drupal_build_css_cache() only.
            $style = $css_optimizer->loadFile($paths['source'] . $file, FALSE);

            // Return the path to where this CSS file originated from, stripping
            // off the name of the file at the end of the path.
            $css_optimizer->rewriteFileURIBasePath = base_path() . dirname($paths['source'] . $file) . '/';

            // Prefix all paths within this CSS file, ignoring absolute paths.
            $style = preg_replace_callback('/url\([\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\)/i', [
              $css_optimizer,
              'rewriteFileURI',
            ], $style);

            // Rewrite stylesheet with new colors.
            $style = _color_rewrite_stylesheet($theme, $info, $paths, $this->getPalette(), $style);

            $base_file = \Drupal::service('file_system')->basename($file);
            $css[] = $paths['target'] . $base_file;
            _color_save_stylesheet($paths['target'] . $base_file, $style, $paths);
          }

          // Save stylesheets to the entity.
          $this->setStylesheets($css);
        }
      }
    }
  }

}
