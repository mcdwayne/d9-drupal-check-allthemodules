<?php

namespace Drupal\chatbot\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Workflow entity.
 *
 * @ingroup chatbot
 *
 * @ContentEntityType(
 *   id = "chatbot_workflow",
 *   label = @Translation("Workflow"),
 *   handlers = {
 *     "list_builder" = "Drupal\chatbot\WorkflowListBuilder",
 *     "views_data" = "Drupal\chatbot\Entity\WorkflowViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\chatbot\Form\WorkflowForm",
 *       "add" = "Drupal\chatbot\Form\WorkflowForm",
 *       "edit" = "Drupal\chatbot\Form\WorkflowForm",
 *       "delete" = "Drupal\chatbot\Form\WorkflowDeleteForm",
 *     },
 *     "access" = "Drupal\chatbot\WorkflowAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\chatbot\WorkflowHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "chatbot_workflow",
 *   admin_permission = "administer workflow entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/chatbots/chatbot_workflow/{chatbot_workflow}",
 *     "add-form" = "/admin/structure/chatbots/chatbot_workflow/add",
 *     "edit-form" = "/admin/structure/chatbots/chatbot_workflow/{chatbot_workflow}/edit",
 *     "delete-form" = "/admin/structure/chatbots/chatbot_workflow/{chatbot_workflow}/delete",
 *     "collection" = "/admin/structure/chatbots/chatbot_workflow",
 *   },
 *   field_ui_base_route = "chatbot_workflow.settings"
 * )
 */
class Workflow extends ContentEntityBase implements WorkflowInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
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
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['steps'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Steps'))
      ->setDescription(t('Enter the steps in this workflow.'))
      ->setSetting('target_type', 'chatbot_step')
      ->setSetting('handler', 'default')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
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

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Workflow entity.'))
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Workflow is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Returns a list of Step Entity.
   *
   * @return \Drupal\chatbot\Entity\StepInterface
   *   A list of Steps
   */
  public function getSteps() {
    return $this->get('steps')->value;
  }

  /**
   * Sets the entity owner's user entity.
   *
   * @param $steps
   *   A list of Steps
   *
   * @return $this
   */
  public function setSteps($steps) {
    $this->set('steps', $steps);
    return $this;
  }

}
