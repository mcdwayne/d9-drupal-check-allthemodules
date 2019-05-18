<?php

namespace Drupal\amp\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Plugin\Block\SystemBrandingBlock;

/**
 * Provides a block to display 'Site branding' elements.
 *
 * @Block(
 *   id = "amp_system_branding_block",
 *   admin_label = @Translation("AMP Site branding"),
 *   forms = {
 *     "settings_tray" = "Drupal\system\Form\SystemBrandingOffCanvasForm",
 *   },
 * )
 */
class AmpSystemBrandingBlock extends SystemBrandingBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'logo_width' => NULL,
      'logo_height' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['block_branding']['logo_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site logo width'),
      '#default_value' => $this->configuration['logo_width'],
      '#required' => TRUE,
    ];
    $form['block_branding']['logo_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site logo height'),
      '#default_value' => $this->configuration['logo_height'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $block_branding = $form_state->getValue('block_branding');
    $this->configuration['logo_width'] = $block_branding['logo_width'];
    $this->configuration['logo_height'] = $block_branding['logo_height'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $build['logo_width'] = $this->configuration['logo_width'];
    $build['logo_height'] = $this->configuration['logo_height'];
    return $build;
  }

}
