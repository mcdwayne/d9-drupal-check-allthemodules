<?php

namespace Drupal\tagadelic\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render a list of Tagadelic Tags.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "tagadelic_list",
 *   title = @Translation("Tagadelic List"),
 *   help = @Translation("Render a list of Tagadelic Tags."),
 *   theme = "tagadelic_view_tagadelic_list",
 *   display_types = { "normal" }
 * )
 *
 */
class TagadelicList extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

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
   * Should field labels be enabled by default.
   *
   * @var bool
   */
  protected $defaultFieldLabels = TRUE;
  
  /**
   * Set default options
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['path'] = array('default' => '');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = array('' => $this->t('- None -'));
    $field_labels = $this->displayHandler->getFieldLabels(TRUE);
    $options += $field_labels;

    $handlers = $this->displayHandler->getHandlers('field');
    if (empty($handlers)) {
      $form['error_markup'] = array(
        '#markup' => '<div class="messages messages--error">' . $this->t('You need at least one field before you can configure your table settings') . '</div>',
      );
      return;
    }
    
    $form['count_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Count field'),
      '#description' => $this->t('The field that will be used to caculate the text size.'),
      '#options' => $options,
      '#default_value' => $this->options['count_field'],
    );
  }
}
