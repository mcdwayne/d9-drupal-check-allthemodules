<?php

namespace Drupal\efs\Plugin\efs\Formatter;

use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\efs\Entity\ExtraFieldInterface;
use Drupal\efs\ExtraFieldFormatterPluginBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Details element.
 *
 * @ExtraFieldFormatter(
 *   id = "tokenizer_wysiwyg",
 *   label = @Translation("Tokenizer Wysiwyg"),
 *   description = @Translation("Tokenizer Wysiwyg"),
 *   supported_contexts = {
 *     "form",
 *     "display"
 *   }
 * )
 */
class TokenizerWysiwyg extends ExtraFieldFormatterPluginBase {

  /**
   * The Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The language interface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->token = $token;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings(string $context) {
    $defaults = [
      'content' => NULL,
    ] + parent::defaultSettings();

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;

  }

  /**
   * {@inheritdoc}
   */
  public function view(array $build, EntityInterface $entity, EntityDisplayBase $display, string $view_mode, ExtraFieldInterface $extra_field) {
    $settings = $this->getSettings();

    $content = $settings['content']['value'];
    $element = [
      '#type' => 'processed_text',
      '#text' => $this->token->replace($content, [$extra_field->get('entity_type') => $entity], ['clear' => TRUE]),
      '#format' => $settings['content']['format'],
      '#filter_types_to_skip' => [],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(EntityDisplayFormBase $view_display, array $form, FormStateInterface $form_state, ExtraFieldInterface $extra_field, string $field) {
    $form = parent::settingsForm($view_display, $form, $form_state, $extra_field, $field);
    /** @var \Drupal\Core\Entity\EntityDisplayBase $display */
    $settings = $this->getSettings();
    $form_state->setValue('field_mirror_name', $field);

    $form['content'] = [
      '#title' => $this->t('Content'),
      '#type' => 'text_format',
      '#default_value' => !empty($settings['content']['value']) ? $settings['content']['value'] : NULL,
      '#format' => !empty($settings['content']['format']) ? $settings['content']['format'] : filter_default_format(),
      '#token_types' => [$extra_field->get('entity_type')],
      '#element_validate' => ['token_element_validate'],
      '#after_build' => ['token_element_validate'],
    ];

    // Show the token help relevant to this pattern type.
    $form['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [$extra_field->get('entity_type')],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(string $context) {
    $summary = parent::settingsSummary($context);
    if ($this->getSetting('field')) {
      $field = $this->getSetting('field');
      $formatter = $this->getSetting('formatter');
      $summary[] = $this->t('Field: %components', ['%components' => $field]);
      $summary[] = $this->t('Field formatter: %components', ['%components' => $formatter]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(string $entity_type_id, string $bundle) {
    return TRUE;
  }

  /**
   * Get the field options.
   *
   * @param array $fields
   *   The array of fields.
   *
   * @return array
   *   The select options.
   */
  protected function getFieldOptions(array $fields) {
    $options = [];
    foreach ($fields as $field) {
      if ($field instanceof FieldConfig) {
        $options[$field->get('field_name')] = $field->label();
      }
    }
    return $options;
  }

  /**
   * Get the field selected formatter options.
   *
   * @param string $field_name
   *   The field name.
   * @param array $fields
   *   The list of fields.
   *
   * @return array
   *   The selected formatter options.
   */
  protected function getFieldSelectedFormatters(string $field_name, array $fields) {
    $options = [];
    $field = $fields[$field_name];
    $field_type = $field->get('field_type');
    $definitions = $this->formatter->getDefinitions();
    foreach ($definitions as $id => $def) {
      if (in_array($field_type, $def['field_types'])) {
        $options[$id] = $def['label'];
      }
    }
    return $options;
  }

  /**
   * Get the settings of selected formatter.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param \Drupal\field\Entity\FieldConfig $field
   *   The field entity config.
   * @param array $settings
   *   The settings of field.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The settings of selected formatter.
   */
  protected function getFieldSelectedFormatterSettings(string $plugin_id, FieldConfig $field, array $settings, array $form, FormStateInterface $form_state) {
    $configuration = [
      'field_definition' => $field,
      'third_party_settings' => [],
      'settings' => $settings['settings'],
      'label' => $settings['label'],
      'view_mode' => '',
    ];
    $plugin = $this->formatter->createInstance($plugin_id, $configuration);
    $settings = $plugin->settingsForm($form, $form_state);
    return $settings;
  }

}
