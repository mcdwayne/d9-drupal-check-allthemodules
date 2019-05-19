<?php

namespace Drupal\shi\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a 'Simple Header Image' Block.
 *
 * @Block(
 *   id = "simple-header-image",
 *   admin_label = @Translation("Simple Header Image"),
 * )
 */
class simple_header_image extends BlockBase implements BlockPluginInterface {
  public function blockForm($form, FormStateInterface $form_state) {
      $form = parent::blockForm($form, $form_state);

      $config = $this->getConfiguration();

      $form['shi'] = array(
        '#title' => $this->t('Simple Header Image'),
        '#type' => 'fieldset',
      );
      $form['shi']['title'] = array(
        '#title' => $this->t('Page Title'),
        '#description' => $this->t('Use the Page Title as the header caption'),
        '#default_value' => isset($config['simple_header_image']) ? $config['simple_header_image'] : '',
        '#type' => 'checkbox',
      );

      return $form;
    }
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['simple_header_image'] = $values['shi']['title'];
  }
  /* *
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#markup' => ' ',
      '#cache' => ['max-age' => 0,],
    );
  }
}
