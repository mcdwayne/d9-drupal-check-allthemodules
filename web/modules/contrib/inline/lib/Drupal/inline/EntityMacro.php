<?php

/**
 * @file
 * Contains Drupal\inline\EntityMacro.
 */

namespace Drupal\inline;

class EntityMacro extends MacroBase {

  protected $entityType;

  protected $entity;

  /**
   * Implements MacroInterface::getType().
   */
  public function getType() {
    return 'entity';
  }

  /**
   * Implements MacroInterface::getParameters().
   */
  public function getParameters() {
    $args['type'] = array(
      '#datatype' => 'string',
      '#required' => TRUE,
      '#title' => t('Entity type'),
    );
    $args['id'] = array(
      '#datatype' => 'int',
      '#required' => TRUE,
      '#title' => t('Entity ID'),
      '#description' => t('The ID of an entity to embed.'),
    );
    $args['view_mode'] = array(
      '#datatype' => 'string',
      '#title' => t('View mode'),
      // @todo Make the default value configurable.
      '#default_value' => 'teaser',
    );
    return $args;
  }


  /**
   * Implements MacroInterface::validate().
   */
  public function validate(array $context) {
    if (!entity_get_info($this->params['type'])) {
      return t('The specified entity type %type does not exist.', array('%type' => $this->params['type']));
    }
    if (empty($this->params['id'])) {
      return t('An entity ID is required.');
    }
    if ($this->params['type'] == $context['entity']->entityType() && $this->params['id'] == $context['entity']->id()) {
      return t('The content @id cannot reference itself.', array('@id' => $this->params['id']));
    }
  }

  /**
   * Implements MacroInterface::prepareView().
   */
  public function prepareView(array $context) {
    if (empty($this->params['type']) || empty($this->params['id'])) {
      return;
    }
    $entity = entity_load($this->params['type'], $this->params['id']);
    if ($entity) {
      $this->entityType = $entity->entityType();
      $this->entity = $entity;
    }
  }

  /**
   * Implements MacroInterface::view().
   */
  public function view(array $context) {
    if (empty($this->entity)) {
      return '';
    }
    // @todo Add dependency on Entity API module for generic entity access?
    if ($this->entityType == 'node' && !node_access('view', $this->entity)) {
      return '';
    }
    // The inlined entity being rendered MUST be cloned before invoking
    // ENTITY_view(). The render process is not designed to be re-entrant in D7.
    // @see field_attach_prepare_view()
    // @see http://drupal.org/node/1289336
    $render_entity = clone $this->entity;

    // Call ENTITY_view() to invoke hook_entity_view() for FieldMacro.
    $function = $this->entityType . '_view';
    $elements = $function($render_entity, $this->params['view_mode']);
    return drupal_render($elements);
  }
}
