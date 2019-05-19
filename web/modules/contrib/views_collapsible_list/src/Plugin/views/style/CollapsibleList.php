<?php

namespace Drupal\views_collapsible_list\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\HtmlList;

/**
 * Style plugin to render a list of collapsible items.
 *
 * @ViewsStyle(
 *   id = "collapsible_list",
 *   title = @Translation("Collapsible list"),
 *   help = @Translation("Displays rows as an HTML list that can be expanded and collapsed to show more details."),
 *   theme = "views_view_collapsible_list",
 *   display_types = {"normal"}
 * )
 */
class CollapsibleList extends HtmlList {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['collapsible_fields'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remove a little configurability for the sake of enforcing styling.
    $form['type'] = ['#type' => 'value', '#value' => 'ul'];
    $form['wrapper_class'] = ['#type' => 'value', '#value' => 'views-collapsible-list'];

    $form['collapsible_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Collapsible fields'),
      '#description' => $this->t('The fields that will be expanded and collapsed when the row is toggled.'),
      '#options' => $this->view->display_handler->getFieldLabels(),
      '#default_value' => $this->options['collapsible_fields'],
    ];
  }

}
