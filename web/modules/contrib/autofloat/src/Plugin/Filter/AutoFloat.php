<?php

namespace Drupal\autofloat\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Url;

/**
 * Provides a filter that wraps images in a selector with odd/even classes.
 *
 * @Filter(
 *   id = "filter_autofloat",
 *   module = "autofloat",
 *   title = @Translation("Float images alternately left and right"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   weight = 20
 * )
 */
class AutoFloat extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $url = Url::fromRoute('autofloat.settings');
    $config_link = \Drupal::l(t('AutoFloat Filter Settings'), $url);

    $form['notice'] = [
      '#markup' => t('@config_link are shared by all the text formats where it is enabled.', [
        '@config_link' => $config_link,
      ]),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(_autofloat_filter($text));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Images get an odd/even classes to make them float alternately left and right.');
  }

}
