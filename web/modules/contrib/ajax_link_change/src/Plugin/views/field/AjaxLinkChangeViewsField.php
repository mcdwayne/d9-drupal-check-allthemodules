<?php

namespace Drupal\ajax_link_change\Plugin\views\field;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("ajax_link_change_views_field")
 */
class AjaxLinkChangeViewsField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['id_entity'] = ['default' => 0];
    $options['entity_type'] = ['default' => ''];
    $options['entity_field_change'] = ['default' => ''];
    $options['current_value'] = ['default' => 0];
    $options['value_on'] = ['default' => 1];
    $options['value_off'] = ['default' => 0];
    $options['value_label_on'] = ['default' => "On"];
    $options['value_label_off'] = ['default' => "Off"];
    $options['link_classes'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $content_entity_types = [];
    $entity_type_definitions = \Drupal::entityTypeManager()->getDefinitions();

    foreach ($entity_type_definitions as $key => $definition) {
      if ($definition instanceof ContentEntityType) {
        $content_entity_types[$key] = $key;
      }
    }

    // Id of entity.
    $form['id_entity'] = [
      '#type' => 'textfield',
      '#title' => t('ID Entity'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->options['id_entity'],
    ];
    // Type of entity.
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => t('Type Entity'),
      '#required' => TRUE,
      '#default_value' => $this->options['entity_type'],
      '#options' => $content_entity_types,
    ];
    // The field wil be change value.
    $form['entity_field_change'] = [
      '#type' => 'textfield',
      '#title' => t('Field  Entity'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->options['entity_field_change'],
    ];
    // The current value of field.
    $form['current_value'] = [
      '#type' => 'textfield',
      '#title' => t('Current field value'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->options['current_value'],
    ];
    // Value field in on-states.
    $form['value_on'] = [
      '#type' => 'textfield',
      '#title' => t('Value on'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->options['value_on'],
    ];
    // Value of field in off-states.
    $form['value_off'] = [
      '#type' => 'textfield',
      '#title' => t('Value off'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->options['value_off'],
    ];
    // Value label field in on-states.
    $form['value_label_on'] = [
      '#type' => 'textfield',
      '#title' => t('Value label on'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->options['value_label_on'],
    ];
    // Value label of field in off-states.
    $form['value_label_off'] = [
      '#type' => 'textfield',
      '#title' => t('Value label off'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->options['value_label_off'],
    ];
    $form['link_classes'] = [
      '#type' => 'textfield',
      '#title' => t('link class'),
      '#size' => 60,
      '#maxlength' => 128,
      '#default_value' => $this->options['link_classes'],
    ];
    unset($form['alter']['help']['#states']);
    $form['help'] = $form['alter']['help'];
    unset($form['alter']);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    // Get all tokens in view.
    $tokens = $this->getRenderTokens([]);
    // Check the true value of $id_entity.
    $id_entity = $this->viewsTokenReplace($this->options['id_entity'], $tokens);
    // Check the true value of $current_value.
    $current_value = strip_tags($this->viewsTokenReplace($this->options['current_value'], $tokens));
    // Check the true value of $value_on.
    $value_on = strip_tags($this->viewsTokenReplace($this->options['value_on'], $tokens));
    // Check the true value of $value_off.
    $value_off = strip_tags($this->viewsTokenReplace($this->options['value_off'], $tokens));
    // Check the true value of $classes.
    $classes = strip_tags($this->viewsTokenReplace($this->options['link_classes'], $tokens));
    // Check the true value of $value_label_on.
    $value_label_on = strip_tags($this->viewsTokenReplace($this->options['value_label_on'], $tokens));
    // Check the true value of $value_label_off.
    $value_label_off = strip_tags($this->viewsTokenReplace($this->options['value_label_off'], $tokens));
    // Check the true value of $entity_type.
    $entity_type = strip_tags($this->options['entity_type']);
    // Check the true value of $entity_field_change.
    $entity_field_change = strip_tags($this->options['entity_field_change']);
    // Add space to css-classes.
    if ($classes != '') {
      $classes = ' ' . $classes;
    }
    // Add active css-class for current value of field & set label for checkbox.
    if ($value_on == $current_value) {
      $classes .= " active";
      $label = $value_label_on;
    }
    else {
      $label = $value_label_off;
    }
    // Create the ajax url for editing field.
    $url = Url::fromRoute('ajax_link_change.setvalue',
      [
        'entity_type' => $entity_type,
        'entity_id' => $id_entity,
        'field_name' => $entity_field_change,
        'value_ON' => $value_on,
        'value_OFF' => $value_off,
      ]
    );
    // Set value of params
    // return a array with theme,params and library.
    return [
      '#theme' => 'ajax_link_change',
      '#id_entity' => $id_entity,
      '#entity_type' => $entity_type,
      '#entity_field_change' => $entity_field_change,
      '#current_value' => $current_value,
      '#value_on' => $value_on,
      '#value_off' => $value_off,
      '#value_label_on' => $value_label_on,
      '#value_label_off' => $value_label_off,
      '#class_link' => $classes,
      '#label' => $label,
      "#ajax_url" => $url->toString(),
      '#attached' => [
        'library' => [
          'ajax_link_change/ajax_link_change-library',
        ],
      ],
    ];
  }

}
