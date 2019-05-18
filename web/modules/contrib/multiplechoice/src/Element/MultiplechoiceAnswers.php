<?php

namespace Drupal\multiplechoice\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an AJAX/progress aware widget for mulitplechoice answers.
 *
 * @FormElement("multiplechoice_answers")
 */
class MultiplechoiceAnswers extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processMultiplechoiceAnswers'],
      ],
      '#element_validate' => [
        [$class, 'validateMultiplechoiceAnswers'],
      ],
      '#pre_render' => [
        [$class, 'preRenderMultiplechoiceAnswers'],
      ],
      //'#theme' => 'file_managed_file',
      '#theme_wrappers' => ['form_element'],
      '#progress_indicator' => 'throbber',
      '#progress_message' => NULL,
      '#upload_validators' => [],
      '#upload_location' => NULL,
      '#size' => 22,
      '#multiple' => FALSE,
      '#extended' => FALSE,
//      '#attached' => [
//        'library' => ['file/drupal.file'],
//      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    //dpm($element);
    if ($input !== FALSE) {
      return array_shift($input);
    }
    else {
      return $element['#default_value'];
    }


    // Find the current value of this field.
    $fids = !empty($input['fids']) ? explode(' ', $input['fids']) : [];
    foreach ($fids as $key => $fid) {
      $fids[$key] = (int) $fid;
    }
    $force_default = FALSE;

    // Process any input and save new uploads.
    if ($input !== FALSE) {
      $input['fids'] = $fids;
      $return = $input;

      // Uploads take priority over all other values.
      if ($files = file_managed_file_save_upload($element, $form_state)) {
        if ($element['#multiple']) {
          $fids = array_merge($fids, array_keys($files));
        }
        else {
          $fids = array_keys($files);
        }
      }
      else {
        // Check for #filefield_value_callback values.
        // Because FAPI does not allow multiple #value_callback values like it
        // does for #element_validate and #process, this fills the missing
        // functionality to allow File fields to be extended through FAPI.
        if (isset($element['#file_value_callbacks'])) {
          foreach ($element['#file_value_callbacks'] as $callback) {
            $callback($element, $input, $form_state);
          }
        }

        // Load files if the FIDs have changed to confirm they exist.
        if (!empty($input['fids'])) {
          $fids = [];
          foreach ($input['fids'] as $fid) {
            if ($file = File::load($fid)) {
              $fids[] = $file->id();
              // Temporary files that belong to other users should never be
              // allowed.
              if ($file->isTemporary()) {
                if ($file->getOwnerId() != \Drupal::currentUser()->id()) {
                  $force_default = TRUE;
                  break;
                }
                // Since file ownership can't be determined for anonymous users,
                // they are not allowed to reuse temporary files at all. But
                // they do need to be able to reuse their own files from earlier
                // submissions of the same form, so to allow that, check for the
                // token added by $this->processMultiplechoiceAnswers().
                elseif (\Drupal::currentUser()->isAnonymous()) {
                  $token = NestedArray::getValue($form_state->getUserInput(), array_merge($element['#parents'], array('file_' . $file->id(), 'fid_token')));
                  if ($token !== Crypt::hmacBase64('file-' . $file->id(), \Drupal::service('private_key')->get() . Settings::getHashSalt())) {
                    $force_default = TRUE;
                    break;
                  }
                }
              }
            }
          }
          if ($force_default) {
            $fids = [];
          }
        }
      }
    }

    // If there is no input or if the default value was requested above, use the
    // default value.
    if ($input === FALSE || $force_default) {
      if ($element['#extended']) {
        $default_fids = isset($element['#default_value']['fids']) ? $element['#default_value']['fids'] : [];
        $return = isset($element['#default_value']) ? $element['#default_value'] : ['fids' => []];
      }
      else {
        $default_fids = isset($element['#default_value']) ? $element['#default_value'] : [];
        $return = ['fids' => []];
      }

      // Confirm that the file exists when used as a default value.
      if (!empty($default_fids)) {
        $fids = [];
        foreach ($default_fids as $fid) {
          if ($file = File::load($fid)) {
            $fids[] = $file->id();
          }
        }
      }
    }

    $return['fids'] = $fids;
    return $return;
  }

  /**
   * #ajax callback for multiplechoice answers field.
   *
   * This ajax callback takes care of the following things:
   *   - Ensures that broken requests due to too big files are caught.
   *   - Adds a class to the response to be able to highlight in the UI, that a
   *     new file got uploaded.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response of the ajax upload.
   */
  public static function answersAjaxCallback(&$form, FormStateInterface &$form_state, Request $request) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $attachments = $form['#attached'];
    $form_parents = explode('/', $request->query->get('element_parents'));
    $form_parents[] = 'container';

    $trigger = $form_state->getTriggeringElement();

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    //$form = $form[$field_name]['widget'][$delta]['container'];
    // Add the special AJAX class if a new file was added.
//    $current_file_count = $form_state->get('file_upload_delta_initial');
//    if (isset($form['#file_upload_delta']) && $current_file_count < $form['#file_upload_delta']) {
//      $form[$current_file_count]['#attributes']['class'][] = 'ajax-new-content';
//    }
//    // Otherwise just add the new content class on a placeholder.
//    else {
//      $form['#suffix'] .= '<span class="ajax-new-content"></span>';
//    }
//
//    $status_messages = ['#type' => 'status_messages'];
//    $form['#prefix'] .= $renderer->renderRoot($status_messages);
    $output = $renderer->renderRoot($form);
//dpm($form);
    $response = new AjaxResponse();
    $response->setAttachments($attachments);

    return $response->addCommand(new ReplaceCommand(NULL, $output));
  }

  /**
   * Helper function to get the field delta value
   *
   * @param $form
   * @param $form_state
   * @return mixed
   */
  public static function getDelta($form, $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#parents'];
    return $parents[1];
  }

  public static function getFieldName($form, $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#parents'];
    return $parents[0];
  }

  /**
   * Render API callback: Expands the multiplechoice element type.
   *
   */
  public static function processMultiplechoiceAnswers(&$element, FormStateInterface $form_state, &$complete_form) {


    $defaults = isset($element['#default_value'])  ? $element['#default_value'] : FALSE;
    $defaults = isset($defaults['container']) ? $defaults['container'] : array();

    //dpm($defaults);
    //$instance = field_widget_instance($element, $form_state);
    // Use size setting if it exists
    //$size = isset($instance['widget']['settings']['size']) ? $instance['widget']['settings']['size'] : 60;

    $element['#tree'] = TRUE;

    $delta = $element['#delta'];
    if ($form_state->has(['answer_num', $delta])) {
      $step = $form_state->get(['answer_num', $delta]);
      //\Drupal::logger('multiplechoice')->notice('process form delta ' . $delta . ' step ' . $step);
    }
    if (isset($step) && is_numeric($step)) {
      $num = $step;
    }
    elseif ($defaults) {
      $num = count($defaults);
      $form_state->set(['answer_num', $delta], $num);
    }
    else {
      $num = 1;

    }
    // \Drupal::logger('multiplechoice')->notice('num ' . $num . ' form state ');

    $size = 60;

    $wrapper = 'id-answer-item-' . $delta;
    $question_number = $delta+1;

    $element['container'] = array(
      '#type' => 'fieldset',
      '#title' => t('Answers'),
      '#description' => t('Select one answer as correct'),
      '#attributes' => array(
        'id' => $wrapper,
        'class' => array(
          'answer-item-' . $delta
        )
      )
    );

    for ($i = 0; $i < $num; $i++) {
      // \Drupal::logger('multiplechoice')->notice(' form state num ' . $i);
      $element['container'][$i] = array(
        '#type' => 'container',
        '#delta' => $i,
        '#max_delta' => $num,
        '#field_name' => 'container_'. $i
      );

      $element['container'][$i]['correct'] = array(
        '#type' => 'checkbox',
        '#default_value' => isset($defaults[$i]['correct']) && $defaults[$i]['correct'] == 1 ? $defaults[$i]['correct']
            : NULL,
        '#prefix' => '<div class="answer-item">',
        '#attributes' => array(
          'class' => array(
            'correct-answer',
            'correct-answer-' . $delta
          )
        ),

      );

      $element['container'][$i]['answer'] = array(
        '#type' => 'textfield',
        '#title' => t('Answer'),
        '#default_value' => isset($defaults[$i]['answer']) ? $defaults[$i]['answer'] : '',
        '#size' => $size,
        '#attributes' => array(
          'class' => array(
            'answer'
          )
        )
      );

      $element['container'][$i]['remove'] = array(
        '#type' => 'submit',
        // For the remove button to work, the value has to be unique for this item
        '#value' => t('Remove @key from @num', array('@key' => ($i + 1), '@num' => $question_number)),
        '#limit_validation_errors' => array(),
        '#submit' => [get_called_class() . '::RemoveOneSubmit'],
        '#attributes' => array(
          'class' => array(
            'remove-answer'
          ),
          'title' => 'Remove'
        ),
        '#suffix' => '</div>',
        '#ajax' => array(
          'callback' => [get_called_class(), 'answersAjaxCallback'],
          'wrapper' => $wrapper,
          'options' => [
            'query' => [
              'element_parents' => implode('/', $element['#array_parents']),
            ],
          ],
        ),
      );
    }

    $element['container']['add_more'] = array(
      '#type' => 'submit',
      '#value' => t('Add an Answer to Question @num', array('@num' => $question_number)),
      '#limit_validation_errors' => array(),
      '#submit' => [get_called_class() . '::AddMoreSubmit'],
      '#ajax' => array(
        'callback' => [get_called_class(), 'answersAjaxCallback'],
        'wrapper' => $wrapper,
        'options' => [
          'query' => [
            'element_parents' => implode('/', $element['#array_parents']),
          ],
        ],
      ),
    );

    return $element;
  }

  /**
   * Submit handler for the "Add another item" button of a field form.
   *
   * This handler is run regardless of whether JS is enabled or not. It makes
   * changes to the form state. If the button was clicked with JS disabled, then
   * the page is reloaded with the complete rebuilt form. If the button was
   * clicked with JS enabled, then ajax_form_callback() calls field_add_more_js()
   * to return just the changed part of the form.
   */
  public static function AddMoreSubmit($form, &$form_state) {
    $delta = static::getDelta($form, $form_state);
    if ($form_state->has(['answer_num', $delta])) {
      $step = $form_state->get(['answer_num', $delta]);
    }
    $new_step = isset($step) ? $step=$step+1 : 2;
    $form_state->set(['answer_num', $delta], $new_step);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "Remove" button of a field form.
   * Remove the item from its place in the values tree
   *
   * @param $form
   * @param $form_state
   */
  public static function RemoveOneSubmit($form, &$form_state) {
    $delta = static::getDelta($form, $form_state);
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#parents'];
    $deleted = $parents[3];
//    \Drupal::logger('multiplechoice')->notice('deleted ' . $deleted . ' delta ' . $delta);
    $form_state->set(['deleted', $delta], $deleted);
    $field_name = static::getFieldName($form, $form_state);

    if ($parents[4] == 'remove' && $field_name != FALSE) {
      $form_values = $form_state->getUserInput();
      $values = $form_values[$field_name][$delta]['container'];
      $new_values = array();
      foreach ($values as $key => $item) {
        if ($key != $deleted) {
          $new_values[] = $item;
        }
      }
      $form_values[$field_name][$delta]['container'] = $new_values;
      $form_state->setUserInput($form_values);
      if ($form_state->has(['answer_num', $delta])) {
        $step = $form_state->get(['answer_num', $delta]);
      }
      // \Drupal::logger('multiplechoice')->notice('remove delta ' . $delta . ' step ' . $step);
      $new_step = isset($step) ? $step=$step-1 : 1;
      $form_state->set(['answer_num', $delta], $new_step);
    }
    $form_state->setRebuild();
  }

  /**
   * Render API callback: Hides display of the upload or remove controls.
   *
   * Upload controls are hidden when a file is already uploaded. Remove controls
   * are hidden when there is no file attached. Controls are hidden here instead
   * of in \Drupal\file\Element\MultiplechoiceAnswers::processMultiplechoiceAnswers(), because
   * #access for these buttons depends on the managed_file element's #value. See
   * the documentation of \Drupal\Core\Form\FormBuilderInterface::doBuildForm()
   * for more detailed information about the relationship between #process,
   * #value, and #access.
   *
   * Because #access is set here, it affects display only and does not prevent
   * JavaScript or other untrusted code from submitting the form as though
   * access were enabled. The form processing functions for these elements
   * should not assume that the buttons can't be "clicked" just because they are
   * not displayed.
   *
   * @see \Drupal\file\Element\MultiplechoiceAnswers::processMultiplechoiceAnswers()
   * @see \Drupal\Core\Form\FormBuilderInterface::doBuildForm()
   */
  public static function preRenderMultiplechoiceAnswers($element) {

    return $element;
  }

  /**
   * Render API callback: Validates the managed_file element.
   */
  public static function validateMultiplechoiceAnswers(&$element, FormStateInterface $form_state, &$complete_form) {
    return;
    // If referencing an existing file, only allow if there are existing
    // references. This prevents unmanaged files from being deleted if this
    // item were to be deleted.
    $clicked_button = end($form_state->getTriggeringElement()['#parents']);
    if ($clicked_button != 'remove_button' && !empty($element['fids']['#value'])) {
      $fids = $element['fids']['#value'];
      foreach ($fids as $fid) {
        if ($file = File::load($fid)) {
          if ($file->isPermanent()) {
            $references = static::fileUsage()->listUsage($file);
            if (empty($references)) {
              // We expect the field name placeholder value to be wrapped in t()
              // here, so it won't be escaped again as it's already marked safe.
              $form_state->setError($element, t('The file used in the @name field may not be referenced.', ['@name' => $element['#title']]));
            }
          }
        }
        else {
          // We expect the field name placeholder value to be wrapped in t()
          // here, so it won't be escaped again as it's already marked safe.
          $form_state->setError($element, t('The file referenced by the @name field does not exist.', ['@name' => $element['#title']]));
        }
      }
    }

    // Check required property based on the FID.
    if ($element['#required'] && empty($element['fids']['#value']) && !in_array($clicked_button, ['upload_button', 'remove_button'])) {
      // We expect the field name placeholder value to be wrapped in t()
      // here, so it won't be escaped again as it's already marked safe.
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }

    // Consolidate the array value of this field to array of FIDs.
    if (!$element['#extended']) {
      $form_state->setValueForElement($element, $element['fids']['#value']);
    }
  }

  /**
   * Wraps the file usage service.
   *
   * @return \Drupal\file\FileUsage\FileUsageInterface
   */
  protected static function fileUsage() {
    return \Drupal::service('file.usage');
  }

}
