<?php

/**
 * @file
 * Contains \Drupal\taxonomy_term_fields_manager\Form\TaxonomyTermSetFieldForm.
 */
namespace Drupal\taxonomy_term_fields_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TaxonomyTermSetFieldForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_term_set_field_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'taxonomy_term_fields.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $edit = NULL, $taxonomy_vocabulary = NULL) {

    $module_path = drupal_get_path('module', 'taxonomy_term_fields_manager');
    $form['#attached']['library'][] = 'taxonomy_term_fields_manager/taxonomy-term-fields';

    $form['manual_title'] = array(
      '#markup' => "<h2>Select custom fields to categories the display for the view </h2></br>",
    );

    // During initial form build, add the term entity to the form state for use
    // during form building and processing. During a rebuild, use what is in the
    // form state.

    $entity_type_id = 'taxonomy_term';
    $bundle = $taxonomy_vocabulary;
    foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        $bundleFields[$field_name]['label'] = $field_definition->getLabel();
      }
    }

    if (!empty($bundleFields)) {
      foreach ($bundleFields as $key => $value) {
        $db_selected_field = $taxonomy_vocabulary . '_' . $key . '_cstm_taxonomy';
         $selected_field_name = \Drupal::state()->get($db_selected_field);

        $form[$key] = [
          '#title' => t($value['label']),
          '#type' => 'checkbox',
          '#default_value' => '',
          '#attributes' => !empty($selected_field_name) ? [
            'checked' => 'checked'
            ] : '',
          '#prefix' => '<div class="fullwidth">',
          '#suffix' => '</div>',
        ];
      }
      $form['taxonomy_vocabulary'] = array(
        '#type' => 'hidden',
        '#default_value' => $taxonomy_vocabulary,
      );

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );
    }
    else {
       $form['msg'] = array(
        '#markup' => '<b> No Custom fields available in this taxonomy. </b>',
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $taxonomy_vocabulary = $form_state->getValue('taxonomy_vocabulary');
    $fields_input = $form_state->getUserInput();

    foreach ($fields_input as $key => $value) {
      if (0 === strpos($key, 'field_')) {
        if ($form_state->getValue($key) == 1) {
             \Drupal::state()->set($taxonomy_vocabulary . '_' . $key . '_cstm_taxonomy', $key);
        }
        else {
          $db_selected_field = $taxonomy_vocabulary . '_' . $key . '_cstm_taxonomy';
          $set_field_name = \Drupal::state()->get($db_selected_field);
          if(!empty($set_field_name)){
            \Drupal::state()->delete($taxonomy_vocabulary . '_' . $key . '_cstm_taxonomy');
          }
        }
      }
    }
    $url = '/admin/structure/taxonomy/manage/' . $taxonomy_vocabulary . '/overview';
    $response = new RedirectResponse($url);
    $response->send();
  }
}

