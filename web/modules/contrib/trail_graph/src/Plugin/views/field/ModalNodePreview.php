<?php

namespace Drupal\trail_graph\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("modal_preview")
 */
class ModalNodePreview extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['hide_alter_empty'] = ['default' => FALSE];
    $options['text'] = ['default' => 'Preview'];
    $options['view_mode'] = ['default' => 'default'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['text'] = [
      '#title' => $this->t('Alternative text of the button.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['text'],
      '#size' => 50,
      '#maxlength' => 60,
    ];
    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => \Drupal::service('entity_display.repository')->getViewModeOptions($this->getEntityType()),
      '#title' => $this->t('View mode'),
      '#default_value' => $this->options['view_mode'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $link_url = Url::fromRoute('trail_graph.modal_node_preview_iframe_render', ['node_preview' => $values->_entity->id(), 'view_mode_id' => $this->options['view_mode']]);
    $link_url->setOptions([
      'attributes' => [
        'class' => ['use-ajax'],
      ],
    ]);
    $node_preview = Link::fromTextAndUrl($this->options['text'], $link_url)->toString();
    return $node_preview;
  }

}
