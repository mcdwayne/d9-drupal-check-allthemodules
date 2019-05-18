<?php

/**
 * @file
 * Contains Drupal\inline\FieldMacro.
 */

namespace Drupal\inline;

class FieldMacro extends MacroBase {

  protected $fieldInfo;

  protected $entityType;

  protected $entityID;

  protected $entity;

  protected $formatterInfo;

  /**
   * Implements MacroInterface::getType().
   */
  public function getType() {
    return 'field';
  }

  /**
   * Implements MacroInterface::getParameters().
   */
  public function getParameters() {
    $args['type'] = array(
      '#datatype' => 'string',
      '#required' => TRUE,
      '#title' => t('Entity type'),
      '#description' => t('The type of the entity to embed a field from.'),
    );
    $args['id'] = array(
      '#datatype' => 'int',
      '#required' => TRUE,
      '#title' => t('Entity ID'),
      '#description' => t('The ID of the entity to embed a field from.'),
      '#default_value' => 0,
    );
    // @todo Add vid.
    $args['name'] = array(
      '#required' => TRUE,
      '#title' => t('Field name'),
      '#description' => t('The name of the field to embed.'),
    );
    $args['view_mode'] = array(
      '#datatype' => 'string',
      '#title' => t('View mode'),
      '#default_value' => 'full',
    );
    $args['render_original'] = array(
      '#datatype' => 'int',
      '#title' => t('Render original?'),
      '#description' => t('A optional setting for specifing visibility on the original field'),
      '#default_value' => 0,
    );
    $args['formatter'] = array(
      '#title' => t('Formatter'),
      '#description' => t('The name of a formatter to use'),
    );
    $args['label'] = array(
      '#datatype' => 'string',
      '#title' => t('Display label'),
      '#description' => t('The label display option to use'),
      '#allowed_values' => array(
        'hidden' => 'Label is not displayed',
        'above' => 'Label displayed above the field',
        'inline' => 'Label displayed alongside the field',
      ),
    );
    return $args;
  }

  protected function getFieldInfo() {
    if (!isset($this->fieldInfo)) {
      $this->fieldInfo = field_info_field($this->params['name']);
    }
    return $this->fieldInfo;
  }

  protected function getFormatterInfo() {
    if (!isset($this->formatterInfo)) {
      $this->formatterInfo = field_info_formatter_types($this->params['formatter']);
    }
    return $this->formatterInfo;
  }

  /**
   * Implements MacroInterface::validate().
   */
  public function validate(array $context) {
    if (!$this->getFieldInfo()) {
      return t('%name is not a valid field name.', array('%name' => $this->params['name']));
    }
    // @todo Add validation to disallow self-reference.
    if (isset($this->params['formatter'])) {
      if (!$this->getFormatterInfo()) {
        return t('%name is not a valid field formatter.', array('%name' => $this->params['formatter']));
      }
      if (!in_array($this->fieldInfo['type'], array_values($this->formatterInfo['field types']))) {
        return t('%name is not a valid formatter for %type fields.', array(
          '%name' => $this->params['formatter'],
          '%type' => $this->fieldInfo['type'],
        ));
      }
    }
  }

  /**
   * Implements MacroInterface::prepareView().
   */
  public function prepareView(array $context) {
    if (!$this->getFieldInfo()) {
      return;
    }
    // Special case: id 0 (zero) refers to the current entity in the context;
    // i.e., the entity in which the (text) field is contained.
    if (empty($this->params['id'])) {
      $this->entityType = $context['entity_type'];
      $this->entity = $context['entity'];
    }
    elseif (!empty($this->params['type']) && !empty($this->params['id'])) {
      $entity = entity_load($this->params['type'], $this->params['id']);
      // @todo Proper error handling.
      if (empty($entity)) {
        return;
      }
      $this->entityType = $entity->entityType();
      $this->entity = $entity;
    }
    // @todo Proper error handling.
    if (empty($this->entity)) {
      return;
    }
    // Retrieve formatter info.
    if (isset($this->params['formatter'])) {
      if ($info = $this->getFormatterInfo()) {
        $this->formatterInfo['type'] = $this->params['formatter'];
      }
    }
  }

  /**
   * Implements MacroInterface::view().
   */
  public function view(array $context) {
    if (!isset($this->fieldInfo, $this->entityType, $this->entity)) {
      return '';
    }
    if (!field_access('view', $this->fieldInfo, $this->entityType, $this->entity)) {
      return '';
    }
    $field_name = $this->params['name'];
    $langcode = !empty($this->entity->language) ? $this->entity->language : NULL;

    $instance = field_info_instance($this->entityType, $field_name, $this->entity->bundle());
    $display = field_get_display($instance, $this->params['view_mode'], $this->entity);
    if (isset($this->formatterInfo)) {
      $display['type'] = $this->formatterInfo['type'];
      $display['module'] = $this->formatterInfo['module'];
      $display['settings'] = field_info_formatter_settings($display['type']);

      // Pass all unknown macro parameters as settings to the formatter.
      // @todo Any security issues? (Formatter settings are not validated.)
      // @todo Also apply settings if no formatter was specified?
      $macro_formatter_settings = array_diff_key($this->params, $this->getParameters());
      $display['settings'] = array_merge($display['settings'], $macro_formatter_settings);
    }

    // Allow the macro to override the field label.
    // @todo Validate that value is one of possible options.
    if (isset($this->params['label'])) {
      $display['label'] = $this->params['label'];
    }

    $elements = field_view_field($this->entityType, $this->entity, $field_name, $display, $langcode);
    if (isset($this->params['render_original']) && empty($this->params['render_original'])) {
      $this->entity->inline['remove_fields'][] = $field_name;
    }
    return drupal_render($elements);
  }
}
