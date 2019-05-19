<?php

namespace Drupal\trumba\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'TrumbaPromoControlSpudBlock' block.
 *
 * @Block(
 *  id = "trumba_promo_control_spud_block",
 *  admin_label = @Translation("Trumba Promotional or Control Calendar Spud"),
 * )
 */
class TrumbaPromoControlSpudBlock extends TrumbaBlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form = parent::blockForm($form, $form_state);
    $form['trumba_spud_url']['#description'] .= ' <strong>' . $this->t('Only necessary if this spud will NOT be on the same page as the main calendar spud! ') . '</strong>';

    $form['trumba_promo_control_spud_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Spud Type'),
      '#description' => $this->t('Select the type of spud this should be.'),
      '#options' => array('upcoming' => $this->t('Upcoming'),
        'datefinder' => $this->t('Date Finder'),
        'daysummary' => $this->t('Day Summary'),
        'searchlabeled' => $this->t('Search'),
        'monthlist' => $this->t('Month List'),
        'tabchooser' => $this->t('View Chooser Tabbed'),
        'filter' => $this->t('filter')),
      '#default_value' => isset($this->configuration['trumba_promo_control_spud_type']) ? $this->configuration['trumba_promo_control_spud_type'] : '',
      '#size' => 1,
      '#weight' => '1',
      '#required' => TRUE,
      '#empty_value' => '',
      '#empty_option' => t('- Select -')
    ];
    $form['trumba_promo_control_spud_configuration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spud Configuration'),
      '#description' => $this->t('If the spud type requires configuration text enter it here.'),
      '#default_value' => isset($this->configuration['trumba_promo_control_spud_configuration']) ? $this->configuration['trumba_promo_control_spud_configuration'] : '',
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
    $this->configuration['trumba_promo_control_spud_type'] = $form_state->getValue('trumba_promo_control_spud_type');
    $this->configuration['trumba_promo_control_spud_configuration'] = $form_state->getValue('trumba_promo_control_spud_configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $params = [
      'spudId' => $this->spudId,
      'webName' => $this->configuration['trumba_web_name'],
      'teaserBase' => $this->convertUriToAbsolutePathOrUrl($this->configuration['trumba_spud_url']),
      'spudType' => $this->configuration['trumba_promo_control_spud_type'],
      'spudConfig' => $this->configuration['trumba_promo_control_spud_configuration']
    ];
    $cache_spud_id = str_ireplace('_','-',$this->getPluginId());
    return _trumba_spud_embed($this->spudId, $params, $cache_spud_id);
  }

}
