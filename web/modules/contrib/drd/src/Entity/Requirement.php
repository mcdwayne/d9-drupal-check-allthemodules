<?php

namespace Drupal\drd\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Requirement entity.
 *
 * @ingroup drd
 *
 * @ContentEntityType(
 *   id = "drd_requirement",
 *   label = @Translation("Requirement"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\drd\Entity\ListBuilder\Requirement",
 *     "views_data" = "Drupal\drd\Entity\ViewsData\Requirement",
 *
 *     "form" = {
 *       "default" = "Drupal\drd\Entity\Form\Requirement",
 *       "edit" = "Drupal\drd\Entity\Form\Requirement",
 *     },
 *     "access" = "Drupal\drd\Entity\AccessControlHandler\Requirement",
 *   },
 *   base_table = "drd_requirement",
 *   admin_permission = "administer DrdRequirement entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "requirement",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/drd/requirements/requirement/{drd_requirement}",
 *     "edit-form" = "/drd/requirements/requirement/{drd_requirement}/edit",
 *   },
 *   field_ui_base_route = "drd_requirement.settings"
 * )
 */
class Requirement extends ContentEntityBase implements RequirementInterface {
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['user_id' => \Drupal::currentUser()->id()];
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
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NodeInterface::PUBLISHED : NodeInterface::NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isIgnored() {
    return (bool) $this->get('ignored')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategory() {
    return $this->get('category')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangCode() {
    return $this->get('langcode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Requirement entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Requirement entity.'))
      ->setReadOnly(TRUE);
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Requirement entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['requirement'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Key'))
      ->setDescription(t('The key of the Requirement entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('The label of the Requirement entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Requirement is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Requirement entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['terms'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tags'))
      ->setDescription(t('Tags for the requirement.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => ['tags'],
        'sort' => ['field' => '_none'],
        'auto_create' => TRUE,
      ])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setCustomStorage(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -1,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ignored'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Globally ignored'))
      ->setDescription(t('A boolean indicating whether this reqauirement is globally ignores.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['corerelated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Core related'))
      ->setDescription(t('A boolean indicating whether the requirement is related to the core only.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -2,
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['category'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Requirement category'))
      ->setDescription(t('The category of the requirement.'))
      ->setSettings([
        'max_length' => 10,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Default categorisation for status information.
   */
  private static function defaultCategories() {
    return [
      'health' => [
        'cron',
        'drd.monitoring',
        'entity_update',
        'update_core',
        'update_contrib',
        'update',
      ],
      'secure' => [
        'php_register_globals',
        'security_review',
        'settings.php',
        'update access',
        'update_core',
        'update_contrib',
        'unicode',
        'drd_agent.ssl',
        'drd_agent.user1',
        'drd_agent.admincount',
        'drd_agent.hidden.warnings',
        'drd_agent.removed.txtfiles',
        'drd_agent.module.php',
      ],
      'other' => [],
      'tuning' => [
        'drd_agent.module.devel',
        'drd_agent.compress.css',
        'drd_agent.compress.js',
        'drd_agent.compress.page',
        'drd_agent.enable.cache',
        'drd_agent.theme.registry',
        'drd_agent.trim.watchdog',
        'drd_agent.defined.403',
        'drd_agent.defined.404',
        'css_gzip',
        'php_memory_limit',
        'ctools_css_cache',
        'file system',
      ],
      'seo' => [
        'drd_agent.module.xmlsitemap',
        'drd_agent.robots.txt',
        'drd_agent.enable.cleanurl',
        'drd_agent.module.pathauto',
        'drd_agent.favicon',
        'drd_agent.module.analytics',
        'drd_agent.module.metatag',
        'drd_agent.module.globalredirect',
        'drd_agent.module.pagetitle',
      ],
      'ignored' => [
        'drd_server.admincount',
        'drd_server.api',
        'drd_server.compress.css',
        'drd_server.compress.js',
        'drd_server.compress.page',
        'drd_server.defined.403',
        'drd_server.defined.404',
        'drd_server.enable.cache',
        'drd_server.enable.cleanurl',
        'drd_server.favicon',
        'drd_server.hidden.warnings',
        'drd_server.module.analytics',
        'drd_server.module.devel',
        'drd_server.module.globalredirect',
        'drd_server.module.metatag',
        'drd_server.module.mollom',
        'drd_server.module.pagetitle',
        'drd_server.module.pathauto',
        'drd_server.module.php',
        'drd_server.module.redirect',
        'drd_server.module.xmlsitemap',
        'drd_server.removed.txtfiles',
        'drd_server.robots.txt',
        'drd_server.securepages',
        'drd_server.ssl',
        'drd_server.theme.registry',
        'drd_server.trim.watchdog',
        'drd_server.user1',
      ],
    ];
  }

  /**
   * Get the categories for a requirements (status report) key.
   *
   * @param string $key
   *   The key of the requirement.
   *
   * @return mixed
   *   The category into which the key matches.
   *
   * @throws \Exception
   */
  private static function categories($key) {
    static $categories;

    if (!isset($categories)) {
      $defaults = self::defaultCategories();

      $server_values = [
        'drupal',
        'file_progress',
        'image_gd',
        'mailhandler_php_imap',
        'php',
        'php_extensions',
        'php_memory_limit',
        'php_register_globals',
        'unicode',
        'update_core',
        'webserver',
      ];

      $categories = [];
      foreach ($defaults as $category => $items) {
        foreach ($items as $item) {
          $categories[$item] = [
            'category' => $category,
            'corerelated' => in_array($item, $server_values),
            'ignored' => ($category == 'ignored'),
          ];
        }
      }
    }

    if (!isset($categories[$key])) {
      $categories[$key] = [
        'category' => 'other',
        'corerelated' => FALSE,
        'ignored' => FALSE,
      ];
    }

    if ($categories[$key]['ignored']) {
      throw new \Exception('Ignore drd_server requirements, they got replaced by drd_agent.');
    }
    return $categories[$key];
  }

  /**
   * {@inheritdoc}
   */
  public static function findOrCreate($key, $label) {
    $storage = \Drupal::entityTypeManager()->getStorage('drd_requirement');
    $requirements = $storage->loadByProperties([
      'requirement' => $key,
    ]);

    $categories = self::categories($key);
    if (empty($requirements)) {
      $requirement = $storage->create([
        'requirement' => $key,
        'label' => $label,
      ] + $categories);
      $requirement->save();
    }
    else {
      /** @var RequirementInterface $requirement */
      $requirement = reset($requirements);
      $changed = FALSE;
      foreach ($categories as $ckey => $value) {
        if ($requirement->get($ckey)->get(0)->getValue() != $value) {
          $changed = TRUE;
          $requirement->set($ckey, $value);
        }
      }
      if ($changed) {
        $requirement->save();
      }
    }
    if ($requirement->isIgnored()) {
      throw new \Exception('Ignored by settings.');
    }
    return $requirement;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCategoryKeys() {
    static $defaults;
    if (!isset($defaults)) {
      $defaults = self::defaultCategories();
      unset($defaults['ignored']);
      $defaults = array_keys($defaults);
    }
    return $defaults;
  }

}
