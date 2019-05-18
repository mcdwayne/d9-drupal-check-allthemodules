<?php

namespace Drupal\bibcite_entity\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Contributor entity.
 *
 * @ingroup bibcite_entity
 *
 * @ContentEntityType(
 *   id = "bibcite_contributor",
 *   label = @Translation("Contributor"),
 *   handlers = {
 *     "storage" = "Drupal\bibcite_entity\ContributorStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bibcite_entity\ContributorListBuilder",
 *     "views_data" = "Drupal\bibcite_entity\ContributorViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\bibcite_entity\Form\ContributorForm",
 *       "add" = "Drupal\bibcite_entity\Form\ContributorForm",
 *       "edit" = "Drupal\bibcite_entity\Form\ContributorForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\bibcite_entity\ContributorAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "bibcite_contributor",
 *   admin_permission = "administer bibcite_contributor",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/bibcite/contributor/{bibcite_contributor}",
 *     "edit-form" = "/bibcite/contributor/{bibcite_contributor}/edit",
 *     "delete-form" = "/bibcite/contributor/{bibcite_contributor}/delete",
 *     "bibcite-merge-form" = "/bibcite/contributor/{bibcite_contributor}/merge",
 *     "add-form" = "/bibcite/contributor/add",
 *     "bibcite-merge-multiple-form" = "/admin/content/bibcite/contributor/merge",
 *     "delete-multiple-form" = "/admin/content/bibcite/contributor/delete",
 *     "collection" = "/admin/content/bibcite/contributor",
 *   },
 * )
 */
class Contributor extends ContentEntityBase implements ContributorInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getLeadingInitial() {
    return $this->get('leading_title')->value;
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
  public function getFirstName() {
    return $this->get('first_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMiddleName() {
    return $this->get('middle_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    return $this->get('last_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getNickName() {
    return $this->get('nick')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuffix() {
    return $this->get('suffix')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefix() {
    return $this->get('prefix')->value;
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
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLeadingInitial($leading) {
    $this->set('leading_title', $leading);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirstName($first_name) {
    $this->set('first_name', $first_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMiddleName($middle_name) {
    $this->set('middle_name', $middle_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastName($last_name) {
    $this->set('last_name', $last_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNickName($nick) {
    $this->set('nick', $nick);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuffix($suffix) {
    $this->set('suffix', $suffix);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrefix($prefix) {
    $this->set('prefix', $prefix);
    return $this;
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setCustomStorage(TRUE)
      ->setClass('\Drupal\bibcite_entity\ContributorName');

    $fields['leading_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Leading initial'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 2,
      ]);

    $fields['prefix'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Prefix'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 3,
      ]);

    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First name'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 4,
      ]);

    $fields['middle_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Middle name'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 5,
      ]);

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last name'))
      ->setDefaultValue('')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 6,
      ]);

    $fields['nick'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nickname'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 7,
      ]);

    $fields['suffix'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Suffix'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 7,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 8,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
