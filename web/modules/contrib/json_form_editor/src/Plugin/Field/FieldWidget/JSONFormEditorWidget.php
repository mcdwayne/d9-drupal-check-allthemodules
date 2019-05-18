<?php
namespace Drupal\json_form_editor\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode as Unicode;
use Drupal\Component\Serialization\Json as Json;

/**
 * Plugin implementation of the 'json_form_editor' widget.
 *
 * @FieldWidget(
 *   id = "json_form_editor",
 *   label = @Translation("JSON Form Editor"),
 *   description = @Translation("JSON Form Editor"),
 *   field_types = {
 *     "json",
 *     "jsonb",
 *   },
 *   multiple_values = TRUE,
 * )
 */
class JSONFormEditorWidget extends StringTextareaWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'schema' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget['value'] = $element + [
      '#type' => 'textarea',
      '#default_value' => (isset($items[$delta]) ? $items[$delta]->value : ''),
      '#rows' => $this->getSetting('rows'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#attributes' => ['class' => ['js-text-full', 'text-full']],
    ];
    $widget['#element_validate'][] = array(get_called_class(), 'validateJsonStructure');

    $field_name = $this->fieldDefinition->getName();

    $settings['json_form_editor'][$field_name]['schema'] = $this->getSetting('schema');
    # not available when editing field structure?
    $node = \Drupal::request()->attributes->get('node');
    $settings['json_form_editor']['bundle'] = ($node ? $node->bundle() : '');
    #$cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    $widget[$delta] = array( 
      '#attached' => array(
         'library' => array(
           'json_form_editor/json_form_editor',
         ),
         'drupalSettings' => $settings
      ),
      #'#cardinality' => $cardinality,
    );

    return $widget;
  }

  /**
   * Validates the input to see if it is a properly formatted JSON object. If not, PgSQL will throw fatal errors upon insert.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form
   */
  public static function validateJsonStructure(&$element, FormStateInterface $form_state, $form) {
    if (Unicode::strlen($element['value']['#value'])) {
      $value = Json::decode($element['value']['#value']);

      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setError($element['value'], t('!name must contain a valid JSON object.', array('!name' => $element['value']['#title'])));
      }
    }
  }

  /**
   * todo: reuse validateJsonStructure
   */
  public static function validateSettingsJsonStructure(&$element, FormStateInterface $form_state, $form) {
    $value = $form_state->getValue($element['#parents']);
    if (Unicode::strlen($value)) {
      Json::decode($value);

      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setError($element, t('Must contain a valid JSON object'));
      }
    }
  }


  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['schema'] = array(
      '#type' => 'textarea',
      '#title' => t('JSON Schema (<a href="https://github.com/json-editor/json-editor#json-schema-support">doc</a>)'),
      '#default_value' => $this->getSetting('schema'),
      #'#description' => t(''),
      '#required' => TRUE,
      '#element_validate' => array(array(get_called_class(), 'validateSettingsJsonStructure'))
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $schema = $this->getSetting('schema');
    if (!empty($schema)) {
      $summary[] = t('Schema: @schema', array('@schema' => $schema));
    }

    return $summary;
  }

}
