<?php

namespace Drupal\modal_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a 'ModalBlock' block.
 *
 * @Block(
 *  id = "modal_block",
 *  admin_label = @Translation("Modal block"),
 * )
 */
class ModalBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();
    $form['block'] = array (
     '#type' => 'entity_autocomplete',
     '#title' => 'Block',
     '#target_type' => 'block',
    );

    $form['frequency'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('Frequency'),
      '#number_type' => 'integer',
      '#default_value' => isset($config['frequency']) ? $config['frequency'] : '',
    );

    $form['period'] = array (
      '#type' => 'select',
      '#title' => ('Period'),
      '#options' => array (
        'hour' => t('Hour'),
        'day' => t('Day'),
        'week' => t('Week'),
        'month' => t('Month'),
      ),
      '#default_value' => isset($config['period']) ? $config['period'] : '',
    );

    $rand = 'modal-block-'.rand(100000,999999);
    $form['random'] = array (
      '#type' => 'hidden',
      '#value' => isset($config['random']) ? $config['random'] : $rand,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
  	$this->setConfigurationValue('block', $form_state->getValue('block'));
    $this->setConfigurationValue('frequency', $form_state->getValue('frequency'));
    $this->setConfigurationValue('period', $form_state->getValue('period')); 
    $this->setConfigurationValue('random', $form_state->getValue('random')); 
  }

  public function build() {
    $config = $this->getConfiguration();
    $block = $config['block'];
    $frequency = $config['frequency'];
    $period = $config['period'];
    $random = $config['random'];

    switch ($period) {
      case 'hour':
         $time = 1*60*60*1000;
         break;
      case 'day':
         $time = 1*24*60*60*1000;
         break;
      case 'week':
        $time = 7*24*60*60*1000;
        break;
      case 'month':
        $time = 30*24*60*60*1000;
        break;
    }

    $block = \Drupal\block\Entity\Block::load($block);
    $block_content = \Drupal::entityManager()
                     ->getViewBuilder('block')
                     ->view($block);
    $block_render = array ('#markup' => drupal_render($block_content));
    $modal_block[] = array (
      '#theme' => 'modal_block_formatter',
      '#block' => $block_render,
    );

    $modal_block['#attached']['drupalSettings']['modal_blocks']['frequency'] = $frequency;
    $modal_block['#attached']['drupalSettings']['modal_blocks']['period'] = $time;
    $modal_block['#attached']['drupalSettings']['modal_blocks']['random'] = $random;
   
    return $modal_block;
  }
}