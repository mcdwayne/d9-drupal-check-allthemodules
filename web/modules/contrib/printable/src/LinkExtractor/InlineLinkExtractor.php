<?php

namespace Drupal\printable\LinkExtractor;

use wa72\htmlpagedom\HtmlPageCrawler;
use Drupal\Core\Url;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Path\AliasManager;

/**
 * Link extractor.
 */
class InlineLinkExtractor implements LinkExtractorInterface {

  /**
   * The DomCrawler object.
   *
   * @var \Wa72\HtmlPageDom\HtmlPageCrawler
   */
  protected $crawler;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * The alias manager service.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasMnager;

  /**
   * Constructs a new InlineLinkExtractor object.
   */
  public function __construct(HtmlPageCrawler $crawler, MetadataBubblingUrlGenerator $urlGenerator, AliasManager $aliasMnager) {
    $this->crawler = $crawler;
    $this->urlGenerator = $urlGenerator;
    $this->aliasMnager = $aliasMnager;
  }

  /**
   * {@inheritdoc}
   */
  public function extract($string) {
    $this->crawler->addContent($string);

    $this->crawler->filter('a')->each(function (HtmlPageCrawler $anchor, $uri) {
      $href = $anchor->attr('href');
      if ($href) {
        $url = $this->urlFromHref($href);
        $anchor->append(' (' . $url->toString() . ')');
      }
    });

    return (string) $this->crawler;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAttribute($content, $attr) {
    $this->crawler->addContent($content);
    $this->crawler->filter('a')->each(function (HtmlPageCrawler $anchor, $uri) {
      $anchor->removeAttribute('href');
    });
    return (string) $this->crawler;
  }

  /**
   * {@inheritdoc}
   */
  public function listAttribute($content) {
    $this->crawler->addContent($content);
    $this->links = [];
    $this->crawler->filter('a')->each(function (HtmlPageCrawler $anchor, $uri) {
      global $base_url;

      $href = $anchor->attr('href');
      try {
        $this->links[] = $base_url . $this->aliasMnager->getAliasByPath($href);
      }
      catch (\Exception $e) {
        $this->links[] = $this->urlFromHref($href)->toString();
      }
    });
    $this->crawler->remove();
    return implode(',', $this->links);
  }

  /**
   * Generate a URL object given a URL from the href attribute.
   *
   * Tries external URLs first, if that fails it will attempt
   * generation from a relative URL.
   *
   * @param string $href
   *   The URL from the href attribute.
   *
   * @return \Drupal\Core\Url
   *   The created URL object.
   */
  private function urlFromHref($href) {
    try {
      $url = Url::fromUri($href, ['absolute' => TRUE]);
    }
    catch (\InvalidArgumentException $e) {
      $url = Url::fromUserInput($href, ['absolute' => TRUE]);
    }

    return $url;
  }

}
