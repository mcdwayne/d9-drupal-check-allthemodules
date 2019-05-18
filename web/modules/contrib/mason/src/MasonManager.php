<?php

namespace Drupal\mason;

use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\NestedArray;
use Drupal\blazy\BlazyManagerBase;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\mason\Entity\Mason;

/**
 * Implements MasonManagerInterface.
 *
 * @todo lazyload[x], granular image styles, decent skins, stamps.
 */
class MasonManager extends BlazyManagerBase implements BlazyManagerInterface, MasonManagerInterface {

  /**
   * Returns defined skins as registered via hook_mason_skins_info().
   */
  public function getSkins() {
    $skins = &drupal_static(__METHOD__, NULL);
    if (!isset($skins)) {
      $skins = $this->buildSkins('mason', '\Drupal\mason\MasonSkin');
    }
    return $skins;
  }

  /**
   * Returns array of needed assets suitable for #attached for the given mason.
   */
  public function attach($attach = []) {
    $attach += ['skin' => FALSE];
    $attach['blazy'] = TRUE;

    $load = parent::attach($attach);

    $load['library'][] = 'mason/mason.load';

    $skins = $this->getSkins();
    if ($skin = $attach['skin']) {
      $provider = isset($skins[$skin]['provider']) ? $skins[$skin]['provider'] : 'mason';
      $load['library'][] = 'mason/' . $provider . '.' . $skin;
    }

    $load['drupalSettings']['mason'] = Mason::load('default')->getOptions();

    $this->moduleHandler->alter('mason_attach', $load, $attach);
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build = []) {
    foreach (['items', 'optionset', 'settings'] as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    $mason = [
      '#theme'      => 'mason',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderMason']],
    ];

    $settings          = $build['settings'];
    $suffixes[]        = count($build['items']);
    $suffixes[]        = count(array_filter($settings));
    $suffixes[]        = $settings['cache'];
    $cache['tags']     = Cache::buildTags('mason:' . $settings['id'], $suffixes, '.');
    $cache['contexts'] = ['languages'];
    $cache['max-age']  = $settings['cache'];
    $cache['keys']     = isset($settings['cache_metadata']['keys']) ? $settings['cache_metadata']['keys'] : [$settings['id']];
    $mason['#cache']   = $cache;

    return $mason;
  }

  /**
   * {@inheritdoc}
   */
  public function preRenderMason($element) {
    $build = $element['#build'];
    unset($element['#build']);

    if (empty($build['items'])) {
      return [];
    }

    // Build mason elements.
    $defaults    = Mason::htmlSettings();
    $settings    = $build['settings'] ? array_merge($defaults, $build['settings']) : $defaults;
    $attachments = $this->attach($settings);
    $optionset   = $build['optionset'] ?: Mason::load($settings['optionset']);

    $element['#optionset'] = $optionset;
    $element['#settings']  = $settings;
    $element['#attached']  = empty($build['attached']) ? $attachments : NestedArray::mergeDeep($build['attached'], $attachments);
    $element['#items']     = $build['items'];
    $element['#json']      = $optionset->getJson();

    return $element;
  }

}
