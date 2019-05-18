<?php

namespace Drupal\microformats\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\microformats\Utility;

/**
 * Provides a 'Contact Info' block.
 *
 * @Block(
 *   id = "microformats_contactinfo_block",
 *   admin_label = @Translation("Microformats Contact Info Block")
 * )
 */
class ContactInfoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
//    $config = $this->getConfiguration();
//    var_dump($config);
    //$render = array(
      //'#markup' => $this->t('Hello, World!'),
      //'#theme' => 'microformats_contactinfo_block',
    //);
    $render = ['#theme' => 'microformats_contactinfo_block'];
    $render = array_merge($render, Utility::getMicroformatsContactInfoVars());
    $render['#attached']['library'][] = 'microformats/microformats';
    return $render;
  }

}
