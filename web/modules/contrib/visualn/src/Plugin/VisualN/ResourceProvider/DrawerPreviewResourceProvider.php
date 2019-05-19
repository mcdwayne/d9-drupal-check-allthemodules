<?php

// @todo: mostly it is the same as Generated Data standard resource provider
//    but also has a drawer context that allows to autodetect corresponding data generator
//    and additional controls, e.g. to reset to default or show/hide relevant
//    data generators

// @todo: rename to "Compatible generator data (for drawer preview)"

namespace Drupal\visualn\Plugin\VisualN\ResourceProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\Element;

/**
 * Provides a 'Drawer Preview Resource Provider' VisualN resource provider.
 *
 * @VisualNResourceProvider(
 *  id = "visualn_drawer_preview",
 *  label = @Translation("Drawer Preview Resource Provider"),
 *  context = {
 *    "visualn_drawer_id" = @ContextDefinition("string", label = @Translation("VisualN Drawer Id"), required = TRUE)
 *  }
 * )
 */
class DrawerPreviewResourceProvider extends GeneratedResourceProvider {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);


    // @todo: get from context
    $base_drawer_id = $this->getContextValue('visualn_drawer_id');
    $compatible_dgs = [];
    $dg_definitions = $this->visualNDataGeneratorManager->getDefinitions();
    foreach ($dg_definitions as $id => $dg_definition) {
      if (!empty($dg_definition['compatible_drawers'])) {
        // @todo: is it any drawer or just base drawers?
        foreach ($dg_definition['compatible_drawers'] as $drawer_id) {
          $compatible_dgs[$drawer_id][] = $id;
        }
      }
    }

    // @todo: or other drawers could also provide info (using some hook)
    //   about "compatible drawers" which can share generator providers
    //   with them

    $default_data_generator_id = '';

    // keep compatible generators for ::preRenderShowCompatible() callback
    $form['#compatible_dgs'] = [];
    if (!empty($compatible_dgs[$base_drawer_id])) {
      $default_data_generator_id = reset($compatible_dgs[$base_drawer_id]);
      $form['data_generator_id']['#default_value'] = $default_data_generator_id;
      $form['#compatible_dgs'] = $compatible_dgs[$base_drawer_id];
    }
    else {
      // @todo: add message 'No compatible Data generators found'
    }


    // @todo: may context be also used with subdrawers but not only base drawers?

    // @todo: add context of the current drawer used


    // limit list to only compatible data generators, add ajax action to the checkbox
    $ajax_wrapper_id = implode('-', array_merge($form['#array_parents'], ['show_compatible'])) .'-ajax-wrapper';

    // avoid overriding prefix and suffix if already set somewhere else
    $form['#prefix'] = isset($form['#prefix']) ? $form['#prefix'] : '';
    $form['#suffix'] = isset($form['#suffix']) ? $form['#suffix'] : '';
    $form['#prefix'] = '<div id="' . $ajax_wrapper_id . '">' . $form['#prefix'];
    $form['#suffix'] = $form['#suffix'] . '</div>';
    $form['show_compatible'] = [
      '#type' => 'checkbox',
      '#title' => t('Show only compatible generators'),
      '#default_value' => !empty($default_data_generator_id),
      '#ajax' =>  [
        'callback' => [get_called_class(), 'ajaxCallbackShowCompatible'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#weight' => -50,
    ];
    $form['#pre_render'][] = static::class . '::preRenderShowCompatible';

/*
    $form['reset'] = [
      '#type' => 'submit',
      '#value' => t('Reset'),
      //'#weight' => -50,
    ];
*/

    return $form;
  }

  /**
   * Return data generator configuration form via ajax request at "show compatible" option change.
   */
  public static function ajaxCallbackShowCompatible(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element;
  }

  /**
   * Hide incompatible drawer generators.
   */
  public static function preRenderShowCompatible($build) {

    $selected_generator_id = $build['data_generator_id']['#value'];
    if ($build['show_compatible']['#value']) {
      $visible_dgs = $build['#compatible_dgs'];
      if (!empty($selected_generator_id) && !in_array($selected_generator_id, $visible_dgs)) {
        $visible_dgs[] = $selected_generator_id;
      }

      foreach ($build['data_generator_id']['#options'] as $k => $options) {
        if ($k != '' && !in_array($k, $visible_dgs)) {
          unset($build['data_generator_id']['#options'][$k]);
        }
      }
    }

    // @todo: or should be done in ajaxCallbackShowCompatible() ?
    // open generator config by default when changin "show compatible" option
    if (!empty($selected_generator_id)) {
      $build['generator_container'][$selected_generator_id]['#open'] = TRUE;
    }

    return $build;
  }

}
