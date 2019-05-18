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
 * Defines the Major Version entity.
 *
 * @ingroup drd
 *
 * @ContentEntityType(
 *   id = "drd_major",
 *   label = @Translation("Major Version"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\drd\Entity\ListBuilderMajor",
 *     "views_data" = "Drupal\drd\Entity\ViewsData\Major",
 *
 *     "form" = {
 *       "default" = "Drupal\drd\Entity\Form\Major",
 *       "edit" = "Drupal\drd\Entity\Form\Major",
 *     },
 *     "access" = "Drupal\drd\Entity\AccessControlHandler\Major",
 *   },
 *   base_table = "drd_major",
 *   admin_permission = "administer DrdMajor entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "coreversion",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/drd/majors/major/{drd_major}",
 *     "edit-form" = "/drd/majors/major/{drd_major}/edit",
 *     "delete-form" = "/drd/majors/major/{drd_major}/delete"
 *   },
 *   field_ui_base_route = "drd_major.settings"
 * )
 */
class Major extends ContentEntityBase implements MajorInterface {
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
  public function getCoreVersion() {
    return $this->get('coreversion')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCoreVersion($coreversion) {
    $this->set('coreversion', $coreversion);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMajorVersion() {
    return $this->get('majorversion')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMajorVersion($majorversion) {
    $this->set('majorversion', $majorversion);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProject() {
    return $this->get('project')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setProject(ProjectInterface $project) {
    $this->set('project', $project->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentProject() {
    return $this->get('parentproject')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentProject(ProjectInterface $project) {
    $this->set('parentproject', $project->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendedRelease() {
    return $this->get('recommended')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecommendedRelease(ReleaseInterface $release) {
    $this->set('recommended', $release->id());
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
  public function isHidden() {
    return (bool) $this->get('hidden')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHidden($hidden) {
    $this->set('hidden', $hidden ? 1 : 0);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSupported() {
    return (bool) $this->get('supported')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSupported($supported) {
    $this->set('supported', $supported ? 1 : 0);
    return $this;
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
      ->setDescription(t('The ID of the Release entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Release entity.'))
      ->setReadOnly(TRUE);
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Host entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Major Version is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Major Version entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['terms'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tags'))
      ->setDescription(t('Tags for the host.'))
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

    $fields['project'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Project'))
      ->setDescription(t('The project for which this is a major version.'))
      ->setSetting('target_type', 'drd_project')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -5,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['parentproject'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent project'))
      ->setDescription(t('The parent project in which this major versions project is included.'))
      ->setSetting('target_type', 'drd_project')
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -4,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['coreversion'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Core version'))
      ->setDescription(t('The main core version.'))
      ->setSetting('size', 'small')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['majorversion'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Major version'))
      ->setDescription(t('The major version within the core version.'))
      ->setSetting('size', 'small')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['hidden'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Hidden'))
      ->setDescription(t('A boolean indicating whether the Major Version is hidden.'))
      ->setDefaultValue(FALSE);

    $fields['supported'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Supported'))
      ->setDescription(t('A boolean indicating whether the Major Version is supported.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recommended'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recommended release'))
      ->setDescription(t('The recommended relase for this major version.'))
      ->setSetting('target_type', 'drd_release')
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['information'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Information'))
      ->setDescription(t('Serialized information about the release.'))
      ->setDefaultValue([]);

    $fields['updatestatus'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Update status'))
      ->setDescription(t('Aggregated update status of all release of this major.'))
      ->setSettings([
        'max_length' => 20,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function updateStatus() {
    $query = \Drupal::database()->select('drd_domain__releases', 'd');
    $query->join('drd_release', 'r', 'd.releases_target_id=r.id');
    $query
      ->fields('r', ['updatestatus'])
      ->condition('r.major', $this->id());
    $stati = $query
      ->distinct()
      ->execute()
      ->fetchAllKeyed(0, 0);
    $this->set('updatestatus', implode(',', $stati));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function findOrCreate($type, $name, $version) {
    $major = self::find($name, $version);
    if (empty($major)) {
      $project = Project::findOrCreate($type, $name);
      $storage = \Drupal::entityTypeManager()->getStorage('drd_major');

      $parts = explode('-', $version);
      $coreparts = explode('.', $parts[0]);
      $coreversion = $coreparts[0];
      if (count($coreparts) == 2 && !empty($parts[1])) {
        list($majorversion,) = explode('.', $parts[1]);
      }
      else {
        $majorversion = $coreversion;
      }

      $major = $storage->create([
        'project' => $project->id(),
        'coreversion' => $coreversion,
        'majorversion' => $majorversion,
      ]);
      $major->save();
    }
    return $major;
  }

  /**
   * {@inheritdoc}
   */
  public static function find($name, $version) {
    $project = Project::find($name);
    if ($project) {
      $storage = \Drupal::entityTypeManager()->getStorage('drd_major');

      $parts = explode('-', $version);
      $coreparts = explode('.', $parts[0]);
      $coreversion = $coreparts[0];
      if (count($coreparts) == 2 && !empty($parts[1])) {
        list($majorversion,) = explode('.', $parts[1]);
      }
      else {
        $majorversion = $coreversion;
      }

      $majors = $storage->loadByProperties([
        'project' => $project->id(),
        'coreversion' => $coreversion,
        'majorversion' => $majorversion,
      ]);
    }
    return empty($majors) ? FALSE : reset($majors);
  }

}
