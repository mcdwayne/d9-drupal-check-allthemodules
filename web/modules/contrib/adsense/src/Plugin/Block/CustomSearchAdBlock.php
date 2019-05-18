<?php

namespace Drupal\adsense\Plugin\Block;

use Drupal\adsense\AdBlockInterface;
use Drupal\adsense\Plugin\AdsenseAd\CustomSearchAd;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides an AdSense Custom Search ad block.
 *
 * @Block(
 *   id = "adsense_cse_ad_block",
 *   admin_label = @Translation("Custom search"),
 *   category = @Translation("Adsense")
 * )
 */
class CustomSearchAdBlock extends BlockBase implements AdBlockInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ad_slot' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createAd() {
    return new CustomSearchAd([
      'slot' => $this->configuration['ad_slot'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->createAd()->display();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Hide block title by default.
    $form['label_display']['#default_value'] = FALSE;

    $link = Link::fromTextAndUrl($this->t('Google AdSense account page'), Url::fromUri('https://www.google.com/adsense/app#main/myads-springboard'))->toString();

    $form['ad_slot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ad ID'),
      '#default_value' => $this->configuration['ad_slot'],
      '#description' => $this->t('This is the Ad ID from your @adsensepage, such as 1234567890.',
        ['@adsensepage' => $link]),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['ad_slot'] = $form_state->getValue('ad_slot');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    /*return Cache::PERMANENT;*/
    return 0;
  }

}
