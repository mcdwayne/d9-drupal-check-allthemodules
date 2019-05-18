<?php

namespace Drupal\monster_menus\Entity;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\monster_menus\Constants;
use Drupal\user\UserInterface;

class MMTreeDepthException extends \Exception {}

/**
 * Defines the MM Page entity.
 *
 * @ingroup monster_menus
 *
 * @ContentEntityType(
 *   id = "mm_tree",
 *   label = @Translation("MM Page"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\monster_menus\MMTreeListBuilder",
 *     "views_data" = "Drupal\monster_menus\Entity\MMTreeViewsData",
 *     "translation" = "Drupal\monster_menus\MMTreeTranslationHandler",
 *     "storage_schema" = "Drupal\monster_menus\MMTreeStorageSchema",
 *     "form" = {
 *       "default" = "Drupal\monster_menus\Form\EditContentForm",
 *       "add" = "Drupal\monster_menus\Form\EditContentForm",
 *       "edit" = "Drupal\monster_menus\Form\EditContentForm",
 *       "delete" = "Drupal\monster_menus\Form\DeleteContentConfirmForm",
 *     },
 *     "access" = "Drupal\monster_menus\MMTreeAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\monster_menus\MMTreeHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "mm_tree",
 *   data_table = "",
 *   translatable = FALSE,
 *   admin_permission = "administer all menus",
 *   uri_callback = "mm_tree_uri",
 *   entity_keys = {
 *     "id" = "mmtid",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/mm/{mm_tree}",
 *     "add-form" = "/mm/{mm_tree}/settings/sub",
 *     "edit-form" = "/mm/{mm_tree}/settings/edit",
 *     "delete-form" = "/mm/{mm_tree}/settings/delete",
 *     "version-history" = "/mm/{mm_tree}/settings/revisions",
 *   }
 * )
 */
class MMTree extends ContentEntityBase implements MMTreeInterface {

  use EntityChangedTrait;
  use MMTreeExtendedSettingsTrait;

  /**
   * Initial values of several fields, to determine upon save whether the sort
   * index needs to be updated.
   *
   * @var $oldName
   *   The user-readable name
   * @var $oldWeight
   *   The weight (sort order)
   * @var $oldHidden
   *   Whether the item is hidden in menus
   * @var $oldParent
   *  The parent MM Tree ID
   */
  private $oldName, $oldWeight, $oldHidden, $oldParent;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'uid' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * @inheritDoc
   */
  public function isGroup() {
    return mm_content_is_group((bool) ($this->isNew() ? $this->get('parent')->getString() : $this->id()));
  }

  /**
   * @inheritDoc
   */
  public function save() {
    $parent = $this->get('parent')->getString();
    if (!isset($parent)) {
      throw new \Exception('MMTree::save() requires that "parent" be set.');
    }
    $parent = (int) $parent;

    if ($this->isNew()) {
      $msg = array('An attempt to create a new child of mmtid=@mmtid failed, because it would result in a tree that is too deeply nested. To correct this problem, increase the length of mm_tree.sort_idx then run mm_content_update_sort().', array('@mmtid' => $parent));
      if (!_mm_content_test_sort_length($sort_idx = _mm_content_get_next_sort($parent), $msg)) {
        throw new MMTreeDepthException('This MMTree could not be created because it would cause the tree to become too deeply nested. Please contact a system administrator.');
      }

      if (empty($this->getCreatedTime())) {
        $this->setCreatedTime(mm_request_time());
      }

      if (empty($this->get('cuid')->value)) {
        $this->set('cuid', \Drupal::currentUser()->id());
      }

      $this->set('sort_idx_dirty', 1);
      $this->set('sort_idx', $sort_idx);

      $transaction = Database::getConnection()->startTransaction();
      try {
        $parent_list = mm_content_get_parents_with_self($parent, FALSE, FALSE); // don't include virtual parents
        // Note: save() automatically writes a revision.
        parent::save();
        mm_content_update_parents($this->id(), $parent_list, TRUE);
        $this->saveExtendedSettings();
        // If the direct parent is a recycle bin, update the mm_recycle table.
        // This should only happen during migrate or import, since that is the
        // only time a new MMTree would be created inside a bin.
        if (mm_content_user_can($parent, Constants::MM_PERMS_IS_RECYCLE_BIN)) {
          Database::getConnection()->insert('mm_recycle')
            ->fields(array(
              'type' => 'cat',
              'id' => $this->id(),
              'bin_mmtid' => $parent,
              'recycle_date' => mm_request_time(),
            ))
            ->execute();
        }
        mm_content_clear_caches($parent);
        mm_content_update_sort_queue($parent);
      }
      catch (\Exception $e) {
        $transaction->rollback();
        throw $e;
      }
    }
    // Update.
    else {
      $fields = $this->getFields();
      if ($fields['name']->value != $this->oldName || $fields['weight']->value != $this->oldWeight || $fields['hidden']->value != $this->oldHidden || $parent != $this->oldParent) {
        if ($parent != $this->oldParent) {
          // Location in tree is changing.
          if ($fields['weight']->value == $this->oldWeight) {
            // Weight is not intentionally set, so reset it.
            $this->set('weight', 0);
          }
          mm_content_update_parents($this->id());
        }
        $this->set('sort_idx_dirty', 1);
        mm_content_update_sort_queue($parent);
      }
      $transaction = Database::getConnection()->startTransaction();
      try {
        parent::save();
        $this->saveExtendedSettings();
        mm_content_clear_caches($parent);
        mm_content_update_sort_queue($parent);
        mm_content_clear_routing_cache_tagged($this->id());
      }
      catch (\Exception $e) {
        $transaction->rollback();
        throw $e;
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function delete() {
    parent::delete();
    static::deleteMultiple([$this->id()], FALSE);
  }

  /**
   * Delete multiple MMTree entities.
   *
   * @param array $mmtids
   *   The list of tree entries to delete.
   * @param bool $doBaseTable
   *   Whether to also delete rows from the base mm_tree table.
   */
  public static function deleteMultiple($mmtids, $doBaseTable = TRUE) {
    $database = Database::getConnection();
    $use_db_query = $database->databaseType() == 'mysql';

    if ($doBaseTable) {
      $database->delete('mm_tree')
        ->condition('mmtid', $mmtids, 'IN')
        ->execute();
    }
    $or = new Condition('OR');
    $database->delete('mm_tree_parents')
      ->condition($or
        ->condition('mmtid', $mmtids, 'IN')
        ->condition('parent', $mmtids, 'IN')
      )
      ->execute();
    $database->delete('mm_node2tree')
      ->condition('mmtid', $mmtids, 'IN')
      ->execute();
    // Clear the cache used by mm_content_get_by_nid.
    mm_content_get_by_nid(NULL, TRUE);
    $database->delete('mm_node_reorder')
      ->condition('mmtid', $mmtids, 'IN')
      ->execute();
    $database->delete('mm_recycle')
      ->condition('type', 'cat')
      ->condition('id', $mmtids, 'IN')
      ->execute();
    $database->delete('mm_recycle')
      ->condition('type', 'node')
      ->condition('bin_mmtid', $mmtids, 'IN')
      ->execute();
    $database->update('mm_recycle')
      ->fields(array('from_mmtid' => 0))
      ->condition('type', 'node')
      ->condition('from_mmtid', $mmtids, 'IN')
      ->execute();

    // This needs to happen before deleting groups, below.
    static::deleteMultipleExtendedSettings($mmtids);

    // Remove ad-hoc groups (gid<0) first.
    // It's far faster to use $database->query(), since DBTNG doesn't allow JOIN.
    if ($use_db_query) {
      $database->query('DELETE g FROM {mm_group} g INNER JOIN {mm_tree_access} a ON a.gid = g.gid WHERE a.mmtid IN(:mmtids[]) AND a.gid < 0', array(':mmtids[]' => $mmtids));
    }
    else {
      // DELETE FROM {mm_group} WHERE
      //   (SELECT 1 FROM {mm_tree_access} a WHERE a.gid = {mm_group}.gid
      //     AND a.mmtid IN(:mmtids) AND a.gid < 0)
      $adhoc = $database->select('mm_tree_access', 'a');
      $adhoc->addExpression(1);
      $adhoc->where('a.gid = {mm_group}.gid')
        ->condition('a.mmtid', $mmtids, 'IN')
        ->condition('a.gid', 0, '<');
      $database->delete('mm_group')
        ->condition($adhoc)
        ->execute();
    }

    // Remove remaining groups.
    $database->delete('mm_tree_access')
      ->condition(db_or()
        ->condition('mmtid', $mmtids, 'IN')
        ->condition('gid', $mmtids, 'IN')
      )
      ->execute();
    $database->delete('mm_node_write')
      ->condition('gid', $mmtids, 'IN')
      ->execute();
    $database->delete('mm_node_redir')
      ->condition('mmtid', $mmtids, 'IN')
      ->execute();
    $database->delete('mm_tree_bookmarks')
      ->condition('data', $mmtids, 'IN')
      ->execute();
  }

  /**
   * @inheritDoc
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    // Copy some values during initial load for comparison during save() to see
    // if the sort index needs to be updated.
    /** @var MMTree[] $entities */
    foreach ($entities as $entity) {
      $entity->setOldSortValues($entity->getName(), $entity->get('weight')->value, $entity->get('hidden')->value, (int) $entity->get('parent')->getString());
    }
    parent::postLoad($storage, $entities);
  }

  public function setOldSortValues($name, $weight, $hidden, $parent) {
    $this->oldName = $name;
    $this->oldWeight = $weight;
    $this->oldHidden = $hidden;
    $this->oldParent = $parent;
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
  public function getCreatedTime() {
    return $this->get('ctime')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('ctime', $timestamp);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('mtime')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('mtime', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
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
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * Get a representation of the entity as a standard object.
   *
   * @return \stdClass
   */
  public function toObject() {
    $object = (object) [];
    /** @var FieldItemListInterface $property */
    foreach ($this->getFields() as $name => $property) {
      $val = $property->getValue();
      $object->$name = isset($val[0]['value']) ? $val[0]['value'] : NULL;
    }
    return $object;
  }

  /**
   * {@inheritdoc}
   */
  public function &__get($name) {
    if (in_array($name, static::$extendedFieldKeys)) {
      $data = $this->getExtendedSettings();
      if (!isset($data[$name])) {
        $data[$name] = NULL;
      }
      return $data[$name];
    }
    return parent::__get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    if (in_array($name, static::$extendedFieldKeys)) {
      $this->extendedSettings[$name] = $value;
    }
    else {
      parent::__set($name, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($name) {
    if (in_array($name, static::$extendedFieldKeys)) {
      $data = $this->getExtendedSettings();
      return isset($data[$name]);
    }
    return parent::__isset($name);
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($name) {
    if (in_array($name, static::$extendedFieldKeys)) {
      unset($this->extendedSettings[$name]);
    }
    else {
      parent::__unset($name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('User ID of the owner'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Page Name'))
      ->setDescription(t('Name of the page'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'max_length' => 128,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['alias'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL alias'))
      ->setDescription(t('Alias of the page'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'max_length' => 128,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setReadOnly(TRUE)
      ->setLabel(t('Parent MMTID'))
      ->setDescription(t('ID of the parent'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'mm_tree')
      ->setSetting('handler', 'default');

    $fields['default_mode'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Access mode(s)'))
      ->setDescription(t('Access mode(s) for anonymous user'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'max_length' => 7,
        'text_processing' => 0,
      ))
      ->setDefaultValue('');

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Menu order'))
      ->setDefaultValue(0);

    $fields['theme'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Theme'))
      ->setRevisionable(TRUE)
      ->setDescription(t('Theme for this page and its children'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['sort_idx'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sort index'))
      ->setReadOnly(TRUE)
      ->setSettings(array(
        'max_length' => min(intval(255 / Constants::MM_CONTENT_BTOA_CHARS), Constants::MM_CONTENT_MYSQL_MAX_JOINS) * Constants::MM_CONTENT_BTOA_CHARS,
        'text_processing' => 0,
      ));

    $fields['sort_idx_dirty'] = BaseFieldDefinition::create('boolean')
      ->setReadOnly(TRUE)
      ->setLabel(t('Sort index is dirty'))
      ->setDefaultValue(FALSE);

    $fields['hover'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hover'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDescription(t('Displayed when mouse hovers over menu entry'))
      ->setSettings(array(
        'max_length' => 128,
        'text_processing' => 0,
      ));

    $fields['rss'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('RSS feed is enabled'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE);

    $fields['ctime'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Creation time'))
      ->setReadOnly(TRUE);

    $fields['cuid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('User ID of the creator'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValue(1)
      ->setReadOnly(TRUE);

    $fields['mtime'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modification time'))
      ->setDescription(t('The time when the entry was last edited'))
      ->setRevisionable(TRUE);

    $fields['muid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Editor'))
      ->setDescription(t('User ID of the editor'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValue(1)
      ->setRevisionable(TRUE);

    $fields['node_info'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Default attribution display mode'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(1);

    $fields['previews'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show only teasers'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE);

    $fields['hidden'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Hidden'))
      ->setDescription(t('Page is hidden in menu'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE);

    $fields['comment'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Default comment display mode'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(0);

    // Add a placeholder for extended settings which is only used during import.
    $fields['extendedSettings'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Placeholder for extended settings'))
      ->setRevisionable(FALSE)
      ->setCustomStorage(TRUE)
      ->setDefaultValue(NULL);

    return $fields;
  }

}
