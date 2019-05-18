<?php

namespace Drupal\multistep_submit\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'multistep form' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "multistep_submit",
 *   label = @Translation("Multistep Submit"),
 *   description = @Translation("This fieldgroup renders the inner content in a fieldset with the title as legend."),
 *   supported_contexts = {
 *     "form",
 *   }
 * )
 */
class Multistep extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {

    $element += array(
      '#type' => 'fieldset',
      '#title' => Html::escape($this->t($this->getLabel())),
      '#pre_render' => array(),
      '#attributes' => array(),
    );
    
    if ($this->getSetting('description')) {
      $element += array(
        '#description' => $this->getSetting('description'),
      );

      // When a fieldset has a description, an id is required.
      if (!$this->getSetting('id')) {
        $element['#id'] = Html::getId($this->group->group_name);
      }

    }

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element['#attributes'] += array('class' => $classes);
    }

    if ($this->getSetting('required_fields')) {
      $element['#attached']['library'][] = 'field_group/formatter.fieldset';
      $element['#attached']['library'][] = 'field_group/core';
    }
    //Attached multistep jqueyr steps configuration
    $config = \Drupal::configFactory()->getEditable('multistep_submit_form.settings');
    $element['#attached']['drupalSettings']['multistep_submit']['buttons'] = [
      'next' => $config->get('multistep_submit_next_btn') ? $config->get('multistep_submit_next_btn') : t('Next'),
      'cancel' => $config->get('multistep_submit_cancel_btn') ? $config->get('multistep_submit_cancel_btn') : t('Cancel'),
      'finish' => $config->get('multistep_submit_finish_btn')  ? $config->get('multistep_submit_finish_btn') : t('Finished'),
      'previous' => $config->get('multistep_submit_previous_btn') ? $config->get('multistep_submit_previous_btn') : t('Previous'),
    ];
    $element['#attached']['drupalSettings']['multistep_submit']['transition'] = $config->get('multistep_submit_transition_effects') ? $config->get('multistep_submit_transition_effects') : 'slideLeft';
    $element['#attached']['drupalSettings']['multistep_submit']['orientation'] = $config->get('multistep_submit_orientation') ? $config->get('multistep_submit_orientation') : 'horizontal';
    //attached library jquery steps
    $element['#attached']['library'][] = 'multistep_submit/multistep_submit.integration';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $form['description'] = array(
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('description'),
      '#weight' => -4,
    );

    if ($this->context == 'form') {
      $form['required_fields'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Mark group as required if it contains required fields.'),
        '#default_value' => $this->getSetting('required_fields'),
        '#weight' => 2,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = parent::settingsSummary();

    if ($this->getSetting('required_fields')) {
      $summary[] = $this->t('Mark as required');
    }

    if ($this->getSetting('description')) {
      $summary[] = $this->t('Description : @description',
        array('@description' => $this->getSetting('description'))
      );
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = array(
      'description' => '',
    ) + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;
  }

}
