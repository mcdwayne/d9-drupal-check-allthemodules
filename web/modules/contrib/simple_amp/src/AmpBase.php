<?php

namespace Drupal\simple_amp;

use Lullabot\AMP\AMP;
use Drupal\Core\Url;

/**
 * Parse content and detect if there is any JS should be added
 */
class AmpBase {

  protected $entity;
  protected $view_mode;
  protected $content;
  protected $html;

  protected $component_manager;
  protected $metadata_manager;
  protected $config;
  protected $ga;

  // Default and absolutely must scripts.
  protected $scripts = [
    '<script async src="https://cdn.ampproject.org/v0.js"></script>',
  ];

  public function __construct() {
    $this->component_manager = \Drupal::service('plugin.manager.simple_amp_component');
    $this->metadata_manager = \Drupal::service('plugin.manager.simple_amp_metadata');
    $this->config = \Drupal::config('simple_amp.settings');
    $this->ga = \Drupal::config('google_analytics.settings')->get('account');
  }

  public function setEntity($entity) {
    $this->entity = $entity;
    return $this;
  }

  public function getEntity() {
    return $this->entity;
  }

  public function getScripts() {
    return join("\n", $this->scripts);
  }

  public function getCanonicalUrl() {
    $options = ['absolute' => TRUE];
    $url = Url::fromRoute('entity.node.canonical', ['node' => $this->getEntity()->id()], $options);
    return $url->toString();
  }

  public function generateAmpURL() {
    $path = Url::fromRoute('simple_amp.amp', ['entity' => $this->getEntity()->id()]);
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    return rtrim($base_url, '/') . $path->toString();
  }

  public function getContent() {
    return $this->content;
  }

  public function getViewMode() {
    return $this->config->get($this->getEntity()->bundle() . '_view_mode');
  }

  public function isComponentEnabled($id) {
    return (bool) $this->config->get('component_' . $id . '_enable');
  }

  public function isAmpEnabled() {
    return (bool) $this->config->get($this->getEntity()->bundle() . '_enable');
  }

  public function getGoogleAnalytics() {
    return $this->ga;
  }

  public function getMetadata() {
    $entity = $this->getEntity();
    $manager = $this->metadata_manager;
    $plugins = $manager->getDefinitions();
    foreach ($plugins as $id => $plugin) {
      $plugin = $manager->createInstance($plugin['id']);
      $entity_types = $plugin->getEntityTypes($entity);
      if (in_array($entity->bundle(), $entity_types)) {
        $metadata = $plugin->getMetadata($entity);
        return json_encode($metadata);
      }
    }
  }

  public function parse() {
    $amp = new AMP();
    $amp->loadHtml($this->renderEntityViewMode());
    $this->content = $amp->convertToAmpHtml();
    if ($scripts = $amp->getComponentJs()) {
      foreach ($scripts as $id => $src) {
        $this->scripts[] = '<script  async custom-element="' . $id . '" src="' . $src . '"></script>';
      }
    }
    $this->detect();
    return $this;
  }

  public function enableIndividualAmp() {
    db_insert('simple_amp_disabled')
      ->fields([
        'nid' => $this->getEntity()->id(),
      ])
      ->execute();
    return $this;
  }

  public function disableIndividualAmp() {
    db_delete('simple_amp_disabled')
      ->condition('nid', $this->getEntity()->id())
      ->execute();
    return $this;
  }

  public function individualAmpDisabled() {
    $disabled = db_select('simple_amp_disabled', 'a')
      ->fields('a', ['nid'])
      ->condition('a.nid', $this->getEntity()->id())
      ->execute()
      ->fetchField();
    return !empty($disabled);
  }

  protected function renderEntityViewMode() {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($this->getEntity()->getEntityTypeId());
    $node = $view_builder->view($this->getEntity(), $this->getViewMode(), $langcode);
    $html = \Drupal::service('renderer')->render($node);
    return $this->html = $html->__toString();
  }

  protected function detect() {
    $manager = $this->component_manager;
    $plugins = $manager->getDefinitions();

    // Find component.
    foreach ($plugins as $plugin) {
      $id = $plugin['id'];
      $plugin = $manager->createInstance($id);
      if ($this->isComponentEnabled($id)) {
        if ($element = $plugin->getElement()) {
          $this->scripts[] = $element;
        }
      }

      if ($regexp = $plugin->getRegexp()) {
        $component = [];
        if (!is_array($regexp)) {
          $component['regexp'][] = $regexp;
        }
        else {
          $component['regexp'] = $regexp;
        }

        // Try all regular expressions.
        foreach ($component['regexp'] as $delta => $regexp) {
          if (preg_match($regexp, $this->html, $matches) || preg_match($regexp, $this->content, $matches)) {
            if ($element = $plugin->getElement()) {
              $this->scripts[] = $element;
            }
          }
        }
      }

    }

  }

}
