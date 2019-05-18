<?php

/**
 * @file
 * Contains Drupal\picture\PictureMappingForm.
 */

namespace Drupal\picture;

use Drupal\responsive_image\ResponsiveImageStyleForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the responsive image edit/add forms.
 */
class PictureMappingForm extends ResponsiveImageStyleForm {

  /**
   * Overrides Drupal\responsive_image\ResponsiveImageForm::form().
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The array containing the complete form.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $image_styles = image_style_options(TRUE);
    $image_styles[RESPONSIVE_IMAGE_EMPTY_IMAGE] = $this->t('- empty image -');
    $breakpoints = $this->breakpointManager->getBreakpointsByGroup($this->entity->getBreakpointGroup());
    foreach ($breakpoints as $breakpoint_id => $breakpoint) {
      foreach ($breakpoint->getMultipliers() as $multiplier) {
        $label = $multiplier . ' ' . $breakpoint->getLabel() . ' [' . $breakpoint->getMediaQuery() . ']';
        $form['keyed_styles'][$breakpoint_id][$multiplier] = array(
          '#type' => 'details',
          '#title' => $label,
        );
        $mapping_definition = $this->entity->getImageStyleMapping($breakpoint_id, $multiplier);
        $form['keyed_styles'][$breakpoint_id][$multiplier]['image_mapping_type'] = array(
          '#title' => $this->t('Image mapping type'),
          '#type' => 'radios',
          '#options' => array(
            '_none' => $this->t('Do not use this breakpoint'),
            'image_style' => $this->t('Use image styles'),
            'sizes' => $this->t('Use the sizes attribute'),
          ),
          '#default_value' => isset($mapping_definition['image_mapping_type']) ? $mapping_definition['image_mapping_type'] : '_none',
        );
        $form['keyed_styles'][$breakpoint_id][$multiplier]['image_mapping'] = array(
          '#type' => 'select',
          '#title' => $this->t('Image style'),
          '#options' => $image_styles,
          '#default_value' => isset($mapping_definition['image_mapping']) && is_string($mapping_definition['image_mapping']) ? $mapping_definition['image_mapping'] : '',
          '#description' => $this->t('Select an image style for this breakpoint.'),
          '#states' => array(
            'visible' => array(
              ':input[name="keyed_styles[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'image_style'),
            ),
          ),
        );
        $form['keyed_styles'][$breakpoint_id][$multiplier]['sizes'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Sizes'),
          '#default_value' => isset($mapping_definition['image_mapping']['sizes']) ? $mapping_definition['image_mapping']['sizes'] : '',
          '#description' => $this->t('Enter the value for the sizes attribute (e.g. "(min-width:700px) 700px, 100vw").'),
          '#states' => array(
            'visible' => array(
              ':input[name="keyed_styles[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'sizes'),
            ),
          ),
        );
        $form['keyed_styles'][$breakpoint_id][$multiplier]['sizes_image_styles'] = array(
          '#title' => $this->t('Image styles'),
          '#type' => 'checkboxes',
          '#options' => array_diff_key($image_styles, array('' => '')),
          '#default_value' => isset($mapping_definition['image_mapping']['sizes_image_styles']) && is_array($mapping_definition['image_mapping']['sizes_image_styles']) ? $mapping_definition['image_mapping']['sizes_image_styles'] : array(),
          '#states' => array(
            'visible' => array(
              ':input[name="keyed_styles[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'sizes'),
            ),
          ),
        );
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);
    // Only validate on edit.
    if ($form_state->hasValue('keyed_styles')) {
      $styles = $form_state->getValue('keyed_styles');
      foreach ($styles as $breakpoint_id => $multiplier_styles) {
        foreach ($multiplier_styles as $multiplier => $style) {
          if ($style['image_mapping_type'] == 'sizes') {
            $form_state->setValue(
              array(
                'keyed_styles',
                $breakpoint_id,
                $multiplier,
                'image_mapping',
              ),
              array(
                'sizes_image_styles' => array_filter($style['sizes_image_styles']),
                'sizes' => $style['sizes'],
              )
            );
          }
          elseif ($style['image_mapping_type'] != 'image_style') {
            $form_state->unsetValue(
              array('keyed_styles', $breakpoint_id, $multiplier)
            );
          }
          $form_state->unsetValue(
            array(
              'keyed_styles',
              $breakpoint_id,
              $multiplier,
              'sizes_image_styles',
            )
          );
          $form_state->unsetValue(
            array('keyed_styles', $breakpoint_id, $multiplier, 'sizes')
          );
        }
      }

    }
  }

}
