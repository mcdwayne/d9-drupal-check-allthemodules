<?php

namespace Drupal\question_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\question_field\AnswerStorage;
use Drupal\question_field\Form\QuestionForm;
use Drupal\question_field\Plugin\Field\FieldType\QuestionItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'question_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "question_formatter",
 *   module = "question_field",
 *   label = @Translation("Question Formatter"),
 *   field_types = {
 *     "question"
 *   }
 * )
 */
class QuestionFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The answer storage.
   *
   * @var \Drupal\question_field\AnswerStorage
   */
  protected $storage;

  /**
   * The item data.
   *
   * @var \Drupal\question_field\Plugin\Field\FieldFormatter\QuestionFormatterItemData[]
   */
  protected $itemData;

  /**
   * Constructs a new QuestionFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\question_field\AnswerStorage $storage
   *   The answer storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AnswerStorage $storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->storage = $storage;
    $this->itemData = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('question_field.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Get the values.
    $values = $this->storage->getItemValues($items);
    foreach ($items as $delta => $item) {
      $this->itemData[$delta] = new QuestionFormatterItemData($item, $values ? $values[$delta] : '');
    }

    // Mark which questions are follow-ups.
    foreach ($items as $delta => $item) {
      foreach ($item->getAnswerOptions() as $option) {
        foreach ($option->getFollowups() as $followup) {
          $this->itemData[$followup]->addAsFollowupFromOriginal($delta);
        }
      }
    }

    // Create the build array from the values.
    $build = [];
    $element = [
      '#cache' => [
        'tags' => [
          $this->storage->getItemCacheTags($items),
        ],
      ],
    ];
    foreach ($items as $delta => $item) {
      // Skip followup questions and answers here. They are displayed with
      // the original question.
      if ($this->itemData[$delta]->getFollowupsFromOriginal()) {
        continue;
      }

      // Add this item to the build array.
      $build = array_merge($build, $this->getItemBuild($delta, $element));
    }

    return $build;
  }

  /**
   * Get the item build array.
   *
   * @param int $delta
   *   The delta index into $itemData.
   * @param array $element
   *   The default build array with cache tags.
   */
  protected function getItemBuild($delta, array $element) {
    $build = [];

    // Get the item data.
    $value = $this->itemData[$delta]->getValue();
    $item = $this->itemData[$delta]->getItem();

    // Get the question.
    $markup = $item->getQuestion();

    // Add the answer or potential answer to the markup.
    if ($value) {
      // Add the answer to the markup.
      $markup .= ': ' . (is_array($value) ? implode(', ', $value) : $value);
    }
    else {
      // Add the potential answers to the markup.
      $answer_options = $item->getAnswerOptions();
      if (!$answer_options) {
        return [];
      }
      $sep = substr($markup, -1) == '?' ? ' ' : ': ';
      $markup .= $sep . $this->implode(array_map(function ($option) {
        /** @var \Drupal\question_field\AnswerOptions $option */
        return $option->getValue();
      }, $answer_options));
    }

    // Add the markup to the build array.
    $build[] = [
      '#type' => 'markup',
      '#markup' => $markup,
    ] + $element;

    // Show follow-up questions and answers.
    if ($value && !is_array($value)) {
      foreach ($item->getAnswerOptions() as $options) {
        if ($value == $options->getValue()) {
          foreach ($options->getFollowups() as $followup) {
            $build = array_merge($build, $this->getItemBuild($followup, $element));
          }
          break;
        }
      }
    }

    return $build;
  }

  /**
   * Construct a string of options.
   *
   * Construct a string of options where the final separator is different than
   * the other separators.
   *
   * @param array $options
   *   The options.
   * @param string $separator
   *   (optional) The separator.
   * @param string $last_separator
   *   (optional) The separator before the last option.
   *
   * @return string
   *   The imploded string.
   */
  protected function implode(array $options, $separator = ', ', $last_separator = NULL) {
    if (!$options) {
      return '';
    }
    elseif (count($options) == 1) {
      return reset($options);
    }
    else {
      if (!$last_separator) {
        $last_separator = t(' or ');
      }
      $last_option = array_pop($options);
      return implode($separator, $options) . $last_separator . $last_option;
    }
  }

}
