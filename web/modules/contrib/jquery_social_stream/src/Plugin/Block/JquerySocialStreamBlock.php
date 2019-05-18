<?php /**
 * @file
 * Contains \Drupal\jquery_social_stream\Plugin\Block\JquerySocialStreamBlock.
 */

namespace Drupal\jquery_social_stream\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the JquerySocialStream block.
 *
 * @Block(
 *   id = "jquery_social_stream",
 *   admin_label = @Translation("jQuery social stream")
 * )
 */
class JquerySocialStreamBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = jquery_social_stream_block_content($this->configuration);
    return $block['content'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    if (empty($this->configuration['id'])) {
      $this->configuration['id'] = 'block-jquery-social-stream';
    }
    return $form + jquery_social_stream_settings_form($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
  }

}
