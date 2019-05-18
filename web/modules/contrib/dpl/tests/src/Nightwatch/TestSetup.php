<?php

namespace Drupal\dpl;

use Drupal\dpl\Entity\DecoupledPreviewLink;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\RandomGeneratorTrait;
use Drupal\TestSite\TestSetupInterface;

class TestSetup implements TestSetupInterface {

  use BlockCreationTrait;
  use RandomGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function setup() {
    \Drupal::service('module_installer')->install(
      ['node', 'block', 'dpl']
    );
    NodeType::create([
      'type' => 'article',
      'name' => 'Article'
    ])->save();

    DecoupledPreviewLink::create([
      'id' => 'staging',
      'label' => 'staging',
      'tab_label' => 'Visit staging',
      'open_external_label' => 'Open staging',
      'preview_url' => 'http://staging.example/[node:nid]',
    ])->save();
    DecoupledPreviewLink::create([
      'id' => 'live',
      'label' => 'live',
      'tab_label' => 'Visit live',
      'open_external_label' => 'Open live',
      'preview_url' => 'http://live.example/[node:nid]',
    ])->save();

    $this->placeBlock('local_tasks_block');
  }

}
