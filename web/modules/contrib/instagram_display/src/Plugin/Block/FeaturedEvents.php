<?php

namespace Drupal\instagram_display\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Example: configurable text string' block.
 *
 * Drupal\Core\Block\BlockBase gives us a very useful set of basic functionality
 * for this configurable block. We can just fill in a few of the blanks with
 * defaultConfiguration(), blockForm(), blockSubmit(), and build().
 *
 * @Block(
 *   id = "instagram_posts",
 *   admin_label = @Translation("BYU Instagram: Recent Posts")
 * )
 */
class FeaturedEvents extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'instagram_display_string' => $this->t('A default value. Katria, This block was created at %time', array('%time' => date('c'))),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
//      $form['instagram_display_category_id'] = array(
//          '#type' => 'textfield',
//          '#default_value' => theme_get_setting('instagram_display_category_id'),
//          '#description' => 'The Category id of the category for which you wish to display events.',
//      );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $category = $form_state->getValue('instagram_display_category_id');
      $this->configuration['instagram_display_string']
      = $form_state->getValue('instagram_display_category_id');
//      = instagram_display_build_display($category);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
//      $category = $form_state->getValue('instagram_display_category_id');
      $category = '6+4+90';
      $html = instagram_display_build_display($category);
//      $html = instagram_display_fetch_events($category);
//      $html = 'this is a new test';
    return array(
        '#type' => 'markup',
        //      '#markup' => $this->configuration['instagram_display_string'],
        '#markup' =>  $html,
        '#attached' => array(
            'library' => array(
                'instagram_display/feature-styles',
            ),
        ),
    );
  }




}
