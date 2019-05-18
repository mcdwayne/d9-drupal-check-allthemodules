<?php

/**
 * @file
 * Imagepin form implementations used in the imagepin.module file.
 */

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Component\Serialization\Json;

/**
 * Builds the imagepin settings element for the given field formatter form.
 *
 * @param \Drupal\Core\Field\FormatterInterface $plugin
 *   The field formatter plugin instance.
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
 *   The corresponding field definition.
 * @param string $view_mode
 *   The entity view mode.
 * @param array $form
 *   The (entire) configuration form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 *
 * @return array
 *   The settings element as a (sub-)form array.
 */
function imagepin_build_formatter_settings_form(FormatterInterface $plugin, FieldDefinitionInterface $field_definition, $view_mode, array $form, FormStateInterface $form_state) {
  $element = [
    '#type' => 'fieldset',
    '#title' => t('Imagepin settings'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  ];

  $element['pinable'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable users to pin widgets on this image.'),
    '#default_value' => $plugin->getThirdPartySetting('imagepin', 'pinable'),
  ];

  $image_style = $plugin->getThirdPartySetting('imagepin', 'image_style');
  $styles = image_style_options(FALSE);
  $element['image_style'] = [
    '#type' => 'select',
    '#title' => t('Image style to use for dragging the pins'),
    '#default_value' => isset($image_style) ? $image_style : key($styles),
    '#options' => $styles,
    '#states' => [
      'invisible' => [
        ':input[name="fields[' . $field_definition->getName() . '][settings_edit_form][third_party_settings][imagepin][pinable]"]' => ['checked' => FALSE],
      ],
    ],
  ];

  $breakpoint = $plugin->getThirdPartySetting('imagepin', 'breakpoint');
  $element['breakpoint'] = [
    '#type' => 'number',
    '#title' => t('Breakpoint from Mobile to Desktop'),
    '#field_prefix' => 'min-width',
    '#default_value' => isset($breakpoint) ? $breakpoint : '1024',
    '#description' => t('Leave empty to always use the mobile display variant, or set 0 to always use the desktop variant.'),
    '#field_suffix' => 'px',
    '#states' => [
      'invisible' => [
        ':input[name="fields[' . $field_definition->getName() . '][settings_edit_form][third_party_settings][imagepin][pinable]"]' => ['checked' => FALSE],
      ],
    ],
  ];

  return $element;
}

/**
 * Adds the link to the pin form to an image form element.
 *
 * See imagepin_field_widget_form_alter()
 *
 * @param array &$element
 *   The element array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param array $context
 *   The context array.
 */
function imagepin_add_link_to_form_element(array &$element, FormStateInterface $form_state, array $context) {
  $field_name = $context['items']->getName();
  $entity = $context['items']->getEntity();
  $entity_type = $entity->getEntityTypeId();
  $bundle = $entity->bundle();
  $enabled_view_modes = \Drupal::service('entity_display.repository')
    ->getViewModeOptionsByBundle($entity_type, $bundle);
  $view_storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');

  foreach (array_keys($enabled_view_modes) as $view_mode) {
    $content = $view_storage->load($entity_type . '.' . $bundle . '.' . $view_mode)->get('content');
    if (empty($content[$field_name]['third_party_settings']['imagepin']['pinable'])
    || empty($content[$field_name]['third_party_settings']['imagepin']['image_style'])) {
      continue;
    }
    $arguments = [
      'image_fid' => reset($element['#default_value']['fids']),
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'language' => $entity->language()->getId(),
      'id' => 'new',
    ];
    if (!$entity->isNew()) {
      $arguments['id'] = $entity->id();
    }
    $element['imagepin'] = [
      '#prefix' => '<div>',
      '#suffix' => '</div>',
      '#weight' => 100,
      '#type' => 'link',
      '#title' => t('Pin widgets on this image'),
      '#url' => Url::fromRoute('imagepin.pin_widgets', $arguments),
      '#attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => '90%']),
      ],
    ];
    break;
  }
}
