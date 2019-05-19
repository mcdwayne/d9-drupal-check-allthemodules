<?php

namespace Drupal\trail_graph\Plugin\views\field;

use Drupal\Component\Serialization\Json;
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
 * @ViewsField("modal_edit_form")
 */
class ModalEditField extends FieldPluginBase {

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
    $options['width'] = ['default' => '90%'];
    $options['height'] = ['default' => '90%'];
    $options['text'] = ['default' => 'Edit'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['width'] = [
      '#title' => $this->t('Width of modal form.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['width'],
      '#size' => 10,
      '#maxlength' => 20,
      '#required' => TRUE,
    ];

    $form['height'] = [
      '#title' => $this->t('Height of modal form.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['height'],
      '#size' => 10,
      '#maxlength' => 20,
      '#required' => TRUE,
    ];

    $form['text'] = [
      '#title' => $this->t('Alternative text of the button.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['text'],
      '#size' => 50,
      '#maxlength' => 60,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $link_url = Url::fromRoute('entity.node.edit_form', ['node' => $values->_entity->id(), 'mim' => 'trail_graph']);
    $link_url->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'trail-graph--icon--edit'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => $this->options['width'],
          'height' => $this->options['height'],
        ]),
      ],
    ]);
    $node_edit = Link::fromTextAndUrl($this->options['text'], $link_url)->toString();
    return $node_edit;
  }

}
