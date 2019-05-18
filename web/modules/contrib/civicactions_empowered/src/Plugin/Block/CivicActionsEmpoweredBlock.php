<?php

namespace Drupal\civicactions_empowered\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'CivicActionsEmpoweredBlock' block.
 *
 * @Block(
 *  id = "civic_actions_empowered_block",
 *  admin_label = @Translation("CivicActions Empowered block"),
 * )
 */
class CivicActionsEmpoweredBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
    ];
  }

  /**
   * {@inherhitdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['display_text'] = [
      '#type' => 'textfield',
      '#format' => 'restricted_html',
      '#title' => $this->t('CivicActions Empowered block text'),
      '#description' => $this->t('Modify the text as you wish'),
      '#default_value' => isset($config['display_text']) ? $config['display_text'] : '<a href="https://civicactions.com">CivicActions</a> Empowered',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['display_text'] = $values['display_text'];
    drupal_set_message("CivicActions Empowered block value set to \"{$this->configuration['display_text']}\"");
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    if (!empty($config['display_text'])) {
      $dt = $config['display_text'];
    }
    else {
      $dt = t('<a href="https://civicactions.com">CivicActions</a> Empowered');
    }
    $build['civic_actions_empowered_block']['#markup'] = $dt;
    return $build;
  }

}
