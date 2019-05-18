<?php

namespace Drupal\elf\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * Adds a CSS class to all external and mailto links.
 *
 * @Filter(
 *   id = "filter_elf",
 *   title = @Translation("Add an icon to external and mailto links"),
 *   description = @Translation("External and mailto links in content links have an icon."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "elf_nofollow" = false
 *   }
 * )
 */
class FilterElf extends FilterBase {

  /**
   * Store internal domains.
   * @var string
   */
  protected $pattern = '';

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['elf_nofollow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add rel="nofollow" to all external links'),
      '#default_value' => $this->settings['elf_nofollow'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $elf_settings = \Drupal::config('elf.settings');
    $result = new FilterProcessResult($text);

    $dom = Html::load($text);
    $links = $dom->getElementsByTagName('a');
    foreach ($links as $a) {
      $href = $a->getAttribute('href');
      if (!$href) {
        continue;
      }
      // This is a mailto link.
      if (strpos($href, 'mailto:') === 0) {
        $a->setAttribute('class', ($a->getAttribute('class') ? $a->getAttribute('class') . ' elf-mailto elf-icon' : 'elf-mailto elf-icon'));
        continue;
      }
      // This is external links.
      if ($this->elf_url_external($href)) {
        // The link is external. Add external class.
        $a->setAttribute('class', ($a->getAttribute('class') ? $a->getAttribute('class') . ' elf-external elf-icon' : 'elf-external elf-icon'));
        if ($a->getElementsByTagName('img')->length > 0) {
          $a->setAttribute('class', ($a->getAttribute('class') ? $a->getAttribute('class') . ' elf-img' : 'elf-img'));
        }

        // Add nofollow.
        $no_follow = $this->getConfiguration()['settings']['elf_nofollow'];
        if ($no_follow) {
          $rel = array_filter(explode(' ', $a->getAttribute('rel')));
          if (!in_array('nofollow', $rel)) {
            $rel[] = 'nofollow';
            $a->setAttribute('rel', implode(' ', $rel));
          }
        }

        // Add redirect.
        if ($elf_settings->get('elf_redirect')) {
          $url = Url::fromRoute('elf.redirect', [], [
            'query' => [
              'url' => $a->getAttribute('href'),
            ],
          ]);
          $a->setAttribute('href', $url->toString());
        }
      }
    }

    $result->setProcessedText(Html::serialize($dom))
      ->addAttachments(['library' => ['elf/elf_css']]);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('External and mailto links in content links have an icon.');
  }

  /**
   * @param string $url
   *
   * @return bool
   */
  public function elf_url_external($url) {
    global $base_url;

    if (empty($this->pattern)) {
      $domains = [];
      $elf_domains = \Drupal::config('elf.settings')->get('elf_domains');
      $elf_domains = is_array($elf_domains) ? $elf_domains : [];
      foreach (array_merge($elf_domains, [$base_url]) as $domain) {
        $domains[] = preg_quote($domain, '#');
      }
      $this->pattern = '#^(' . str_replace('\*', '.*', implode('|', $domains)) . ')#';
    }

    return preg_match($this->pattern, $url) ? FALSE : UrlHelper::isExternal($url);
  }

}
