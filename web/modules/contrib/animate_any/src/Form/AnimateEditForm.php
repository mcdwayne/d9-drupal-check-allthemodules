<?php

namespace Drupal\animate_any\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a edit form for edit/update the animation data from Animation list.
 */
class AnimateEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'animate_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $element = NULL) {

    $fetch = \Drupal::database()->select("animate_any_settings", "a");
    $fetch->fields('a');
    $fetch->condition('a.aid', $element);
    $fetch_results = $fetch->execute()->fetchAssoc();

    $form = [];
    $form['#attached']['library'][] = 'animate_any/animate';

    $form['#tree'] = TRUE;
    $form['parent_class'] = [
      '#title' => 'Add Parent Class',
      '#description' => $this->t('You can add body class like <em>body.front (for front page)</em> OR class with dot(.) prefix and Id with hash(#) prefix.'),
      '#type' => 'textfield',
      '#default_value' => $fetch_results['parent'],
    ];
    $form['aid'] = [
      '#type' => 'hidden',
      '#default_value' => $element,
    ];
    $form['animate_fieldset'] = [
      '#prefix' => '<div id="item-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#theme' => 'table',
      '#header' => [],
      '#rows' => [],
      '#attributes' => ['class' => 'animation'],
    ];
    // Json decode to get json to array.
    $data = json_decode($fetch_results['identifier']);
    foreach ($data as $key => $value) {
      $section_identity = [
        '#type' => 'textfield',
        '#title' => $this->t('Section identity'),
        '#default_value' => $value->section_identity,
        '#description' => $this->t("Add class with dot(.) prefix and Id with hash(#) prefix."),
      ];
      $section_animation = [
        '#type' => 'select',
        '#options' => animate_any_options(),
        '#title' => $this->t('Section Animation'),
        '#default_value' => $value->section_animation,
        '#attributes' => ['class' => ['select_animate']],
      ];
      $animation = [
        '#markup' => 'ANIMATE ANY',
        '#prefix' => '<h1 id="animate" class="" style="font-size: 30px;">',
        '#suffix' => '</h1>',
      ];
      $form['animate_fieldset'][$key] = [
        'section_identity' => &$section_identity,
        'section_animation' => &$section_animation,
        'animation' => &$animation,
      ];
      $form['animate_fieldset']['#rows'][$key] = [
        ['data' => &$section_identity],
        ['data' => &$section_animation],
        ['data' => &$animation],
      ];

      unset($section_identity);
      unset($section_animation);
      unset($animation);
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Settings'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $op = (string) $form_state->getValue('op');
    if ($op == 'Update Settings') {
      $parent = $form_state->getValue('parent_class');
      if (empty($parent)) {
        $form_state->setErrorByName("parent_class", $this->t("Please select parent class"));
      }
      foreach ($form_state->getValue('animate_fieldset') as $key => $value) {
        if (empty($value['section_identity'])) {
          $form_state->setErrorByName("animate_fieldset][{$key}][section_identity", $this->t("Please select section identity for row @key", ['@key' => $key]));
        }
        if ($value['section_animation'] == 'none') {
          $form_state->setErrorByName("animate_fieldset][{$key}][section_animation", $this->t("Please select section animation for row @key", ['@key' => $key]));
        }
      }
    }
  }

  /**
   * Submit for animate_any_settings.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update the data for current element.
    $parent = $form_state->getvalue('parent_class');
    $aid = $form_state->getvalue('aid');
    $identifiers = json_encode($form_state->getvalue('animate_fieldset'));
    $data = \Drupal::database()->update('animate_any_settings');
    $data->fields([
      'parent' => $parent,
      'identifier' => $identifiers,
    ]);
    $data->condition('aid', $aid);
    $valid = $data->execute();
    if ($valid) {
      drupal_set_message(t('Animation settings updated.'));
    }
  }

}
