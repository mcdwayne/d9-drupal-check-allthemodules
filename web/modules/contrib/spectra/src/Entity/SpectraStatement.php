<?php
/**
 * @file
 * Contains \Drupal\spectra\Entity\SpectraStatement.
 */

namespace Drupal\spectra\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\spectra\Controller\SpectraController;
use Drupal\spectra\Entity\SpectraData;
use Drupal\spectra\Entity\SpectraNoun;
use Drupal\spectra\Entity\SpectraVerb;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the SpectraStatement entity.
 *
 * @ingroup spectra
 *
 *
 * @ContentEntityType(
 * id = "spectra_statement",
 * label = @Translation("Spectra Statement"),
 * handlers = {
 *   "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *   "list_builder" = "Drupal\spectra\Entity\Controller\SpectraStatementListBuilder",
 *   "views_data" = "Drupal\spectra\Entity\Views\SpectraStatementViewsData",
 *   "form" = {
 *     "add" = "Drupal\spectra\Form\SpectraStatementForm",
 *     "edit" = "Drupal\spectra\Form\SpectraStatementForm",
 *     "delete" = "Drupal\spectra\Form\SpectraStatementDeleteForm",
 *   },
 *   "access" = "Drupal\spectra\SpectraStatementAccessControlHandler",
 * },
 * base_table = "spectra_statement",
 * admin_permission = "administer spectra_statement entity",
 * fieldable = TRUE,
 * entity_keys = {
 *   "id" = "statement_id",
 *   "uuid" = "uuid",
 * },
 * links = {
 *   "canonical" = "/spectra_statement/{spectra_statement}",
 *   "edit-form" = "/spectra_statement/{spectra_statement}/edit",
 *   "delete-form" = "/spectra_statement/{spectra_statement}/delete",
 *   "collection" = "/spectra_statement/list"
 * },
 * field_ui_base_route = "spectra.spectra_statement_settings",
 * )
 */
class SpectraStatement extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSpectraEntityType($type = 'machine_name') {
    switch ($type) {
      case 'class_name':
        return 'SpectraStatement';
        break;
      case 'short':
        return 'statement';
        break;
      case 'machine_name':
      default:
        return 'spectra_statement';
    }
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
  public function delete() {
    // Delete the associated data
    $query = \Drupal::entityQuery('spectra_data');
    $query->condition('statement_id', $this->id());
    $result = array_keys($query->execute());
    $data = SpectraData::loadMultiple($result);
    foreach ($data as $d) {
      $d->delete();
    }

    // The associated data is deleted. Now run the standard delete logic.
    if (!$this
      ->isNew()) {
      $this
        ->entityTypeManager()
        ->getStorage($this->entityTypeId)
        ->delete(array(
          $this
            ->id() => $this,
        ));
    }
  }

  /**
   * Delete entities associated with a statement, if they are otherwise unused.
   */
  public function deleteAssociatedEntities() {
    $action_id = isset($this->get('action_id')->getValue()[0]['target_id']) ? $this->get('action_id')->getValue()[0]['target_id'] : 0;
    $actor_id = isset($this->get('actor_id')->getValue()[0]['target_id']) ? $this->get('actor_id')->getValue()[0]['target_id'] : 0;
    $context_id = isset($this->get('context_id')->getValue()[0]['target_id']) ? $this->get('context_id')->getValue()[0]['target_id'] : 0;
    $object_id = isset($this->get('object_id')->getValue()[0]['target_id']) ? $this->get('object_id')->getValue()[0]['target_id'] : 0;
    if ($action_id) {
      $actionQuery = \Drupal::entityQuery('spectra_statement')->condition('action_id', $action_id);
      $actionStatements = $actionQuery->count()->execute();
      if ($actionStatements <= 1) {
        SpectraVerb::load($action_id)->delete();
      }
    }
    if ($actor_id) {
      $actorQuery = \Drupal::entityQuery('spectra_statement')->condition('actor_id', $actor_id);
      $actorStatements = $actorQuery->count()->execute();
      if ($actorStatements <= 1) {
        SpectraNoun::load($actor_id)->delete();
      }
    }
    if ($context_id) {
      $contextQuery = \Drupal::entityQuery('spectra_statement')->condition('context_id', $context_id);
      $contextStatements = $contextQuery->count()->execute();
      if ($contextStatements <= 1) {
        SpectraNoun::load($context_id)->delete();
      }
    }
    if ($object_id) {
      $objectQuery = \Drupal::entityQuery('spectra_statement')->condition('object_id', $object_id);
      $objectStatements = $objectQuery->count()->execute();
      if ($objectStatements <= 1) {
        SpectraNoun::load($object_id)->delete();
      }
    }
  }

  /**
   * Load non-data entities associated with this statement
   */
  public function loadAssociatedEntities() {
    $ret = [];
    foreach (
      [
        'actor' => 'Drupal\spectra\Entity\SpectraNoun',
        'action' => 'Drupal\spectra\Entity\SpectraVerb',
        'object' => 'Drupal\spectra\Entity\SpectraNoun',
        'context' => 'Drupal\spectra\Entity\SpectraNoun'
      ] as $ent => $class) {
      $id = isset($this->get($ent . '_id')->getValue()[0]['target_id']) ? $this->get($ent . '_id')->getValue()[0]['target_id'] : 0;
      if ($id) {
        $ret[$ent] = $class::load($id);
      }
    }
    return $ret;
  }

  /**
   * Load data entities associated with this statement
   */
  public function loadAssociatedData() {
    $id = $this->id();
    $query = \Drupal::entityQuery('spectra_data')->condition('statement_id', $id);
    $data = $query->execute();

    return SpectraData::loadMultiple(array_keys($data));
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['statement_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the SpectraStatement entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the SpectraStatement entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['statement_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Statement Time'))
      ->setDescription(t('The time of the statement event.'));

    $fields['statement_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Statement Type'))
      ->setDescription(t('Used for determining the correct plugin to call, and for indexing.'))
      ->setSettings(array(
        'default_value' => 'default',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['actor_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Actor'))
      ->setDescription(t('The Spectra Actor, who is generating this statement'))
      ->setSetting('target_type', 'spectra_noun')
      ->setSetting('handler', 'default');

    $fields['action_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Action'))
      ->setDescription(t('The Spectra Action, being performed by the Actor'))
      ->setSetting('target_type', 'spectra_verb')
      ->setSetting('handler', 'default');

    $fields['object_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Object'))
      ->setDescription(t('The Spectra Object, which is having an action performed on it by the Actor'))
      ->setSetting('target_type', 'spectra_noun')
      ->setSetting('handler', 'default');

    $fields['context_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Context'))
      ->setDescription(t('The Spectra Context, in which the actor is performing the action'))
      ->setSetting('target_type', 'spectra_noun')
      ->setSetting('handler', 'default');

    $fields['parent_statement'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent Statement'))
      ->setDescription(t('The Parent statement, to which this item is a sub-statement'))
      ->setSetting('target_type', 'spectra_statement')
      ->setSetting('handler', 'default');

    return $fields;
  }

}