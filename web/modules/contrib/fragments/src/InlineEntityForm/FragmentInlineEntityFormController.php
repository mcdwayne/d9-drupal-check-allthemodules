<?php

namespace Drupal\fragments\InlineEntityForm;

/**
 * Class FragmentInlineEntityFormController.
 *
 * NOTE: This is a left-over from the D7 version and probably will not work
 * as-is on D8.
 */
class FragmentInlineEntityFormController extends \EntityInlineEntityFormController {

  /**
   * {@inheritdoc}
   */
  public function entityForm($entity_form, &$form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);

    $fragment = $entity_form['#entity'];
    $extra_fields = field_info_extra_fields('fragment', $fragment->type, 'form');

    $entity_form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $fragment->title,
      '#maxlength' => 255,
      // The label might be missing if the Title module has replaced it.
      '#weight' => !empty($extra_fields['title']) ? $extra_fields['title']['weight'] : -5,
    ];

    return $entity_form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultLabels() {
    return [
      'singular' => t('Fragment'),
      'plural' => t('Fragments'),
    ];
  }

}
