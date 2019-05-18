<?php

namespace Drupal\field_group_label_classes\Plugin\field_group\FieldGroupFormatter;

use Drupal\Core\Form\FormState;
use Drupal\field_group\Plugin\field_group\FieldGroupFormatter\HtmlElement as HtmlElementPlugin;
use Drupal\field_group\Element\HtmlElement;

/**
 * Plugin implementation of the 'html_element' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "html_element",
 *   label = @Translation("HTML element"),
 *   description = @Translation("This fieldgroup renders the inner content in a
 *   HTML element with classes and attributes."), supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class HtmlClassTitleElement extends HtmlElementPlugin {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);
    if ($this->getSetting('show_label')) {
      $element['#title_classes'] = $this->getSetting('title_classes');
    }
    $form_state = new FormState();
    HtmlElement::processHtmlElement($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();
    $form['title_classes'] = [
      '#title' => $this->t('Classes for title'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('title_classes'),
      '#weight' => 3,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = parent::settingsSummary();

    if ($this->getSetting('title_classes')) {
      $summary[] = $this->t('Classes title: @title_classes',
        ['@title_classes' => $this->getSetting('title_classes')]
      );
    }

    return $summary;
  }

}
