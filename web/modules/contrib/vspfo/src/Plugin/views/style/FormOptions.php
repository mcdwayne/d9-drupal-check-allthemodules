<?php

namespace Drupal\vspfo\Plugin\views\style;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\vspfo\Form\VspfoForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Style plugin to render each row as an option for a form element.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "vspfo_form_options",
 *   title = @Translation("Form options"),
 *   help = @Translation("Displays rows as options of a form element."),
 *   display_types = {"normal"},
 * )
 */
class FormOptions extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * FormOptions constructor.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->renderer = $renderer;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['value_field'] = ['default' => ''];
    $options['element_type'] = ['default' => 'select'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $handlers = $this->displayHandler->getHandlers('field');
    $fields = [];

    if (empty($handlers)) {
      $form['error_markup'] = [
        '#markup' => '<div class="messages messages--error">' . $this->t('You need at least one field before you can configure your form options settings.') . '</div>',
      ];
      return;
    }

    /** @var \Drupal\views\Plugin\views\field\FieldPluginBase $handler */
    foreach ($handlers as $id => $handler) {
      $fields[$id] = $handler->adminLabel();
    }

    $form['markup'] = [
      '#markup' => '<div class="js-form-item form-item description">' . $this->t('To properly configure form options, you must select one field that will represent the option value to utilize. You can then set that field to exclude from output. All other displayed fields will be part of the option. Please note that all HTML will be stripped from the output in the select box.') . '</div>',
    ];

    $form['value_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Option value'),
      '#options' => $fields,
      '#default_value' => $this->options['value_field'],
    ];

    $form['element_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Element type'),
      '#options' => [
        'checkboxes' => $this->t('Checkboxes'),
        'radios' => $this->t('Radios'),
        'select' => $this->t('Select'),
      ],
      '#default_value' => $this->options['element_type'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $sets = $this->renderGrouping($this->view->result, $this->options['grouping'], TRUE);
    $options = $this->processGroupingSets($sets);

    $form = VspfoForm::create(\Drupal::getContainer(), $this->view->id(), $this->view->current_display);
    $settings = [
      'options' => $options,
      'element_type' => $this->options['element_type'],
      'ajax' => $this->view->ajaxEnabled() || !empty($this->view->live_preview),
    ];
    return $this->formBuilder->getForm($form, $settings);
  }

  /**
   * Processes the grouping sets.
   *
   * @param array $sets
   *   Array containing the grouping sets.
   *
   * @return array
   *   Grouping sets as nested form options array.
   */
  public function processGroupingSets(array $sets) {
    $options = [];

    foreach ($sets as $set) {
      $group = $set['group'] ? (string) $set['group'] : NULL;

      $row = reset($set['rows']);
      // Process as a grouping set.
      if (is_array($row) && isset($row['group'])) {
        $options[$group] = $this->processGroupingSets($set['rows']);
      }
      // Process as a record set.
      else {
        foreach ($set['rows'] as $index => $row) {
          $this->view->row_index = $index;

          $option = $this->view->rowPlugin->render($row);
          $option = (string) $this->renderer->renderPlain($option);
          if ($this->options['element_type'] == 'select') {
            $option = trim(strip_tags(Html::decodeEntities($option)));
          }

          if (!empty($this->options['value_field'])) {
            $value = (string) $this->getField($this->view->row_index, $this->options['value_field']);
          }
          else {
            // Initially value field option may be missing.
            $value = $option;
          }

          if ($group) {
            $options[$group][$value] = $option;
          }
          else {
            $options[$value] = $option;
          }
        }
      }
    }
    unset($this->view->row_index);

    return $options;
  }

}
