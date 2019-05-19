<?php

/**
 * @file
 * Contains \Drupal\simple_tweets\Form\simpleTweetsBlockForm.
 */

namespace Drupal\simple_tweets\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the search form for the search block.
 */
class simpleTweetsBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_tweets_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $moduleSettings = \Drupal::config('simple_tweets.settings');

    $form['#attached']['library'][] = 'simple_tweets/simple_tweets_js_library';

    $form['#attached']['drupalSettings']['simple_tweets']['id'] =
        $moduleSettings->get('simple_tweets_id');
    $form['#attached']['drupalSettings']['simple_tweets']['maximum'] =
        $moduleSettings->get('simple_tweets_max');
    $form['#attached']['drupalSettings']['simple_tweets']['hyperlink'] =
        $moduleSettings->get('simple_tweets_hyperlink');
    $form['#attached']['drupalSettings']['simple_tweets']['user'] =
        $moduleSettings->get('simple_tweets_user');
    $form['#attached']['drupalSettings']['simple_tweets']['interact'] =
        $moduleSettings->get('simple_tweets_interact');
    $form['#attached']['drupalSettings']['simple_tweets']['wind'] =
        $moduleSettings->get('simple_tweets_wind');
    $form['#attached']['drupalSettings']['simple_tweets']['img'] =
        $moduleSettings->get('simple_tweets_img');
    $form['#attached']['drupalSettings']['simple_tweets']['lang'] =
        $moduleSettings->get('simple_tweets_lang');
    $form['#attached']['drupalSettings']['simple_tweets']['retweet'] =
        $moduleSettings->get('simple_tweets_retweet');
    $form['#attached']['drupalSettings']['simple_tweets']['post_time'] =
        $moduleSettings->get('simple_tweets_time');

    return $form;
  }

  /**
   * {@inheritdoc}
   * This method should be implemented, because FormBase is abstract class.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
