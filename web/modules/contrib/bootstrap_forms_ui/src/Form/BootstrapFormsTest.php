<?php

namespace Drupal\bootstrap_forms_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility;
use Drupal\Core\Render\Element;

/**
 * Implements the ModalForm form controller.
 *
 * This example demonstrates implementation of a form that is designed to be
 * used as a modal form.  To properly display the modal the link presented by
 * the \Drupal\fapi_example\Controller\Page page controller loads the Drupal
 * dialog and ajax libraries.  The submit handler in this class returns ajax
 * commands to replace text in the calling page after submission .
 *
 * @see \Drupal\Core\Form\FormBase
 */
class BootstrapFormsTest extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Container for all horizontal forms.
    $form['horizontal'] = array(
      '#type' => 'fieldset',
      '#title' => 'Horizontal Forms',
      '#description' => 'This is the fieldset description for the horizontal forms fieldset.',
      '#attributes' => array(
        'id' => 'horizontal',
      ),
    );

    $form['tooltips'] = array(
      '#type' => 'fieldset',
      '#title' => 'Horizontal Forms (with tooltips)',
      '#description' => 'This is the fieldset description for the horizontal forms fieldset.',
      '#attributes' => array(
        'id' => 'horizontal-tooltips',
      ),
    );

    $form['popovers'] = array(
      '#type' => 'fieldset',
      '#title' => 'Horizontal Forms (with popovers)',
      '#description' => 'This is the fieldset description for the horizontal forms fieldset.',
      '#attributes' => array(
        'id' => 'horizontal-popovers',
      ),
    );

    // Container for all basic forms.
    $form['basic'] = array(
      '#type' => 'fieldset',
      '#title' => 'Basic Forms',
      '#description' => 'This is the fieldset description for the basic forms fieldset.',
      '#attributes' => array(
        'id' => 'basic',
      ),
    );

    // Container for all basic forms.
    $form['basic-auto-width'] = array(
      '#type' => 'fieldset',
      '#title' => 'Basic Forms (automatic width)',
      '#description' => 'This is the fieldset description for the basic forms fieldset.',
      '#attributes' => array(
        'id' => 'basic-auto-width',
      ),
    );

    // Container for all inline forms.
    $form['inline'] = array(
      '#type' => 'fieldset',
      '#title' => 'Inline Forms',
      '#description' => 'This is the fieldset description for the inline forms fieldset.',
      '#attributes' => array(
        'id' => 'inline',
      ),
    );

    // Textfield.
    $form['horizontal']['textfield'] = array(
      '#type' => 'textfield',
      '#title' => 'Textfield',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#attributes' => array(
        'placeholder' => 'This is some placeholder text'
      ),
      '#form_type' => 'horizontal',
      '#title_grid' => array(
        'columns' => array(
          'md' => 3,
        )
      ),
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
      ),
    );

    // Textarea.
    $form['horizontal']['textarea'] = array(
      '#type' => 'textarea',
      '#title' => 'Textarea',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#attributes' => array(
        'placeholder' => 'This is some placeholder text'
      ),
      '#value' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum',
      '#form_type' => 'horizontal',
      '#title_grid' => array(
        'columns' => array(
          'md' => 3,
        )
      ),
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
      ),
    );

    // Select.
    $form['horizontal']['select'] = array(
      '#type' => 'select',
      '#title' => 'Select',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#attributes' => array(
      ),
      '#options' => array(
        1, 2, 3, 4, 5, 6,
      ),
      '#form_type' => 'horizontal',
      '#title_grid' => array(
        'columns' => array(
          'md' => 3,
        )
      ),
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
      ),
    );

    // Checkbox.
    $form['horizontal']['checkbox'] = array(
      '#type' => 'checkbox',
      '#title' => 'Checkbox (Horizontal)',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#form_type' => 'horizontal',

      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
        'offset' => array(
          'md' => 3,
        ),
      ),
    );

    // Checkboxes
    $form['horizontal']['checkboxes'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#options' => array(
        '1'=>'test',
        '2' => 'testing'
      ),
      '#form_type' => 'horizontal',
      '#title_grid' => array(
        'columns' => array(
          'md' => 3,
        )
      ),
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
      ),
    );

    // Checkboxes.
    $form['horizontal']['checkboxesinline'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes Inline',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#options' => array(
        '1' => 'Item #1',
        '2' => 'Item #2',
        '3' => 'Item #3',
        '4' => 'Item #4',
        '5' => 'Item #5',
        '6' => 'Item #6',
        '7' => 'Item #7',
        '8' => 'Item #8',
        '9' => 'Item #9',
        '10' => 'Item #10',
      ),
      '#form_type' => 'horizontal',
      '#title_grid' => array(
        'columns' => array(
          'md' => 3
        )
      ),
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
      ),
      '#child_grid' => array(
        'columns' => array(
          'md' => 4,
        ),
      ),
    );

    // Checkboxes
    $form['horizontal']['radios'] = array(
      '#type' => 'radios',
      '#title' => 'radios',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#options' => array(
        '1'=>'test',
        '2' => 'testing'
      ),
      '#form_type' => 'horizontal',
      '#title_grid' => array(
        'columns' => array(
          'md' => 3,
        ),
      ),
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
      ),
    );

    // Checkboxes.
    $form['horizontal']['radiosinline'] = array(
      '#type' => 'radios',
      '#title' => 'Radios Inline',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#options' => array(
        '1' => 'Item #1',
        '2' => 'Item #2',
        '3' => 'Item #3',
        '4' => 'Item #4',
        '5' => 'Item #5',
        '6' => 'Item #6',
        '7' => 'Item #7',
        '8' => 'Item #8',
        '9' => 'Item #9',
        '10' => 'Item #10',
      ),
      '#form_type' => 'horizontal',
      '#title_grid' => array(
        'columns' => array(
          'md' => 3
        ),
      ),
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
      ),
      '#child_grid' => array(
        'columns' => array(
          'md' => 4,
        ),
      ),
    );

    $form['horizontal']['radio'] = array(
      '#type' => 'radio',
      '#form_type' => 'horizontal',
      '#title' => 'Radio (Horizontal)',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
        'offset' => array(
          'md' => 3,
        ),
      ),
    );

    // File.
    $form['horizontal']['file'] = array(
      '#type' => 'file',
      '#form_type' => 'horizontal',
      '#title' => 'File',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#title_grid' => array(
        'columns' => array(
          'md' => 3,
        ),
      ),
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
      ),
    );

    // Date.
    $form['horizontal']['date'] = array(
      '#type' => 'date',
      '#form_type' => 'horizontal',
      '#title' => 'Date',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#title_grid' => array(
        'columns' => array(
          'md' => 3,
        ),
      ),
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
      ),
    );

    // DateList.
    $form['horizontal']['datelist'] = array(
      '#type' => 'datelist',
      '#form_type' => 'horizontal',
      '#title' => 'datelist',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#title_grid' => array(
        'columns' => array(
          'md' => 3,
        ),
      ),
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
      ),
    );

    // Range
    $form['horizontal']['range'] = array(
      '#type' => 'range',
      '#form_type' => 'horizontal',
      '#min' => 0,
      '#max' => 100,
      '#title' => 'Range',
      '#description' => 'Duis leo. Phasellus consectetuer vestibulum elit. Aenean ut eros et nisl sagittis vestibulum.',
      '#description_display' => 'after',
      '#title_grid' => array(
        'columns' => array(
          'md' => 3,
        ),
      ),
      '#element_grid' => array(
        'columns' => array(
          'md' => 6,
        ),
      ),
    );

    // Tooltip forms.
    foreach (Element::children($form['horizontal']) as $key) {
      $form['tooltips'][$key] = $form['horizontal'][$key];
      $form['tooltips'][$key]['#prefix_tooltip'] = array(
        'title' => 'testing',
      );
      $form['tooltips'][$key]['#suffix_tooltip'] = array(
        'title' => 'testing',
      );
    }

    // Popover forms.
    foreach (Element::children($form['horizontal']) as $key) {
      $form['popovers'][$key] = $form['horizontal'][$key];
      $form['popovers'][$key]['#prefix_popover'] = array(
        'title' => 'Nullam tincidunt',
        'content' => 'Suspendisse nisl elit, rhoncus eget, elementum ac, condimentum eget, diam.',
      );
      $form['popovers'][$key]['#suffix_popover'] = array(
        'title' => 'Nullam tincidunt',
        'content' => 'Suspendisse nisl elit, rhoncus eget, elementum ac, condimentum eget, diam.',
      );
    }

    // Basic forms.
    foreach (Element::children($form['horizontal']) as $key) {
      $form['basic'][$key] = $form['horizontal'][$key];
      $form['basic'][$key]['#form_type'] = 'basic';
    }

    foreach (Element::children($form['horizontal']) as $key) {
      $form['basic-auto-width'][$key] = $form['horizontal'][$key];
      $form['basic-auto-width'][$key]['#form_type'] = 'basic';
      $form['basic-auto-width'][$key]['#element_width_auto'] = TRUE;
    }

    // Inline forms.
    foreach (Element::children($form['horizontal']) as $key) {
      $form['inline'][$key] = $form['horizontal'][$key];
      $form['inline'][$key]['#form_type'] = 'inline';
    }

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'submit',

    );

    // Return the form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bootstrap_forms_test';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

}
