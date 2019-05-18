<?php

/**
 * Planyo Block
 *
 * @Block(
 *   id = "planyo_block",
 *   admin_label = @Translation("Reservation"),
 * )
 */

namespace Drupal\planyo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\planyo\Common\PlanyoUtils;

class PlanyoBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $attrib_string = "";
    if (isset($config['attrib_string'])) {
      $attrib_string = $config['attrib_string'];
      global $planyo_attribs;
      $planyo_attribs = $attrib_string;
    }

    $content = PlanyoUtils::planyo_display_block_content();
    $content['#type'] = 'markup';
    $content['#title'] = $this->t('Reservation');
    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['planyo_block_attrib_string'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('Attributes string'),
      '#description' => $this->t("Consult the FAQ question <a href='https://www.planyo.com/faq.php?q=167' target='_blank'>Q167</a> or go to the <a href='https://www.planyo.com/integration-attr-str.php?tool=DP' target='_blank'>attribute string generator</a>. Example: mode=reserve&resource_id=123 will display the reservation form for resource 123 (where 123 is a number representing a resource ID)."),
      '#default_value' => isset($config['attrib_string']) ? $config['attrib_string'] : ''
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('attrib_string', $form_state->getValue('planyo_block_attrib_string'));
  }
}

?>