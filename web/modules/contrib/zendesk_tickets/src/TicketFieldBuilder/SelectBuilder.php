<?php

namespace Drupal\zendesk_tickets\TicketFieldBuilder;

/**
 * Provides a select field element builder.
 */
class SelectBuilder extends BaseBuilder {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    $element = parent::getElement();
    if (empty($element)) {
      return [];
    }

    $element['#type'] = 'select';
    $field_options = [];
    if (!empty($this->field->custom_field_options)) {
      $field_options = $this->field->custom_field_options;
    }
    elseif (!empty($this->field->system_field_options)) {
      $field_options = $this->field->system_field_options;
    }

    if ($field_options) {
      $default_value = NULL;
      $options = [];
      foreach ($field_options as $field_option) {
        if (isset($field_option->value) && isset($field_option->name)) {
          $options[$field_option->value] = $field_option->name;
          if (isset($field_option->default) && !empty($field_option->default)) {
            $default_value = $field_option->value;
          }
        }
      }

      $element['#options'] = $options;
      $element['#defualt_value'] = $default_value;
    }

    return $element;
  }

}
