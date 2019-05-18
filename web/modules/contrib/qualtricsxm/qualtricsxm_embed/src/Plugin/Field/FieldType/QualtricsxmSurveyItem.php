<?php

namespace Drupal\qualtricsxm_embed\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_qualtricsxm_survey' field type.
 *
 * @FieldType(
 *   id = "field_qualtricsxm_survey",
 *   label = @Translation("Field Qualtricsxm Survey"),
 *   module = "qualtricsxm_embed",
 *   description = @Translation("Render Qualtrics Survey iframe."),
 *   default_widget = "field_qualtricsxm_dropdown",
 *   default_formatter = "field_qualtricsxm_iframe",
 * )
 */
class QualtricsxmSurveyItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('something'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'auto' => [
        'qualtricsxm_embed_enable_iframe_auto_resize' => 1,
      ],
      'custom' => [
        'qualtricsxm_embed_width' => "",
        'qualtricsxm_embed_height' => "",
      ],
    ] + parent::defaultFieldSettings();

  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $form = ['#element_validate' => [[$this, 'fieldSettingsFormValidate']]];

    $form['auto'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Auto resize iframe'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['auto']['qualtricsxm_embed_enable_iframe_auto_resize'] = [
      '#type' => 'checkbox',
      '#title' => t('Iframe auto resize'),
      '#default_value' => $this->getSetting('auto')['qualtricsxm_embed_enable_iframe_auto_resize'],
      '#description' => t("To ensure the auto-resize iframe working, please copy the following code into Qualtrics form header. Untick this if custom iframe Width and Height have been given."),
    ];

    $form['auto']['qualtricsxm_embed_cross_region_js'] = [
      '#type' => 'textarea',

      '#default_value' => '<script> /*Use the "script" opening and closing tags only if you are placing this script in the header of your survey(to run on all pages)*/

   Qualtrics.SurveyEngine.addOnload(function()

   {

       // Wait half a second and then adjust the iframe height to fit the questions

               setTimeout(function () {

                  parent.postMessage( document.getElementById("Wrapper").scrollHeight+"px", "' . $GLOBALS['base_url'] . '");

                                                }, 500);
   });

</script>',

    ];

    $form['custom'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Custom'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['custom']['qualtricsxm_embed_width'] = [
      '#type' => 'textfield',
      '#title' => t('Survey iframe width'),
      '#default_value' => $this->getSetting('custom')['qualtricsxm_embed_width'],
      '#element_validate' => ['element_validate_integer_positive'],
      '#description' => t('The width for the iframe. Leave it empty if Auto Resize Iframe has been enabled.'),
    ];
    $form['custom']['qualtricsxm_embed_height'] = [
      '#type' => 'textfield',
      '#title' => t('Survey iframe height'),
      '#default_value' => $this->getSetting('custom')['qualtricsxm_embed_height'],
      '#element_validate' => ['element_validate_integer_positive'],
      '#description' => t('The height for the iframe. Leave it empty if Auto Resize Iframe has been enabled.'),
    ];
    return $form;
  }

  /**
   * Not really validation, ensue parse the right field setting to formatter.
   */
  public function fieldSettingsFormValidate(array $form, FormStateInterface $form_state) {
    $iframe_auto = !empty($form['auto']['qualtricsxm_embed_enable_iframe_auto_resize']['#value']) ? TRUE : FALSE;
    $iframe_width = $form['custom']['qualtricsxm_embed_width']['#value'];
    $iframe_height = $form['custom']['qualtricsxm_embed_height']['#value'];
    $iframe_custom = !empty($iframe_width) || !empty($iframe_height) ? TRUE : FALSE;

    if ($iframe_auto && $iframe_custom) {
      // setError on checkbox seems broken checkbox, disable this for now.
      $form_state->setError($form['custom']['qualtricsxm_embed_width'], t("Leave it empty if Auto Resize Iframe has been enabled."));
      $form_state->setError($form['custom']['qualtricsxm_embed_height'], t("Leave it empty if Auto Resize Iframe has been enabled."));
    }
  }

}
