<?php

namespace Drupal\trumba\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'TrumbaOpenSpudBlock' block.
 *
 * @Block(
 *  id = "trumba_open_spud_block",
 *  admin_label = @Translation("Trumba Open Spud"),
 * )
 */
class TrumbaOpenSpudBlock extends TrumbaBlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['trumba_spud_url']['#description'] .= ' <strong>' . $this->t('Only necessary if this spud will NOT be on the same page as the main calendar spud! ') . '</strong>';
    $form['trumba_spud_url']['#required'] = FALSE;

    $form['trumba_open_spud_spud_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spud Type'),
      '#description' => $this->t('Enter the name for the type of spud this should be.'),
      '#default_value' => isset($this->configuration['trumba_open_spud_spud_type']) ? $this->configuration['trumba_open_spud_spud_type'] : '',
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '1',
      '#required' => TRUE
    ];
    $form['trumba_open_spud_spud_configuration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spud Configuration'),
      '#description' => $this->t('If the spud type requires configuration text enter it here.'),
      '#default_value' => isset($this->configuration['trumba_open_spud_spud_configuration']) ? $this->configuration['trumba_open_spud_spud_configuration'] : '',
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '2',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['trumba_open_spud_spud_type'] = $form_state->getValue('trumba_open_spud_spud_type');
    $this->configuration['trumba_open_spud_spud_configuration'] = $form_state->getValue('trumba_open_spud_spud_configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $params = [
      'spudId' => $this->spudId,
      'webName' => $this->configuration['trumba_web_name'],
      'teaserBase' => $this->convertUriToAbsolutePathOrUrl($this->configuration['trumba_spud_url']),
      'spudType' => $this->configuration['trumba_open_spud_spud_type'],
      'spudConfig' => $this->configuration['trumba_open_spud_spud_configuration'],
    ];

    $cache_spud_id = str_ireplace('_','-',$this->getPluginId());
    return _trumba_spud_embed($this->spudId, $params, $cache_spud_id);
  }

}
