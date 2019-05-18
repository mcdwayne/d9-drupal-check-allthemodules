<?php

namespace Drupal\nofollowlist\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * @Filter(
 *   id = "nofollowlist",
 *   title = @Translation("nofollow list"),
 *   description = @Translation("Provides a nofollowlist filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterNoFollowList extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $list = preg_split('/\s+/', $this->settings['nofollowlist_hosts']);
    $html_dom = Html::load($text);
    $links = $html_dom->getElementsByTagName('a');
    foreach ($links as $link) {
      $url = parse_url($link->getAttribute('href'));
      // Handle whitelist option.
      if ($this->settings['nofollowlist_option'] == 'white') {
        // If there is a host present and it is not in the list of allowed hosts
        // we add rel="nofollow".
        if (isset($url['host']) && !(in_array($url['host'], $list))) {
          $link->setAttribute('rel', 'nofollow');
        }
      }
      // Handle blacklist option.
      elseif ($this->settings['nofollowlist_option'] == 'black') {
        // If there is a host present and it is in the list of disallowed hosts we
        // add rel="nofollow".
        if (isset($url['host']) && in_array($url['host'], $list)) {
          $link->setAttribute('rel', 'nofollow');
        }
      }
    }
    $text = Html::serialize($html_dom);
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    // Form to add radio button options to opt between whitelist & blacklist links.
    $form['nofollowlist_option'] = [
      '#type' => 'radios',
      '#title' => $this->t('Hosts list option'),
      '#description' => $this->t('If you choose the whitelist option, be sure to add your own site to the list!'),
      '#options' => [
        'black' => $this->t('Blacklist: Add rel="nofollow" to links leading to the listed hosts.'),
        'white' => $this->t('Whitelist: Add rel="nofollow" to all links <b>except</b> the listed hosts.'),
      ],
      '#default_value' => $this->settings['nofollowlist_option'],
    ];

    // Form to add textarea to enter links.
    $form['nofollowlist_hosts'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Nofollowlist hosts'),
      '#description' => $this->t('Add one host per line. Ex: en.wikipedia.org'),
      '#default_value' => $this->settings['nofollowlist_hosts'],
    ];
    return $form;
  }

}
