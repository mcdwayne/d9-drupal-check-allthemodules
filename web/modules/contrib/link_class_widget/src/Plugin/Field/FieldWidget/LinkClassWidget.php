<?php

/**
 * @file
 * Contains \Drupal\link_class_widget\Plugin\Field\FieldWidget\LinkClassWidget.
 */

namespace Drupal\link_class_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "link_class",
 *   label = @Translation("Link with class"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkClassWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'allowed_classes' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Let LinkWidget render the formElement so we just need to extend it.
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // If there is at least one class defined, we can render the select element.
    if(count($options = $this->getAllowedClasses()) > 0) {

      // We append the select element to _attributes, so the class will be
      // populated to the link HTML element by link module.
      $element['options']['attributes']['class'] = array(
        '#type' => 'select',
        '#title' => $this->t('Class'),
        '#options' => $options,
        '#default_value' => isset($items[$delta]->options['attributes']['class']) ? $items[$delta]->options['attributes']['class'] : NULL,
      );
    }

    return $element;
  }

  private function getAllowedClasses() {

    $options = array();

    // Get allowed classes from field settings.
    $classes_string = $this->getSetting('allowed_classes');
    $classes = array();

    if(!empty($classes_string)) {
      $classes = explode(
        '
', $classes_string);
    }

    // If there is at least one class defined, we can render the select element.
    if(count($classes) > 0) {

      foreach ($classes as $class) {
        $parts = explode('|', $class);
        if (count($parts) == 2) {
          $options[$parts[0]] = $parts[1];
        }
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    // Let LinkWidget render the element.
    $elements = parent::settingsForm($form, $form_state);

    // Just append the allowed_classes textfield.
    $elements['allowed_classes'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Allowed classes for this link field'),
      '#default_value' => $this->getSetting('allowed_classes'),
      '#description' => $this->t('Enter one class per line like: <strong>class_name|Class Name</strong>.'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    // Alter the settingsSummary to display allowed classes.
    $summary = parent::settingsSummary();
    $allowed_classes = $this->getSetting('allowed_classes');

    if (empty($allowed_classes)) {
      $summary[] = $this->t('No classes defined');
    }
    else {
      $summary[] = $this->t('Allowed classes: @allowed_classes', array('@allowed_classes' => str_replace('
', ', ', $allowed_classes)));
    }

    return $summary;
  }
}
