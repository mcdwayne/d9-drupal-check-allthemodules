<?php

/**
 * @file
 * Contains \Drupal\elfinder\Plugin\Block\elFinderBlock.
 */

namespace Drupal\elfinder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elfinder\Controller\elFinderPageController;

/**
 * Provides a 'elFinder block' block.
 *
 * @Block(
 *   id = "elfinder",
 *   admin_label = @Translation("elFinder")
 * )
 */
class elFinderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'elfinder_string' => $this->t('A default value. This block was created at %time', array('%time' => date('c'))),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['elfinder_string_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Block contents'),
      '#description' => $this->t('This text will appear in the example block.'),
      '#default_value' => $this->configuration['elfinder_string'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['elfinder_string'] = $form_state->getValue('elfinder_string_text');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
  
    $build = \Drupal\elfinder\Controller\elFinderPageController::buildBrowserPage(TRUE);
    
    
    $build['#theme'] = 'browser_page';
    return $build;
  }

}
