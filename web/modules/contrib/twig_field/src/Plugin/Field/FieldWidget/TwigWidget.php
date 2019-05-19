<?php

namespace Drupal\twig_field\Plugin\Field\FieldWidget;

use Drupal\codemirror_editor\Plugin\Field\FieldWidget\CodeMirrorEditorWidget;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the Twig field widget.
 *
 * @FieldWidget(
 *   id = "twig",
 *   label = @Translation("Template editor"),
 *   field_types = {"twig"},
 * )
 */
class TwigWidget extends CodeMirrorEditorWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['mode' => 'html_twig'] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['value']['#codemirror']['modeSelect'] = [
      'text/x-twig' => $this->t('Twig'),
      'html_twig' => $this->t('HTML/Twig'),
      'text/html' => $this->t('HTML'),
      'text/javascript' => $this->t('JavaScript'),
      'text/css' => $this->t('CSS'),
    ];

    $element['value']['#element_validate'] = [[get_class($this), 'validateTemplate']];

    $twig_field_name = $this->fieldDefinition->getName();
    $widget_html_id = $twig_field_name . '-' . $delta;
    $element['value']['#attributes']['data-tf-textarea'] = $widget_html_id;

    $element['footer'] = [
      '#type' => 'container',
      '#title' => $this->t('Twig context'),
      '#attributes' => ['class' => ['twig-field-editor-footer', 'container-inline']],
      '#weight' => 10,
    ];

    $options = ['' => $this->t('- Select -')];
    $default_context_names = array_keys(twig_field_default_context());
    $options['Global'] = array_combine($default_context_names, $default_context_names);

    $display_mode_id = $this->getFieldSetting('display_mode');
    $display_mode = EntityViewDisplay::load($display_mode_id);
    $components = $display_mode ? $display_mode->getComponents() : [];
    ksort($components);
    foreach ($components as $field_name => $component) {
      // Skip components that has not type property like 'Links' as we are not
      // supporting them.
      if ($twig_field_name != $field_name && isset($component['type'])) {
        $options['Fields'][$field_name] = $field_name;
      }
    }

    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $options['Other'][$entity_type] = $entity_type;

    $element['footer']['variables'] = [
      '#type' => 'select',
      '#title' => $this->t('Variables'),
      '#options' => $options,
      '#attributes' => ['data-tf-variables' => $widget_html_id],
    ];

    $element['footer']['insert'] = [
      '#type' => 'button',
      '#value' => $this->t('Insert'),
      '#attributes' => ['data-tf-insert' => $widget_html_id],
    ];

    $element['#attached']['library'][] = 'twig_field/editor';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    // Footer is only used on client side. Unset it to avoid configuration
    // schema errors.
    foreach ($values as &$value) {
      unset($value['footer']);
    }

    return parent::massageFormValues($values, $form, $form_state);
  }

  /**
   * Validation callback for a Template element.
   */
  public static function validateTemplate(&$element, FormStateInterface $form_state) {
    $build = [
      '#type' => 'inline_template',
      '#template' => $element['#value'],
      '#context' => twig_field_default_context(),
    ];
    try {
      \Drupal::service('renderer')->renderPlain($build);
    }
    catch (\Exception $exception) {
      $form_state->setError($element, t('Template error: @error', ['@error' => $exception->getMessage()]));
    }
  }

}
