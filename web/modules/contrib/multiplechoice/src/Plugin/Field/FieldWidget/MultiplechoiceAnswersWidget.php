<?php

namespace Drupal\multiplechoice\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\multiplechoice\Element\MultiplechoiceAnswers;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
* @FieldWidget(
*   id = "multiplechoice_answers",
*   label = @Translation("Multiplechoice answers widget"),
*   field_types = {
*     "multiplechoice"
*   }
* )
*/


class MultiplechoiceAnswersWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->elementInfo = $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('element_info'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(

    ) + parent::defaultSettings();
  }


  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();

    // The field settings include defaults for the field type. However, this
    // widget is a base class for other widgets (e.g., ImageWidget) that may act
    // on field types without these expected settings.
    $field_settings += array(
      'display_default' => NULL,
      'display_field' => NULL,
      'description_field' => NULL,
    );

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $defaults = array(
      'display' => (bool) $field_settings['display_default'],
      'description' => '',
    );

    // We use the multiplechoice_answers type, extended with some
    // enhancements.
    $element_info = $this->elementInfo->getInfo('multiplechoice_answers');
    //dpm(array_merge($element_info['#process'], array(array(get_class($this), 'process'))));
    $element += array(
      '#type' => 'multiplechoice_answers',
      '#value_callback' => array(get_class($this), 'value'),
      '#process' => array_merge($element_info['#process'], array(array(get_class($this), 'process'))),
      '#progress_indicator' => $this->getSetting('progress_indicator'),
      // Allows this field to return an array instead of a single value.
      '#extended' => TRUE,
      // Add properties needed by value() and process() methods.
      '#field_name' => $this->fieldDefinition->getName(),
      '#entity_type' => $items->getEntity()->getEntityTypeId(),
      '#display_field' => (bool) $field_settings['display_field'],
      '#display_default' => $field_settings['display_default'],
      '#description_field' => $field_settings['description_field'],
      '#cardinality' => $cardinality,
      '#default_value' => $items[$delta]->getValue()
    );

    $element['#weight'] = $delta;

    return $element;
  }

  /**
   * Form API callback: Processes a multiplechoice field element.
   *
   * Expands the file_generic type to include the description and display
   * fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
   //dpm($element);
    $delta = $element['#delta'];
    //dpm($delta);
    $item = $element['#value'];

    //    if (empty($form_state['input'])) {
//      $num_items = count($items);
//      if ($delta > 0 && $num_items < $delta + 1) {
//        $form_state['field']['multiplechoice_questions'][$langcode]['items_count']--;
//        return FALSE;
//      }
//    }

    $size = isset($instance['widget']['settings']['size']) ? $instance['widget']['settings']['size'] : 60;
    $max_length = isset($field['settings']['max_length']) ? $field['settings']['max_length'] : 256;
    $format = 'filtered_html';

    $question_number = $delta+1;
    $difficulty_options = array();
    //$difficulty = $this->getSetting('difficulty_rating_range');
    $difficulty_range = isset($difficulty) ? $difficulty : 5;
    for ($i=1; $i <= $difficulty_range; $i++) {
      $difficulty_options[$i] = $i;
    }

    $element['question'] = array(
      '#type' => 'text_format',
      '#format' => $format,
      '#title' => t('Question @num', array('@num' => $question_number)),
      '#weight' => -2,
      '#default_value' => isset($item['question']) ? $item['question'] : '',
      '#max_length' => $max_length,
      '#size' => $size
    );

    $element['difficulty'] = array(
      '#type' => 'select',
      '#options' => $difficulty_options,
      '#title' => t('Difficulty'),
      '#weight' => -2,
      '#default_value' => isset($item['difficulty']) ? $item['difficulty'] : ''
    );

    $element['correct_answer'] = array(
      '#type' => 'value',
      '#value' => isset($item['correct_answer']) ? $item['correct_answer'] : ''
    );

    $element['delete'] = array(
      '#type' => 'checkbox',
      '#title' => t('Delete this item'),
      '#weight' => -1,
    );


    return $element;

    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];

    // Add the display field if enabled.
    if ($element['#display_field']) {
      $element['display'] = array(
        '#type' => empty($item['fids']) ? 'hidden' : 'checkbox',
        '#title' => t('Include file in display'),
        '#attributes' => array('class' => array('file-display')),
      );
      if (isset($item['display'])) {
        $element['display']['#value'] = $item['display'] ? '1' : '';
      }
      else {
        $element['display']['#value'] = $element['#display_default'];
      }
    }
    else {
      $element['display'] = array(
        '#type' => 'hidden',
        '#value' => '1',
      );
    }

    // Add the description field if enabled.
    if ($element['#description_field'] && $item['fids']) {
      $config = \Drupal::config('file.settings');
      $element['description'] = array(
        '#type' => $config->get('description.type'),
        '#title' => t('Description'),
        '#value' => isset($item['description']) ? $item['description'] : '',
        '#maxlength' => $config->get('description.length'),
        '#description' => t('The description may be used as the label of the link to the file.'),
      );
    }

    // Adjust the Ajax settings so that on upload and remove of any individual
    // file, the entire group of file fields is updated together.
    if ($element['#cardinality'] != 1) {
      $parents = array_slice($element['#array_parents'], 0, -1);
      $new_options = array(
        'query' => array(
          'element_parents' => implode('/', $parents),
        ),
      );
      $field_element = NestedArray::getValue($form, $parents);
      $new_wrapper = $field_element['#id'] . '-ajax-wrapper';
      foreach (Element::children($element) as $key) {
        if (isset($element[$key]['#ajax'])) {
          $element[$key]['#ajax']['options'] = $new_options;
          $element[$key]['#ajax']['wrapper'] = $new_wrapper;
        }
      }
      unset($element['#prefix'], $element['#suffix']);
    }

    // Add another submit handler to the upload and remove buttons, to implement
    // functionality needed by the field widget. This submit handler, along with
    // the rebuild logic in file_field_widget_form() requires the entire field,
    // not just the individual item, to be valid.
    foreach (array('upload_button', 'remove_button') as $key) {
      $element[$key]['#submit'][] = array(get_called_class(), 'submit');
      $element[$key]['#limit_validation_errors'] = array(array_slice($element['#parents'], 0, -1));
    }

    return $element;
  }

  /**
   * Form API callback. Retrieves the value for the multiplechoice answers field element.
   *
   * This method is assigned as a #value_callback in formElement() method.
   */
  public static function value($element, $input = FALSE, FormStateInterface $form_state) {
    // dpm($input);
    //$return = MultiplechoiceAnswers::valueCallback($element, $input, $form_state);
    // dpm($element['default_value']);
    if ($input !== FALSE) {
      return array_shift($input);
    }
    else {
      return $element['#default_value'];
    }

    return $return;
  }


  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $element['max_length'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum length'),
      '#default_value' => isset($this->getSetting['max_length']) ? $this->getSetting['max_length'] : 60,
      '#required' => TRUE,
      '#description' => t('The maximum length of the Question field in characters.'),
      '#element_validate' => array('element_validate_integer_positive'),
    );

    return $elements;
  }


  /**
   * Javascript add more handler
   *
   * @param $form
   * @param $form_state
   * @return mixed
   */
  public function multiplechoice_add_more_js(array &$form, FormStateInterface $form_state) {
    $delta = static::_multiplechoice_get_delta($form, $form_state);
    $parents = $form_state['triggering_element']['#parents'];
    return $form[$parents[0]]['und'][$delta]['answers'];
  }

  public function usernameValidateCallback(array &$form, FormStateInterface $form_state) {
    // Instantiate an AjaxResponse Object to return.
    $ajax_response = new AjaxResponse();

    // Check if Username exists and is not Anonymous User ('').
    if (user_load_by_name($form_state->getValue('user_name')) && $form_state->getValue('user_name') != false) {
      $text = 'User Found';
      $color = 'green';
    } else {
      $text = 'No User Found';
      $color = 'red';
    }

    // Add a command to execute on form, jQuery .html() replaces content between tags.
    // In this case, we replace the desription with wheter the username was found or not.
    $ajax_response->addCommand(new HtmlCommand('#edit-user-name--description', $text));

    // CssCommand did not work.
    //$ajax_response->addCommand(new CssCommand('#edit-user-name--description', array('color', $color)));

    // Add a command, InvokeCommand, which allows for custom jQuery commands.
    // In this case, we alter the color of the description.
    $ajax_response->addCommand(new InvokeCommand('#edit-user-name--description', 'css', array('color', $color)));

    // Return the AjaxResponse Object.
    return $ajax_response;
  }


  /**
   * Helper function to get the field delta value
   *
   * @param $form
   * @param $form_state
   * @return mixed
   */
  public function _multiplechoice_get_delta($form, $form_state) {
    $button = $form_state->triggering_element;
    return $button['#parents'][2];
  }

  /**
   * Helper function to get the field name
   *
   * @param $form_state
   * @return bool
   */
  public function multiplechoice_get_field($form_state) {
    foreach ($form_state['field'] as $field) {
      foreach ($field as $item) {
        if ($item['field']['type'] == 'multichoice') {
          return $item['field']['field_name'];
        }
      }
    }
    return FALSE;
  }




  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $placeholder_title = $this->getSetting('placeholder_title');
    $placeholder_url = $this->getSetting('placeholder_url');
    if (empty($placeholder_title) && empty($placeholder_url)) {
      $summary[] = $this->t('No placeholders');
    }
    else {
      if (!empty($placeholder_title)) {
        $summary[] = $this->t('Title placeholder: @placeholder_title', array('@placeholder_title' => $placeholder_title));
      }
      if (!empty($placeholder_url)) {
        $summary[] = $this->t('URL placeholder: @placeholder_url', array('@placeholder_url' => $placeholder_url));
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
//  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
//    foreach ($values as &$value) {
//      $value['uri'] = static::getUserEnteredStringAsUri($value['uri']);
//      $value += ['options' => []];
//    }
//    return $values;
//  }



}
