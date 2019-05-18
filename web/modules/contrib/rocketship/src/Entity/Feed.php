<?php

namespace Drupal\rocketship\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\UserInterface;

/**
 * Defines the Rocketship Feed entity.
 *
 * @ingroup rocketship
 *
 * @ContentEntityType(
 *   id = "rocketship_feed",
 *   label = @Translation("Rocketship Feed"),
 *   handlers = {
 *     "list_builder" = "Drupal\rocketship\FeedListBuilder",
 *     "views_data" = "Drupal\rocketship\Entity\FeedViewsData",
 *     "form" = {
 *       "default" = "Drupal\rocketship\Form\FeedForm",
 *       "add" = "Drupal\rocketship\Form\FeedForm",
 *       "edit" = "Drupal\rocketship\Form\FeedForm",
 *       "delete" = "Drupal\rocketship\Form\FeedDeleteForm",
 *     },
 *     "access" = "Drupal\rocketship\FeedAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\rocketship\FeedHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "rocketship_feed",
 *   admin_permission = "administer rocketship feed entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/services/rocketship/feed/add",
 *     "edit-form" = "/admin/config/services/rocketship/feed/{rocketship_feed}/edit",
 *     "delete-form" = "/admin/config/services/rocketship/feed/{rocketship_feed}/delete",
 *     "collection" = "/admin/config/services/rocketship",
 *   },
 * )
 */
class Feed extends ContentEntityBase implements FeedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('label', $label);
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
  public function isEnabled() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled($enabled) {
    $this->set('status', (bool) $enabled);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array(
          'display_label' => TRUE,
        ),
        'weight' => -15,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Feed label'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['filter_project'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Issues for project'))
      ->setDescription(t('Optionally specify one project machine name.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['rocketship_projects' => 'rocketship_projects'], 'auto_create' => TRUE])
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['filter_tag'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Issues tagged with'))
      ->setDescription(t('Optionally specify one tag.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['rocketship_issue_tags' => 'rocketship_issue_tags'], 'auto_create' => TRUE])
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['filter_assigned'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Issues assigned to'))
      ->setDescription(t('Optionally specify one username.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['rocketship_contributors' => 'rocketship_contributors'], 'auto_create' => TRUE])
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['filter_nid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Issue node identifier'))
      ->setDescription(t('Optionally specify one issue number. The feed will be valid with a valid issue number even if the other conditions do not match the issue.'))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'size' => '60',
        'weight' => 20,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedUrl() {
    $url = 'https://www.drupal.org/api-d7/node.json?type=project_issue&';

    // If there was a node id, ignore all the other filters.
    $nid = $this->filter_nid->getValue()[0]['value'];
    if (!empty($nid)) {
      $url .= 'nid=' . urlencode($nid);
      return $url;
    }

    // Add project filter.
    $project = $this->filter_project->getValue()[0]['target_id'];
    if (!empty($project)) {
      $term = Term::load($project);
      $nid = $term->field_rocketship_drupal_org_nid->getValue()[0]['value'];
      $url .= 'field_project=' . urlencode($nid);
    }

    // Add assigned filter.
    $user = $this->filter_assigned->getValue()[0]['target_id'];
    if (!empty($user)) {
      $term = Term::load($user);
      $uid = $term->field_rocketship_drupal_org_uid->getValue()[0]['value'];
      $url .= 'field_issue_assigned=' . urlencode($uid);
    }

    // Add tag filter.
    $tag = $this->filter_tag->getValue()[0]['target_id'];
    if (!empty($tag)) {
      $term = Term::load($tag);
      $tid = $term->field_rocketship_drupal_org_tid->getValue()[0]['value'];
      $url .= 'taxonomy_vocabulary_9=' . urlencode($tid);
    }

    return $url;
  }

}
