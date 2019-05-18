<?php

namespace Drupal\azure_entity_moderation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\azure_text_analytics_api\Service\TextAnalytics;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Language\Language;

/**
 * Plugin implementation of the 'azure_entity_moderation' widget.
 *
 * @FieldWidget(
 *   id = "azure_entity_moderation",
 *   module = "azure_entity_moderation",
 *   label = @Translation("Number input if has permissions or automatic value using Azure API."),
 *   field_types = {
 *     "azure_entity_moderation"
 *   }
 * )
 */
class AzureModerationWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Text analytics service.
   *
   * @var \Drupal\azure_text_analytics_api\Service\TextAnalytics
   */
  protected $textAnalytics;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('azure_text_analytics_api.text_analytics')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountInterface $currentUser, TextAnalytics $textAnalytics) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentUser = $currentUser;
    $this->textAnalytics = $textAnalytics;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $value = isset($items[$delta]->value) ? $items[$delta]->value : 0;
    $element += [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.001,
      '#default_value' => $value,
      '#size' => 5,
      '#access' => $this->currentUser->hasPermission('manually set azure moderation value'),
      '#value_callback' => [$this, 'valueCallback'],
    ];

    $return = ['value' => $element];
    if ($this->currentUser->hasPermission('manually set azure moderation value')) {
      $return['override'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Override API value'),
        '#default_value' => 1,
      ];
    }
    return $return;;
  }

  /**
   * Helper function to analyze output of the textAnalytics service.
   */
  protected function analyzeDocuments(array $documents) {
    $value = FALSE;
    if (!empty($documents)) {
      $result = $this->textAnalytics->sentiment($documents);
      if (empty($result['errors'])) {
        $value = 0;

        // Calculate weighted average.
        $total_length = 0;
        foreach ($documents as $id => $data) {
          if (isset($result['documents'][$id])) {
            $length = strlen($data['text']);
            $total_length += $length;
            $value += $result['documents'][$id]['score'] * $length;
          }
        }

        if ($value > 0) {
          $value /= $total_length;
        }
      }
    }

    return $value;
  }

  /**
   * Value callback for the field.
   */
  public function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $value = isset($element['#default_value']) ? $element['#default_value'] : 0;
    if ($form_state->isProcessingInput()) {
      $user_input = $form_state->getUserInput();

      $override = FALSE;
      if ($this->currentUser->hasPermission('manually set azure moderation value')) {
        $path = $element['#parents'];
        $path[count($path) - 1] = 'override';
        $override = NestedArray::getValue($user_input, $path);
      }

      // If there is no user input, call the Azure text analysis service.
      if ($input === FALSE || empty($override)) {
        $settings = $this->fieldDefinition->getSettings();

        if (isset($user_input['langcode'][0]['value'])) {
          $langcode = $user_input['langcode'][0]['value'];
        }
        else {
          $langcode = $form_state->get('langcode');
        }
        if (in_array($langcode, [
          Language::LANGCODE_NOT_SPECIFIED,
          Language::LANGCODE_NOT_APPLICABLE,
        ], TRUE)) {
          $langcode = '';
        }
        $documents = [];

        foreach ($settings['fields'] as $field_id) {
          if ($field_id && isset($user_input[$field_id])) {
            foreach ($user_input[$field_id] as $field_item) {
              foreach ($field_item as $column => $field_value) {
                if (!empty($field_value)) {
                  $id = count($documents) + 1;
                  $documents[$id] = [
                    'text' => $field_value,
                  ];
                  if (!empty($langcode)) {
                    $documents[$id]['language'] = $langcode;
                  }
                }
              }
            }
          }
        }
        $analyzer_output = $this->analyzeDocuments($documents);
        if ($analyzer_output !== FALSE) {
          $value = round($analyzer_output, 3);
        }
      }
      else {
        $value = $input;
      }
    }
    return $value;
  }

}
