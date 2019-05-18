<?php

namespace Drupal\semantic_map\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "hello_block",
 *   admin_label = @Translation("Hello block"),
 *   category = @Translation("Hello World"),
 * )
 */
class SemanticBlock extends BlockBase implements BlockPluginInterface {

  // func adds "Hello, World!" to the #markup
  public function build() {
    return array(
      '#markup' => $this->t('Hello, World!'),
    );
  }

  // adds a form
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['hello_block_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Who'),
      '#description' => $this->t('Who do you want to say hello to?'),
      '#default_value' => isset($config['hello_block_name']) ? $config['hello_block_name'] : '',
    ];

    return $form;
  }

  // saves the form
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['hello_block_name'] = $form_state->getValue('hello_block_name');
  }

}
