<?php

namespace Drupal\field_group_modal_bootstrap\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormState;
use Drupal\Core\Template\Attribute;
use Drupal\field_group\FieldGroupFormatterBase;
use Drupal\field_group_modal_bootstrap\Element\ModalElement;

/**
 * Modal element.
 *
 * @FieldGroupFormatter(
 *   id = "modal",
 *   label = @Translation("Modal Bootstrap"),
 *   description = @Translation("Add a modal Bootstrap element"),
 *   supported_contexts = {
 *     "view"
 *   }
 * )
 */
class ModalBootstrap extends FieldGroupFormatterBase {

  /**
   * Current modal identifier.
   *
   * @var string
   */
  protected $elementId;

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);
    $this->getGroupId();

    $element_attributes = new Attribute();

    if ($this->getSetting('attributes')) {

      // This regex split the attributes string so that we can pass that
      // later to drupal_attributes().
      preg_match_all('/([^\s=]+)="([^"]+)"/', $this->getSetting('attributes'), $matches);

      // Put the attribute and the value together.
      foreach ($matches[1] as $key => $attribute) {
        $element_attributes[$attribute] = $matches[2][$key];
      }

    }

    // Add the id to the attributes array.
    if ($this->getSetting('id')) {
      $element_attributes['id'] = Html::getId($this->getSetting('id'));
    }

    // Add the classes to the attributes array.
    $classes = $this->getClasses();
    if (!empty($classes)) {
      if (!isset($element_attributes['class'])) {
        $element_attributes['class'] = [];
      }
      // If user also entered class in the attributes textfield,
      // force it to an array.
      else {
        $element_attributes['class'] = [$element_attributes['class']];
      }
      $element_attributes['class'] = array_merge($classes, $element_attributes['class']->value());
    }

    $content_id = $this->elementId . '--content';

    $text_button = empty($this->getSetting('text_button')) ? $this->getLabel() : $this->getSetting('text_button');
    $button = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => [
        'class' => ['btn', 'btn-default', 'field--group-modal-bootstrap-button'],
        'data-toggle' => ['modal'],
        'data-target' => ['#' . $content_id],
      ],
      '#value' => $text_button,
    ];

    $element['#button'] = $button;
    $element['#id'] = $content_id;
    $element['#type'] = 'field_group_modal_bootstrap';
    $element['#attributes'] = $element_attributes;
    if ($this->getSetting('show_label')) {
      $element['#title'] = Html::escape($this->getLabel());
    }

    $form_state = new FormState();
    ModalElement::processModalElement($element, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $form['show_label'] = [
      '#title' => $this->t('Show label'),
      '#type' => 'select',
      '#options' => [0 => $this->t('No'), 1 => $this->t('Yes')],
      '#default_value' => $this->getSetting('show_label'),
      '#weight' => 1,
      '#attributes' => [
        'data-fieldgroup-selector' => 'show_label',
      ],
    ];

    $form['text_button'] = [
      '#title' => $this->t('Text button'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('text_button'),
      '#weight' => 2,
    ];

    $form['attributes_button'] = [
      '#title' => $this->t('Attributes button'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('attributes_button'),
      '#description' => $this->t('E.g. name="anchor"'),
      '#weight' => 3,
    ];

    $form['attributes'] = [
      '#title' => $this->t('Attributes'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('attributes'),
      '#description' => $this->t('E.g. name="anchor"'),
      '#weight' => 4,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = parent::settingsSummary();

    if ($this->getSetting('text_button')) {
      $summary[] = $this->t('Text button: @button',
        ['@button' => $this->getSetting('text_button')]
      );
    }
    if ($this->getSetting('attributes')) {
      $summary[] = $this->t('Attributes: @attributes',
        ['@attributes' => $this->getSetting('attributes')]
      );
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
      'show_label' => 1,
      'attributes' => '',
      'attributes_button' => 'class="btn-sm"',
      'text_button' => t('Show fields'),
    ] + parent::defaultSettings($context);

    return $defaults;

  }

  /**
   * Return current group ID.
   *
   * @return string
   *   Current group ID.
   */
  protected function getGroupId() {
    if (empty($this->elementId)) {

      if ($this->getSetting('id')) {
        $this->elementId = $this->getSetting('id');
      }
      else {
        $this->elementId = 'field_' . $this->group->group_name;
      }

      Html::setIsAjax(FALSE);
      $this->elementId = Html::getUniqueId($this->elementId);
    }

    return $this->elementId;
  }

  /**
   * Get the classes to add to the group.
   */
  protected function getClasses() {

    $classes = [];
    $classes[] = 'field--group-' . str_replace('_', '-', $this->getBaseId() . '-bootstrap');
    $classes[] = 'field--' . str_replace('_', '-', $this->group->group_name);
    if ($this->getSetting('classes')) {
      $classes = array_merge($classes, explode(' ', trim($this->getSetting('classes'))));
    }

    return $classes;
  }

}
