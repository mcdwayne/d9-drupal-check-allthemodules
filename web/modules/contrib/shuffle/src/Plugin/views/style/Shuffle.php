<?php

namespace Drupal\shuffle\Plugin\views\style;

use Drupal\Component\Utility\Html;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Shuffle style plugin to render rows in a shuffle grid.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "shuffle",
 *   title = @Translation("Shuffle grid"),
 *   help = @Translation("Displays rows in a shuffle grid."),
 *   theme = "views_view_shuffle",
 *   display_types = {"normal"}
 * )
 */
class Shuffle extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;


  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['speed'] = array('default' => 250);
    $options['easing'] = array('default' => 'ease-out');
    $options['sizerMethod'] = array('default' => 'sizer');
    $options['sizer'] = array('default' => '.shuffle-item');
    $options['columnWidth'] = array('default' => '250');
    $options['gutterWidth'] = array('default' => '10');
    $options['filter'] = array('default' => 'none');
    $options['useAllFilter'] = array('default' => '0');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $fields_filter = [];
    $fields = $this->displayHandler->getOption('fields');

    foreach ($fields as $field_name => $field) {
      // We support only Entity Reference field with formatter set on label
      // and width a multi_type set on separator width a pipe (|).
      if ($field['type'] == 'entity_reference_label'
        && $field['multi_type'] == 'separator'
        && $field['separator'] == '|') {
        $fields_filter[$field_name] = $field['field'];
      }
    }

    $form['speed'] = array(
      '#title' => $this->t('Speed'),
      '#description' => $this->t('The transition/animation speed (in milliseconds).'),
      '#type' => 'textfield',
      '#default_value' => $this->options['speed'],
    );

    $form['easing'] = array(
      '#title' => $this->t('Easing'),
      '#description' => $this->t('The CSS easing function to use for transition.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['easing'],
    );

    $form['sizerMethod'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Method to determine the width of columns and gutters'),
      '#description' => $this->t('The width of each element can be set manually or be set using a class which handle width and margin. Using a class with the sizer method is useful to have a responsive grid'),
      '#default_value' => $this->options['sizerMethod'],
      '#options' => array('sizer' => $this->t('Sizer'), 'manual' => $this->t('Manual')),
    );

    $form['sizer'] = array(
      '#title' => $this->t('Sizer'),
      '#description' => $this->t('Use a element to determine the size of columns and gutters. Use a complete CSS class name with the point (example: .shuffle-item, or .my-custom-class). The class .shuffle-item is added on every row.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['sizer'],
      '#states' => array(
        'visible' => array(
          'input[name="style_options[sizerMethod]"]' => array('value' => 'sizer'),
        ),
      ),
    );

    $form['columnWidth'] = array(
      '#title' => $this->t('Column width'),
      '#description' => $this->t('A static number which tells the plugin how wide the columns are (value in <strong>pixels</strong>).'),
      '#type' => 'number',
      '#default_value' => $this->options['columnWidth'],
      '#states' => array(
        'visible' => array(
          'input[name="style_options[sizerMethod]"]' => array('value' => 'manual'),
        ),
      ),
    );

    $form['gutterWidth'] = array(
      '#title' => $this->t('Gutter width'),
      '#description' => $this->t('A static number that tells the plugin how wide the gutters between columns are (value in <strong>pixels</strong>).'),
      '#type' => 'number',
      '#default_value' => $this->options['gutterWidth'],
      '#states' => array(
        'visible' => array(
          'input[name="style_options[sizerMethod]"]' => array('value' => 'manual'),
        ),
      ),
    );

    if (empty($fields_filter) and !$this->usesFields()) {
      $form['error_markup'] = array(
        '#markup' => '<div class="messages messages--warning">' . $this->t('If you want to filter rows using a field, you need to check <strong>Force using fields</strong> above and add at least one field to the view (field of type entity_reference) rendered by <strong>label</strong> and width a display type for multiple field settings set on <strong>separator with a pipe (|)</strong>.') . '</div>',
      );

    }

    $form['filter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select a field for filter options'),
      '#description' => $this->t('If you want to filter rows by some values, you need to select the field which will provides these filter values. Only Entity reference field with a formatter set to Label, and display type for multiple field settings set on separator with a pipe (|), are supported.'),
      '#default_value' => $this->options['filter'],
      '#options' => ['none' => $this->t('-None-')] + $fields_filter,
    );

    $form['useAllFilter'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show "All" filter'),
      '#description' => $this->t('Adds a static "All" filter that is automatically selected when deselecting custom filters.'),
      '#default_value' => $this->options['useAllFilter']
    );

  }


  /**
   * {@inheritdoc}
   */
  public function preRender($result) {
    parent::preRender($result);

    // No need to do anything if we we have only one result.
    if (count($result) < 2) {
      return;
    }

    $view_settings['display'] = $this->view->current_display;
    $view_settings['viewname'] = $this->view->storage->id();
    $shuffle_id = Html::cleanCssIdentifier('views-shuffle-' . $view_settings['viewname'] . '-' . $this->view->current_display);

    // Preparing the js variables and adding the js to our display.
    $view_settings['speed'] = ($this->options['speed']) ? $this->options['speed'] : 250;
    $view_settings['easing'] = ($this->options['easing']) ? $this->options['easing'] : 'ease-out';
    $view_settings['sizerMethod'] = $this->options['sizerMethod'];

    if ($this->options['sizerMethod'] == 'sizer') {
      $view_settings['sizer'] = ($this->options['sizer']) ? $this->options['sizer'] : 'views-row';
    }
    else {
      $view_settings['columnWidth'] = ($this->options['columnWidth']) ? $this->options['columnWidth'] : '250';
      $view_settings['gutterWidth'] = ($this->options['gutterWidth']) ? $this->options['gutterWidth'] : '10';
    }

    if ($this->options['filter'] != 'none') {
      $view_settings['filter'] = $this->options['filter'];
    }
    $view_settings['useAllFilter'] = $this->options['useAllFilter'];

    $this->view->element['#attached']['library'][] = 'shuffle/shuffle_plugin';
    $this->view->element['#attached']['library'][] = 'shuffle/shuffle';
    $this->view->element['#attached']['drupalSettings']['shuffle'] = [$shuffle_id => $view_settings];

  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {

    if ($form_state->getValue(['style_options', 'sizerMethod']) == 'sizer') {
      $form_state->setValue(['style_options', 'columnWidth'], '');
      $form_state->setValue(['style_options', 'gutterWidth'], '');
    }

    if ($form_state->getValue(['style_options', 'sizerMethod']) == 'manual') {
      $form_state->setValue(['style_options', 'sizer'], '');
    }
  }

}
