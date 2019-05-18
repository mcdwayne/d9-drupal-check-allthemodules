<?php

namespace Drupal\dbpedia_spotlight\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
/**
 * Implements the SimpleForm form controller.
 *
 * This example demonstrates a simple form with a singe text input element. We
 * extend FormBase which is the simplest form base class used in Drupal.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class DBpediaSpotlightForm extends FormBase {

    /**
     * Build the simple form.
     *
     * A build form method constructs an array that defines how markup and
     * other form elements are included in an HTML form.
     *
     * @param array $form
     *   Default form array structure.
     * @param FormStateInterface $form_state
     *   Object containing current form state.
     *
     * @return array
     *   The render array defining the elements of the form.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $content_types = array_keys(node_type_get_types());

        $form['content_types'] = [
            '#type' => 'select',
            '#title' => $this->t('Content types'),
            '#description' => $this->t('Select content type to annotate'),
            '#options' => array_combine($content_types, $content_types),
        ];
        // Group submit handlers in an actions element with a key of "actions" so
        // that it gets styled correctly, and so that other modules may add actions
        // to the form. This is not required, but is convention.
        $form['actions'] = [
            '#type' => 'actions',
        ];

        // Add a submit button that handles the submission of the form.
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
        ];

        return $form;
    }

    /**
     * Getter method for Form ID.
     *
     * The form ID is used in implementations of hook_form_alter() to allow other
     * modules to alter the render array built by this form controller.  it must
     * be unique site wide. It normally starts with the providing module's name.
     *
     * @return string
     *   The unique ID of the form defined by this class.
     */
    public function getFormId() {
        return 'dbpedia_spotlight_form';
    }

    /**
     * Implements form validation.
     *
     * The validateForm method is the default method called to validate input on
     * a form.
     *
     * @param array $form
     *   The render array of the currently built form.
     * @param FormStateInterface $form_state
     *   Object describing the current state of the form.
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
/*        $title = $form_state->getValue('title');
        if (strlen($title) < 5) {
            // Set an error for the form element with a key of "title".
            $form_state->setErrorByName('title', $this->t('The title must be at least 5 characters long.'));
        }*/
    }

    /**
     * Implements a form submit handler.
     *
     * The submitForm method is the default method called for any submit elements.
     *
     * @param array $form
     *   The render array of the currently built form.
     * @param FormStateInterface $form_state
     *   Object describing the current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        /*
         * This would normally be replaced by code that actually does something
         * with the title.
         */
        // @todo provide this machine name programatically
        $vocabulary = "dbpedia_spotlight_dbpedia";

        $content_type = $form_state->getValue('content_types');
        $this->node_add_dbpedia_field($content_type);
        //drupal_set_message(t('You specified a title of %title.', ['%title' => $title]));

        $batch = array(
            'title' => t('Exporting'),
            'operations' => array(
                array('dbpedia_spotlight_batch', array($content_type, $vocabulary)),
            ),
            'finished' => 'dbpedia_spotlight_batch_finished_callback',
            'file' => drupal_get_path('module', 'dbpedia_spotlight') . '/dbpedia_spotlight.batch.inc',
        );

        batch_set($batch);

    }

    public function node_add_dbpedia_field($type, $label = 'DBpedia') {
      // Add or remove the body field, as needed.
      $field_storage = FieldStorageConfig::loadByName('node', 'field_dbpedia');
      $field = FieldConfig::loadByName('node', $type, 'field_dbpedia');
      if (empty($field)) {
        $field = FieldConfig::create([
          'field_storage' => $field_storage,
          'bundle' => $type,
          'label' => $label,
        ]);
        $field->save();

      }

      return $field;
    }
}
