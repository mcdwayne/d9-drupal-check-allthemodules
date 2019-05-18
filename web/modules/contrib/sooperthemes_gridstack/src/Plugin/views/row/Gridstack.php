<?php

namespace Drupal\sooperthemes_gridstack\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * The basic 'Gridstack' row plugin.
 *
 * @ViewsRow(
 *   id = "sooperthemes_gridstack_gridstack",
 *   title = @Translation("Gridstack"),
 *   help = @Translation("Displays the fields with an Gridstack template."),
 *   theme = "sooperthemes_gridstack_gridstack_row",
 *   display_types = {"normal"}
 * )
 */
class Gridstack extends RowPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['image'] = ['default' => ''];
    $options['title'] = ['default' => ''];
    $options['category'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Pre-build all of our option lists for the dials and switches that follow.
    $fields = [];
    foreach ($this->displayHandler->getHandlers('field') as $field => $handler) {
      $fields[$field] = $field;
    }

    $form['image'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('Image'),
      '#options' => ['' => t('- None -')] + $fields,
      '#default_value' => $this->options['image'],
    ];

    $form['title'] = [
      '#type' => 'select',
      '#title' => t('Title'),
      '#options' => ['' => t('- None -')] + $fields,
      '#default_value' => $this->options['title'],
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => t('Category'),
      '#options' => ['' => t('- None -')] + $fields,
      '#default_value' => $this->options['category'],
    ];
  }

}
