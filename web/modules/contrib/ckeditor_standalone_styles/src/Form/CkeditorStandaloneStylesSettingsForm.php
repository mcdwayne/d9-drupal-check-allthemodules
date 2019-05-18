<?php

namespace Drupal\ckeditor_standalone_styles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CkeditorStandaloneStylesSettingsForm.
 *
 * Provides a form for specifying a list of styles to appear in CKEditor's
 * "Styles" dropdown. Most of the code in this class is taken directly from
 * \Drupal\ckeditor\Plugin\CKEditorPlugin\StylesCombo, which due to its nature
 * we cannot re-use.
 */
class CkeditorStandaloneStylesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckeditor_standalone_styles_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ckeditor_standalone_styles.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor_standalone_styles.settings');

    $form['styles'] = [
      '#title' => $this->t('Styles'),
      '#type' => 'textarea',
      '#default_value' => $config->get('styles'),
      '#description' => $this->t('A list of classes that will be provided in the "Styles" dropdown. Enter one or more classes on each line in the format: element.classA.classB|Label. Example: h2.title|Title. Advanced example: h2.fancy.title|Fancy title.<br />These styles should be available in your theme\'s CSS file.'),
      '#element_validate' => [
        [$this, 'validateStylesValue'],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * The #element_validate handler for the "styles" element.
   */
  public function validateStylesValue(array $element, FormStateInterface $form_state) {
    $styles_setting = static::generateStylesSetSetting($element['#value']);
    if ($styles_setting === FALSE) {
      $form_state->setError($element, $this->t('The provided list of styles is syntactically incorrect.'));
    }
    else {
      $style_names = array_map(function ($style) {
        return $style['name'];
      }, $styles_setting);
      if (count($style_names) !== count(array_unique($style_names))) {
        $form_state->setError($element, $this->t('Each style must have a unique label.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this
      ->config('ckeditor_standalone_styles.settings')
      ->set('styles', $form_state->getValue('styles'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Builds the "stylesSet" configuration part of the CKEditor JS settings.
   *
   * @param string $styles
   *   The "styles" setting string.
   *
   * @return array|false
   *   An array containing the "stylesSet" configuration, or FALSE when the
   *   syntax is invalid.
   */
  public static function generateStylesSetSetting($styles) {
    $styles_set = [];

    // Early-return when empty.
    $styles = trim($styles);
    if (empty($styles)) {
      return $styles_set;
    }

    $styles = str_replace(["\r\n", "\r"], "\n", $styles);
    foreach (explode("\n", $styles) as $style) {
      $style = trim($style);

      // Ignore empty lines in between non-empty lines.
      if (empty($style)) {
        continue;
      }

      // Validate syntax: element[.class...]|label pattern expected.
      if (!preg_match('@^ *[a-zA-Z0-9]+ *(\\.[a-zA-Z0-9_-]+ *)*\\| *.+ *$@', $style)) {
        return FALSE;
      }

      // Parse.
      list($selector, $label) = explode('|', $style);
      $classes = explode('.', $selector);
      $element = array_shift($classes);

      // Build the data structure CKEditor's stylescombo plugin expects.
      // @see http://docs.cksource.com/CKEditor_3.x/Developers_Guide/Styles
      $configured_style = [
        'name' => trim($label),
        'element' => trim($element),
      ];
      if (!empty($classes)) {
        $configured_style['attributes'] = [
          'class' => implode(' ', array_map('trim', $classes)),
        ];
      }
      $styles_set[] = $configured_style;
    }
    return $styles_set;
  }

}
