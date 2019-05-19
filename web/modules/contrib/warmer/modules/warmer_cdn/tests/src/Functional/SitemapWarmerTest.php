<?php

namespace Drupal\Tests\warmer_cdn\Functional;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Path\AliasStorage;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\warmer\Plugin\WarmerPluginManager;

/**
 * Tests for the sitemap warmer.
 *
 * @group warmer
 */
final class SitemapWarmerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'file', 'path', 'warmer', 'warmer_cdn'];

  /**
   * The file entity containing the sitemap.
   *
   * @var \Drupal\file\FileInterface
   */
  private $sitemap;

  /**
   * The nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  private $nodes = [];

  /**
   * The admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $adminUser;

  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->adminUser);
    $content_type = $this->randomMachineName();
    $this->drupalCreateContentType(['type' => $content_type]);
    $this->nodes = array_reduce(range(1, 5), function ($carry, $index) use ($content_type) {
      $node = $this->drupalCreateNode([
        'title' => $this->getRandomGenerator()->sentences(5),
        'type' => $content_type,
        'status' => $index === 2
          ? NodeInterface::NOT_PUBLISHED
          : NodeInterface::PUBLISHED,
      ]);
      // Create alias.
      $alias_storage = \Drupal::service('path.alias_storage');
      assert($alias_storage instanceof AliasStorage);
      $alias = '/' . $this->getRandomGenerator()->word(8);
      $alias_storage->save('/node/' . $node->id(), $alias);
      $carry[$alias] = $node;
      return $carry;
    }, []);
    $this->sitemap = $this->generateSitemaps($this->nodes);
  }

  /**
   * Asserts the enqueue form functionality.
   */
  public function testBuildIds() {
    // Enable the warming of articles.
    $this->config('warmer.settings')->set('warmers', [
      'sitemap' => [
        'id' => 'sitemap',
        'frequency' => 1,
        'batchSize' => 1,
        'sitemaps' => [$this->sitemap->createFileUrl()],
        'headers' => [],
        'minPriority' => 0.7,
      ],
    ])->save();
    // Use the plugin instance to build the IDs.
    $manager = \Drupal::service('plugin.manager.warmer');
    assert($manager instanceof WarmerPluginManager);
    list($warmer) = $manager->getWarmers(['sitemap']);
    $urls = [];
    $ids = [NULL];
    while ($ids = $warmer->buildIdsBatch(end($ids))) {
      $urls = array_merge($urls, $ids);
    }
    // Assert that the expected URLs are parsed and filtered from the sitemap.
    $this->assertCount(2, $urls);
    // Assert that only one URL is warmed because the other one is 403.
    $this->assertSame(1, $warmer->warmMultiple($urls));
  }

  /**
   * Generates the sitemaps.
   *
   * This will generate a sitemap like:
   * @code
   * <?xml version="1.0" encoding="UTF-8"?>
   * <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
   *   <url><loc>/lorem</loc><changefreq>monthly</changefreq><priority>0.8</priority></url>
   *   <url><loc>/ipsum</loc><changefreq>monthly</changefreq><priority>0.8</priority></url>
   *   <url><loc>/dolor</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
   *   <url><changefreq>monthly</changefreq><priority>0.3</priority></url>
   *   <url><loc>/sit</loc></url>
   * </urlset>
   * @endcode
   *
   * Where only 2 of the items are valid and have priority higher than 0.7.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   The nodes to add to the sitemap.
   *
   * @return \Drupal\file\Entity\File
   *   The file entity behind the sitemap.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  private function generateSitemaps(array $nodes) {
    $xml = new \DOMDocument('1.0', 'utf-8');
    $urlset = $xml->createElement('urlset');
    $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $urlset->setAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
    $index = 1;
    foreach ($nodes as $alias => $node) {
      assert($node instanceof NodeInterface);
      $url = $xml->createElement('url');
      if ($index !== 4) {
        $loc = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
        $url->appendChild($xml->createElement('loc', $loc));
      }
      $url->appendChild($xml->createElement('changefreq', 'monthly'));
      if ($index !== 5) {
        $priority = $index === 3 ? '0.2' : '0.8';
        $url->appendChild($xml->createElement('priority', $priority));
      }
      $urlset->appendChild($url);
      $index++;
    }
    $xml->appendChild($urlset);
    $fs = \Drupal::service('file_system');
    assert($fs instanceof FileSystem);
    mkdir('public://sitemaps');
    $filename = 'public://sitemaps/0.xml';
    file_put_contents($fs->realpath($filename), $xml->saveXML());
    $file = File::create(['uri' => $filename]);
    $file->setOwnerId($this->adminUser->id());
    $file->setPermanent();
    $file->save();

    $xml = new \DOMDocument('1.0', 'utf-8');
    $sitemapindex = $xml->createElement('sitemapindex');
    $sitemap = $xml->createElement('sitemap');
    $sitemap->appendChild($xml->createElement('loc', $file->createFileUrl(FALSE)));
    $sitemapindex->appendChild($sitemap);
    $xml->appendChild($sitemapindex);
    $filename = 'public://sitemaps/sitemap.xml';
    file_put_contents($fs->realpath($filename), $xml->saveXML());
    $file = File::create(['uri' => $filename]);
    $file->setOwnerId($this->adminUser->id());
    $file->setPermanent();
    $file->save();

    return $file;
  }

}
