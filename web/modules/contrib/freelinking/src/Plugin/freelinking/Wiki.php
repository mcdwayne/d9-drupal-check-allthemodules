<?php

namespace Drupal\freelinking\Plugin\freelinking;

use Drupal\Core\Url;
use Drupal\freelinking\Plugin\FreelinkingPluginBase;

/**
 * Freelinking wiki plugin implements wikipedia amongst other plugins.
 *
 * @Freelinking(
 *   id = "wiki",
 *   title = @Translation("Wiki"),
 *   weight = 0,
 *   hidden = false,
 *   settings = {  }
 * )
 */
class Wiki extends FreelinkingPluginBase {

  /**
   * A map of wiki abbreviations to full names.
   *
   * @var array
   */
  protected $wikiMap = [
    'wp' => 'wikipedia',
    'wq' => 'wikiquote',
    'wt' => 'wiktionary',
    'wn' => 'wikinews',
    'ws' => 'wikisource',
    'wb' => 'wikibooks',
  ];

  /**
   * {@inheritdoc}
   */
  public function getTip() {
    // @todo Decide to split into multiple plugins for Wikipedia/Wikiquote here.
    return $this->t('Click to view a wiki page.');
  }

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    return '/w((ikipedia|p)|(ikiquote|q)|(iktionary|t)|(ikinews|n)|(ikisource|s)|(ikibooks|b))?$/A';
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    $wikiname = $this->getWikiFromIndicator($target['indicator']);
    $langcode = $target['language']->getId();
    $dest = str_replace(' ', '_', $target['dest']);
    $wikiurl = 'https://' . $langcode . '.' . $wikiname . '.org/wiki/' . $dest;

    return [
      '#type' => 'link',
      '#title' => isset($target['text']) ? $target['text'] : ucwords($wikiname),
      '#url' => Url::fromUri($wikiurl, ['language' => $target['language'], 'absolute' => TRUE]),
      '#attributes' => [
        'title' => $this->getTip(),
      ],
    ];
  }

  /**
   * Get the wiki name from the indicator string.
   *
   * @param string $indicator
   *   The indicator string used.
   *
   * @return string
   *   The full wiki name.
   */
  protected function getWikiFromIndicator($indicator) {
    $ret = '';
    if (preg_match($this->getIndicator(), $indicator, $matches)) {
      if (strlen($matches[0]) === 2) {
        $ret = $this->wikiMap[$matches[0]];
      }
      else {
        // The indicator is the full wiki name.
        $ret = $matches[0];
      }
    }
    return $ret;
  }

}