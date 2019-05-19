<?php

// @todo: rename to DefaultIFrameContentProvider

namespace Drupal\visualn_iframe\IFrameContentProvider;

/**
 * Class ContentProvider.
 */
class ContentProvider implements ContentProviderInterface {


  /**
   * Holds arrays of iframe content providers, keyed by priority.
   *
   * @var array
   */
  protected $iframe_content_providers = array();


  /**
   * Holds the array of iframe content providers sorted by priority.
   *
   * Set to NULL if the array needs to be re-calculated.
   *
   * @var array|null
   */
  protected $sortedContentProviders;


  /**
   * Constructs a new ContentProvider object.
   */
  public function __construct() {

  }

  
  //public function addIFrameContentProvider(ThemeNegotiatorInterface $iframe_content_provider, $priority) {
  public function addIFrameContentProvider(ContentProviderInterface $iframe_content_provider, $priority) {
    $this->iframe_content_providers[$priority][] = $iframe_content_provider;
    // Force the providers to be re-sorted.
    $this->sortedContentProviders = NULL;
  }

  /**
   * Returns the sorted array of iframe content providers.
   *
   * @return array|\Drupal\visualn_iframe\IFrameContentProvider\ContentProviderInterface[]
   *   An array of iframe content provider objects.
   */
  protected function getSortedContentProviders() {
    if (!isset($this->sortedContentProviders)) {
      // Sort the content providers according to priority.
      krsort($this->iframe_content_providers);
      // Merge nested content providers from $this->iframe_content_providers into
      // $this->sortedContentProviders.
      $this->sortedContentProviders = array();
      foreach ($this->iframe_content_providers as $builders) {
        $this->sortedContentProviders = array_merge($this->sortedContentProviders, $builders);
      }
    }
    return $this->sortedContentProviders;
  }

  public function applies($handler_key, $data, $settings) {
    return TRUE;
  }

  public function provideContent($handler_key, $data, $settings) {
    /*
    foreach ($this->getSortedNegotiators() as $negotiator) {
      if ($negotiator->applies($route_match)) {
        $theme = $negotiator->determineActiveTheme($route_match);
        if ($theme !== NULL && $this->themeAccess->checkAccess($theme)) {
          return $theme;
        }
      }
    }
    */
    foreach ($this->getSortedContentProviders() as $iframe_content_provider) {
      if ($iframe_content_provider->applies($handler_key, $data, $settings)) {
        return $iframe_content_provider->provideContent($handler_key, $data, $settings);
      }
    }
    // @todo: This should be set in DefaultContentProvider
    return ['#markup' => 'not found'];
  }

}
