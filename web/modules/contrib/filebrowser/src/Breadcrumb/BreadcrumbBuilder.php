<?php

namespace Drupal\filebrowser\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\filebrowser\Services\Common;
use Drupal\filebrowser\Services\FilebrowserStorage;

class BreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * @var FilebrowserStorage
   */
  protected $storage;

  /**
   * @var Common
   */
  protected $common;

  /**
   * @var \Drupal\node\NodeInterface
   *
   */
  protected $node;

  public function __construct(FilebrowserStorage $storage, Common $common) {
    $this->storage = $storage;
    $this->common = $common;
  }

  /**
   * @var \Drupal\Node\NodeInterface $node
   * @inheritdoc
   */
  public function applies(RouteMatchInterface $route_match) {
    $this->node = $this->common->getNodeFromPath();
    return isset($this->node) && $this->node->bundle() == 'dir_listing';
  }

  /**
   * @inheritdoc
   */
  public function build(RouteMatchInterface $route_match) {
    /** @var \Drupal\node\NodeInterface $node */
    $breadcrumb = new Breadcrumb();
    $this->node =  $this->common->getNodeFromPath();
    $title = $this->node->getTitle();
    $fid = \Drupal::request()->query->get('fid');
    if (isset($fid)) {
      $content = $this->storage->loadAllRecordsFromRoot($this->node->id());
    }
    else {
      $content = !empty($content) ? $content : ['path' => '/'];
    }
    $links = $this->buildLinks($title, $content, $fid);
    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['url', 'url.query_args']);
    return $breadcrumb;
  }

  /**
   * Creates the filebrowser breadcrumb links
   * @param string $title
   * @param int $fid id of current folder being viewed
   * @param array $content array from the DB containing all paths (folders) keyed by $fid.
   * @return array
   */
  private function buildLinks($title, $content, $fid) {
    $links[0] = Link::createFromRoute($this->t('Home'), '<front>');
    $links[1] = Link::createFromRoute($title, 'entity.node.canonical',['node' => $this->node->id()]);
    $trail = isset($fid) && isset($content[$fid]) ? ltrim($content[$fid]->path, "/") : null;
    $folders_raw = !is_null($trail) ? explode('/', $trail) : null;

    if (!empty($folders_raw)) {
      // process the folder to set the fid
      $folders = $this->processTrail($folders_raw, $content);
      $count = count($folders) + 1;
      for ($i = 2; $i <= $count; $i++) {
        if ($i < $count) {
          $links[$i] = Link::fromTextAndUrl($folders[$i-2]['title'], Url::fromUserInput('/node'));
          $links[$i] = Link::fromTextAndUrl($folders[$i-2]['title'],
            Url::fromRoute('entity.node.canonical',
              ['node' => $this->node->id()], ['query' => ['fid' => $folders[$i-2]['fid']]]));
        }
        else {
          $links[$i] = Link::createFromRoute($folders[$i-2]['title'], '<none>');
        }
      }
    }
    else {
      // there are no subdirectories so [1] is the last item
      // route the link to <none>
      $links[1] = Link::createFromRoute($title, '<none>');
    }
    return $links;
  }

  protected function processTrail($folders, $content) {
    $result = [];
    $count = count($folders);
    for ($i = 0; $i < $count; $i++) {
      $folder_path = '/' . implode('/', array_slice($folders, 0, $i+1));
      $result[$i]['title'] = $folders[$i];
      $result[$i]['folder_path'] = $folder_path;
      // Loop trough $content and search for the own and parent fid
      foreach ($content as $fid => $row) {
        if ($row->path == $folder_path) {
          $result[$i]['fid'] = $row->fid;
        }
      }
    }
    return $result;
  }

}
