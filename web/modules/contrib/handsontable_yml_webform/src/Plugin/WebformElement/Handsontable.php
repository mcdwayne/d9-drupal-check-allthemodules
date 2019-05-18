<?php

namespace Drupal\handsontable_yml_webform\Plugin\WebformElement;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\TextBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'handsontable' element.
 *
 * @WebformElement(
 *   id = "handsontable",
 *   label = @Translation("Handsontable"),
 *   category = @Translation("Basic elements"),
 *   multiline = TRUE,
 * )
 */
class Handsontable extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
        'title' => '',
        // General settings.
        'description' => '',
        'default_value' => '',
        'initial_data' => '',
        'view_settings' => '{"colHeaders": false, "contextMenu": ["undo", "redo"]}',
        'make_existing_data_read_only' => FALSE,
        'background_colors' => '',
        // Form display.
        'title_display' => '',
        'description_display' => '',
        'field_prefix' => '',
        'field_suffix' => '',
        'placeholder' => '',
        'rows' => '',
        // Form validation.
        'required' => FALSE,
        'required_error' => '',
      ] + $this->getDefaultBaseProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['initial_data']);
  }

  /**
   * {@inheritdoc}
   */

  public function formatHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $data = json_decode($webform_submission->getData()[$element['#webform_key']], TRUE);
    $headers = $data[0];
    $rows = array_splice($data, 1);
    $attributes = ['class' => ['handsontable_table_display']];

    return [
      '#type' => 'table',
      '#header' => $headers,
      '#empty' => t('No content available.'),
      '#rows' => $rows,
      '#attributes' => $attributes,
    ];
  }

  /**
   * Gets the actual configuration webform array to be built.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['element']['default_value'] = [
      '#type' => 'hidden',
    ];

    $form['element']['initial_data'] = [
      '#type' => 'handsontable',
      '#title' => 'Default data',
      '#view_settings' => [
        'contextMenu' => [
          'row_above',
          'row_below',
          'remove_row',
          'remove_col',
          'col_left',
          'col_right',
          'make_read_only',
          'undo',
          'redo',
        ],
      ],
    ];


    $form['element']['view_settings'] = [
      '#type' => 'textarea',
      '#title' => t('View settings'),
      '#description' => t('Must be valid JSON.') . ' ' . t('See https://docs.handsontable.com for details.'),
    ];

    $form['element']['make_existing_data_read_only'] = [
      '#type' => 'checkbox',
      '#title' => t('Make cells readonly that contain Default data (as defined above)'),
    ];

    $form['element']['background_colors'] = [
      '#type' => 'textarea',
      '#title' => t('Background colors'),
      '#description' => t('You can use the context menu on the table above to set background colors. Your choice will appear here.') . ' ' . t('Must be valid JSON.'),
    ];

    return $form;
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $properties = $this->getConfigurationFormProperties($form, $form_state);

    // is it valid JSON?
    if (!empty($properties['#view_settings'])) {
      set_error_handler('_webform_entity_element_validate_rendering_error_handler');
      if (json_decode($properties['#view_settings']) === NULL) {
        $form_state->setErrorByName('view_settings', t('The view settings are not valid JSON. You may go to jsonlint.com to validate your JSON.'));
      }
      set_error_handler('_drupal_error_handler');
    }
  }
}
