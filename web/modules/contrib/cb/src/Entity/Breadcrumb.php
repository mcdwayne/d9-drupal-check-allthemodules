<?php

namespace Drupal\cb\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\cb\BreadcrumbInterface;

/**
 * Defines the chained breadcrumb entity.
 *
 * @ContentEntityType(
 *   id = "cb_breadcrumb",
 *   label = @Translation("Chained breadcrumb"),
 *   handlers = {
 *     "storage" = "Drupal\cb\BreadcrumbStorage",
 *     "views_data" = "Drupal\cb\BreadcrumbViewsData",
 *     "access" = "Drupal\cb\BreadcrumbAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\cb\MultistepBreadcrumbForm",
 *       "delete" = "Drupal\cb\Form\BreadcrumbDeleteForm"
 *     },
 *     "translation" = "Drupal\cb\BreadcrumbTranslationHandler"
 *   },
 *   base_table = "cb_breadcrumb",
 *   data_table = "cb_breadcrumb_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "bid",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "delete-form" = "/cb/breadcrumb/{cb_breadcrumb}/delete",
 *     "edit-form" = "/cb/breadcrumb/{cb_breadcrumb}/edit",
 *     "collection" = "/admin/structure/cb",
 *     "create" = "/cb/breadcrumb",
 *   },
 * )
 */
class Breadcrumb extends ContentEntityBase implements BreadcrumbInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['bid']->setLabel(t('Breadcrumb ID'))
      ->setDescription(t('The breadcrumb ID.'));

    $fields['uuid']->setDescription(t('The breadcrumb UUID.'));

    $fields['langcode']->setDescription(t('The breadcrumb language code.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the breadcrumb. Will be used only for administrative purposes.'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ]);

    $fields['paths'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Path(s)'))
      ->setDescription(t('The path(s) on wich breadcrumb will appear. Supported only non alias paths and only pattern of the paths, for example - /node/%, /articles, /comment/reply/%/%/%/% - etc. Also supported multiple paths, which you may define per line.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -5,
      ]);

    $fields['link_titles'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Title of the link'))
      ->setDescription(t('The titles of the breadcrumb links. You may use available tokens to define a titles or use a simple string. Supported multiple titles per line.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -2,
      ]);

    $fields['link_paths'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Path of the link'))
      ->setDescription(t('The breadcrumb links. Use in this form a links with system path, for example - /admin/structure, and you may use available tokens to build the links. Supported multiple links per line.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -2,
      ]);

    $fields['enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDescription(t('If unchecked then the breadcrumb will be not appears on the breadcrumb path.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => -1,
      ]);

    $fields['home_link'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Home link'))
      ->setDescription(t('If unchecked then the home link will be not added to the root of the breadcrumb link.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => -1,
      ]);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this breadcrumb in relation to other breadcrumbs.'))
      ->setDefaultValue(0);

    $fields['parent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Breadcrumb parents'))
      ->setDescription(t('The parents of the breadcrumb.'));

    $fields['applies'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Application condition'))
      ->setDescription(t("Condition of the apply of the breadcrumb (PHP code). The code must return boolean value, for example - if (\$route_match->getParameter('node')->getType() == 'article') { return TRUE; }. \$route_match object is available and you may use it in this form."))
      ->setDefaultValue('')
      ->setSetting('max_length', 1000)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -2,
      ]);

    $fields['cache_contexts'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Cache contexts'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the breadcrumb was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $storage = \Drupal::entityManager()->getStorage('cb_breadcrumb');
    $parent = NULL;
    if ($this->hasParent()) {
      $parent = $this->getParentId();
    }
    if ($this->hasChildren()) {
      $children = $storage->loadChildren($this->id());
      unset($children[$this->id()]);
      entity_delete_multiple('cb_breadcrumb', array_keys($children));
    }
    parent::delete();
    if ($parent) {
      $storage->resetCache([$parent]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBreadcrumbCacheContexts() {
    return explode(',', $this->get('cache_contexts')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setBreadcrumbCacheContexts($cache_contexts) {
    $this->set('cache_contexts', implode(',', array_keys($cache_contexts)));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->label();
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
  public function hasParent() {
    return $this->getParentId() != 0;
  }

  /**
   * {@inheritdoc}
   */
  public function hasChildren() {
    return $this->children['x-default'] == NULL ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildrenIds() {
    return $this->hasChildren() ? explode(',', $this->children['x-default']) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkData() {
    $link_data = [];
    $link_titles = explode("\r\n", $this->getLinkTitles());
    $link_paths = explode("\r\n", $this->getLinkPaths());
    foreach ($link_titles as $key => $title) {
      $link_data[$link_paths[$key]] = $title;
    }
    return $link_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkTitles() {
    return $this->get('link_titles')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkPaths() {
    return $this->get('link_paths')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId() {
    return $this->get('parent')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->load($this->getParentId());
  }

  /**
   * {@inheritdoc}
   */
  public function setParent($parent) {
    $this->set('parent', $parent);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->get('enabled')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function enable($value) {
    $this->set('enabled', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function applies() {
    return $this->get('applies')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isHomeLink() {
    return (bool) $this->get('home_link')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaths() {
    return $this->get('paths')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaths($paths) {
    $this->set('paths', $paths);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function multiplePaths() {
    return count($this->pathsToArray()) > 1 ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function pathsToArray() {
    return explode("\r\n", rtrim($this->getPaths(), "\r\n"));
  }

  /**
   * {@inheritdoc}
   */
  public function firstPath() {
    return $this->pathsToArray()[0];
  }

}
