<?php

/**
 * @file
 * Contains \Drupal\wsdata_block\Plugin\Block\WSDataBlock.
 */

namespace Drupal\wsdata_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Green House Gas Emissions' block.
 *
 * @Block(
 *   id = "wsdata_block",
 *   admin_label = @Translation("Wsdata Block"),
 *   category = @Translation("wsdata")
 * )
 */
class WSDataBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $wscall = $this->configuration['wscall'];

    // Occasionally this can return a subfomstate and not a form_state interface.
    if ($form_state instanceof \Drupal\Core\Form\SubformState) {
      $form_state = $form_state->getCompleteFormState();
    }

    $form_state_wscall = $form_state->getValue('settings');
    if (isset($form_state_wscall['wscall'])) {
      $wscall = $form_state_wscall['wscall'];
    }

    $wsdata  = \Drupal::service('wsdata');
    $elements = $wsdata->wscallForm($this->configuration, $wscall);
    $form = array_merge($form, $elements);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['wscall'] = $form_state->getValue('wscall');
    // Loop thru the replacements and save them as an array.
    $replacement = [];
    $wscall = entity_load('wscall', $this->configuration['wscall']);
    foreach ($wscall->getReplacements() as $rep) {
      $replacement[$rep] = $form_state->getValue('replacements')[$rep];
    }
    $this->configuration['replacements'] = $replacement;
    $this->configuration['data'] = $form_state->getValue('data');
    $this->configuration['returnToken'] = $form_state->getValue('returnToken');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = [];
    $wsdata  = \Drupal::service('wsdata');
    $result = $wsdata->call($this->configuration['wscall'], NULL, $this->configuration['replacements'], $this->configuration['data'], array(), $this->configuration['returnToken']);

    $form['wsdata_block_data'] = [
      '#prefix' => '<div class="wsdata_block">',
      '#suffix' => '</div>',
      '#markup' => is_array($result) ? print_r($result, TRUE) : $result,
    ];

    return $form;
  }
}
