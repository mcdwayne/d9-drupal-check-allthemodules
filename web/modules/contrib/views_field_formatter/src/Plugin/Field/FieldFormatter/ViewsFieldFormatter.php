<?php

declare(strict_types = 1);

namespace Drupal\views_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Class ViewsFieldFormatter.
 *
 * @FieldFormatter(
 *   id = "views_field_formatter",
 *   label = @Translation("View"),
 *   description = @Translation("Todo"),
 *   weight = 100,
 *   field_types = {
 *     "boolean",
 *     "changed",
 *     "comment",
 *     "computed",
 *     "created",
 *     "datetime",
 *     "decimal",
 *     "email",
 *     "entity_reference",
 *     "entity_reference_revisions",
 *     "expression_field",
 *     "file",
 *     "float",
 *     "image",
 *     "integer",
 *     "language",
 *     "link",
 *     "list_float",
 *     "list_integer",
 *     "list_string",
 *     "map",
 *     "path",
 *     "string",
 *     "string_long",
 *     "taxonomy_term_reference",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "timestamp",
 *     "uri",
 *     "uuid"
 *     }
 * )
 */
class ViewsFieldFormatter extends FormatterBase {

  /**
   * Custom ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element.
   */
  public function ajaxAddRow(array &$form, FormStateInterface $form_state): array {
    /** @var \Drupal\field\FieldConfigInterface $fieldConfig */
    $fieldConfig = $this->fieldDefinition;

    return $form['fields'][$fieldConfig->getName()]['plugin']['settings_edit_form']['settings']['arguments'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    list($view_id) = \explode('::', $this->getSetting('view'), 2);
    // Don't call the current view, as it would result into an
    // infinite recursion.
    // TODO: Check for infinite loop here.
    if ($view_id !== NULL && $view = View::load($view_id)) {
      $dependencies[$view->getConfigDependencyKey()][] = $view->getConfigDependencyName();
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view' => '',
      'arguments' => [],
      'hide_empty' => FALSE,
      'multiple' => FALSE,
      'implode_character' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();

    // Get all views select options.
    $options = [];
    foreach (Views::getAllViews() as $view) {
      foreach ($view->get('display') as $display) {
        $label = $view->get('label');

        $options[$label][$view->get('id') . '::' . $display['id']] =
          \sprintf('%s - %s', $label, $display['display_title']);
      }
    }

    // Early return if there is no views.
    if ([] === $options) {
      $element['help'] = [
        '#markup' => '<p>' . $this->t('No available Views were found.') . '</p>',
      ];

      return $element;
    }

    $checked_arguments = \array_filter(
      (array) $settings['arguments'],
      function ($argument) {
        return $argument['checked'];
      }
    );

    // Make sure we only save arguments that are enabled.
    $settings['arguments'] = \array_values($checked_arguments);
    $this->setSettings($settings);

    $ajax_arguments_count = 'ajax_arguments_count_' . $this->fieldDefinition->getName();

    if ($form_state->get($ajax_arguments_count) === NULL) {
      $form_state->set($ajax_arguments_count, \count($checked_arguments));
    }

    // Ensure we clicked the Ajax button.
    // @todo Is there a better way to detect this ?
    $trigger = $form_state->getTriggeringElement();
    if (\is_array($trigger['#array_parents']) && \end($trigger['#array_parents']) === 'addRow') {
      $form_state->set($ajax_arguments_count, $form_state->get($ajax_arguments_count) + 1);
    }

    $element['view'] = [
      '#title' => $this->t('View'),
      '#description' => $this->t("Select the view that will be displayed instead of the field's value."),
      '#type' => 'select',
      '#default_value' => $this->getSetting('view'),
      '#options' => $options,
    ];

    $element['arguments'] = [
      '#prefix' => '<div id="ajax_form_table_arguments">',
      '#suffix' => '</div>',
      '#type' => 'table',
      '#header' => [
        '',
        $this->t('Weight'),
        $this->t('Argument index'),
        $this->t('String or token'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'arguments-order-weight',
        ],
      ],
      '#caption' => $this->t(
        'Select,add and reorder the arguments that will be used by the selected
         view as contextual filters.
         To remove some rows, uncheck the checkbox and save.'
      ),
    ];

    for ($i = 0; $i < $form_state->get($ajax_arguments_count); $i++) {
      $element['arguments'][] = [
        'checked' => [
          '#type' => 'checkbox',
          '#title' => '',
          '#default_value' => $this->getSettings()['arguments'][$i]['checked'],
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => 'token']),
          '#title_display' => 'invisible',
          '#attributes' => ['class' => ['arguments-order-weight']],
        ],
        'argument_index' => [
          '#markup' => $i,
        ],
        'token' => [
          '#type' => 'textfield',
          '#title' => 'Argument',
          '#description' => $this->t('Use a static string or a Drupal token.'),
          '#default_value' => $this->getSettings()['arguments'][$i]['token'],
        ],
        '#attributes' => ['class' => ['draggable']],
      ];
    }

    $element['addRow'] = [
      '#type' => 'button',
      '#button_type' => 'secondary',
      '#value' => t('Add a new argument'),
      '#ajax' => [
        'callback' => [$this, 'ajaxAddRow'],
        'event' => 'click',
        'wrapper' => 'ajax_form_table_arguments',
      ],
    ];

    $types = ['site', 'user', 'entity', 'field', 'date'];

    switch ($this->fieldDefinition->getTargetEntityTypeId()) {
      case 'taxonomy_term':
        $types[] = 'term';
        $types[] = 'vocabulary';

        break;

      default:
        $types[] = $this->fieldDefinition->getTargetEntityTypeId();

        break;
    }

    $token = \Drupal::token();
    $info = $token->getInfo();

    $available_token = \array_intersect_key(
      $info['tokens'],
      \array_flip($types)
    );

    $token_items = [];
    foreach ($available_token as $type => $tokens) {
      $item = [
        '#markup' => $this->t('@type tokens', ['@type' => \ucfirst($type)]),
        'children' => [],
      ];

      foreach ($tokens as $name => $info) {
        $info += [
          'description' => $this->t('No description available'),
        ];

        $item['children'][$name] = \sprintf('[%s:%s] - %s: %s', $type, $name, $info['name'], $info['description']);
      }

      $token_items[$type] = $item;
    }

    $element['token_tree_link'] = [
      '#type' => 'details',
      '#title' => $this->t('Available token replacements'),
      'description' => [
        '#markup' => $this->t('To have more tokens, please install the <a href="@token">Token contrib module</a>.', ['@token' => 'https://drupal.org/project/token']),
      ],
    ];

    $element['token_tree_link']['list'] = [
      '#theme' => 'item_list',
      '#items' => $token_items,
      '#attributes' => [
        'class' => ['global-tokens'],
      ],
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['token_tree_link'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => $types,
      ];
    }

    $element['hide_empty'] = [
      '#title' => $this->t('Hide empty views'),
      '#description' => $this->t('Do not display the field if the view is empty.'),
      '#type' => 'checkbox',
      '#default_value' => (bool) $this->getSetting('hide_empty'),
    ];

    $element['multiple'] = [
      '#title' => $this->t('Multiple'),
      '#description' => $this->t(
        'If the field is configured as multiple (<em>greater than one</em>),
         should we display a view per item ? If selected, there will be one view per item.'
      ),
      '#type' => 'checkbox',
      '#default_value' => (bool) $this->getSetting('multiple'),
    ];

    $element['implode_character'] = [
      '#title' => $this->t('Concatenate arguments'),
      '#description' => $this->t(
        'If it is set, all arguments will be concatenated with the chosen character (<em>ex: a simple comma</em>)
         and sent as one argument. Empty to disable.'
      ),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('implode_character'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    // For default settings, don't show a summary.
    if ($settings['view'] === '') {
      return [
        $this->t('Not configured yet.'),
      ];
    }

    list($view, $view_display) = \explode('::', $settings['view'], 2);
    $multiple = ((bool) $settings['multiple'] === TRUE) ? 'Enabled' : 'Disabled';
    $hide_empty = ((bool) $settings['hide_empty'] === TRUE) ? 'Hide' : 'Display';

    $arguments = \array_map(
      function ($argument) {
        return 'Token';
      },
      \array_keys(
        \array_filter(
          (array) $settings['arguments'],
          function ($argument) {
            return $argument['checked'];
          }
        )
      )
    );

    if ([] === $arguments) {
      $arguments[] = $this->t('None');
    }

    if ($view !== NULL) {
      $summary[] = t('View: @view', ['@view' => $view]);
      $summary[] = t('Display: @display', ['@display' => $view_display]);
      $summary[] = t('Argument(s): @arguments', ['@arguments' => \implode(', ', $arguments)]);
      $summary[] = t('Empty views: @hide_empty empty views', ['@hide_empty' => $hide_empty]);
      $summary[] = t('Multiple: @multiple', ['@multiple' => $multiple]);
    }

    if (((bool) $settings['multiple'] === TRUE) && ($settings['implode_character'] !== '')) {
      $summary[] = t('Implode character: @character', ['@character' => $settings['implode_character']]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();

    if (isset($settings['view']) && !empty($settings['view']) && \strpos($settings['view'], '::') !== FALSE) {
      list($view_id, $view_display) = \explode('::', $settings['view'], 2);
    }
    else {
      return $elements;
    }

    // First check the availability of the view.
    $view = Views::getView($view_id);
    if (!$view || !$view->access($view_display)) {
      return $elements;
    }

    $user_arguments = \array_filter(
      (array) $this->getSetting('arguments'),
      function ($argument) {
        return $argument['checked'];
      }
    );

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getParent()->getValue();

    $token = \Drupal::token();

    $arguments = [];
    foreach ($user_arguments as $delta_argument => $item_argument) {
      foreach ($items as $delta_item => $item) {
        $replacements = [
          $entity->getEntityTypeId() => $entity,
          'entity' => $entity,
          'views_field_formatter' => [
            'delta' => $delta_item,
            'item' => $item,
            'items' => $items,
          ],
        ];

        switch ($this->fieldDefinition->getTargetEntityTypeId()) {
          case 'taxonomy_term':
            $replacements['term'] = $entity;
            $replacements['vocabulary'] = Vocabulary::load($entity->getVocabularyId());

            break;
        }

        $arguments[$delta_argument][] = $token->replace($item_argument['token'], $replacements);
      }
    }

    if ((bool) $settings['multiple'] === TRUE) {
      foreach ($items as $delta => $item) {
        $viewArray = $this->getViewArray(
          $view,
          $view_display,
          \array_column($arguments, $delta),
          $settings
        );

        if ([] !== $viewArray) {
          $elements[$delta] = $viewArray;
        }
      }
    }
    else {
      foreach ($arguments as $delta_argument => $item_argument) {
        $arguments[$delta_argument] = \implode($settings['implode_character'], $arguments[$delta_argument]);
      }

      $viewArray = $this->getViewArray(
        $view,
        $view_display,
        $arguments,
        $settings
          );

      if ([] !== $viewArray) {
        $elements[0] = $viewArray;
      }
    }

    return $elements;
  }

  /**
   * Custom function to generate a view render array.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param string $view_display
   *   The view display.
   * @param array $arguments
   *   The arguments to pass to the view.
   * @param array $settings
   *   The field formatter settings.
   *
   * @return array
   *   A render array.
   */
  private function getViewArray(ViewExecutable $view, $view_display, array $arguments, array $settings): array {
    if ((bool) $settings['hide_empty'] === TRUE) {
      $view->setArguments($arguments);
      $view->setDisplay($view_display);
      $view->preExecute();
      $view->execute();

      if (empty($view->result)) {
        return [];
      }
    }

    return [
      '#type' => 'view',
      '#name' => $view->id(),
      '#display_id' => $view_display,
      '#arguments' => $arguments,
    ];
  }

}
