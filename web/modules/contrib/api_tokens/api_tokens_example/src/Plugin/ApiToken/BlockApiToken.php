<?php

namespace Drupal\api_tokens_example\Plugin\ApiToken;

use Drupal\block\Entity\Block;
use Drupal\api_tokens\ApiTokenBase;

/**
 * Provides a Block API token.
 *
 * Token examples:
 * - [api:block["bartik_breadcrumbs"]/]
 *
 * @ApiToken(
 *   id = "block",
 *   label = @Translation("Block"),
 *   description = @Translation("Renders a block.")
 * )
 */
class BlockApiToken extends ApiTokenBase {

  /**
   * {@inheritdoc}
   */
  public function validate(array $params) {
    // For [api:block["bartik_breadcrumbs"]/] token:
    //$params = [
    //  'id' => 'bartik_breadcrumbs',
    //];

    // Check that "id" is a string.
    if (!is_string($params['id'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Build callback.
   *
   * @param string $id
   *   The block ID.
   *
   * return array
   *   A renderable array.
   *
   * @see \Drupal\api_tokens\ApiTokenPluginInterface::build();
   */
  public function build($id) {
    $build = [];
    $block = Block::load($id);
    if ($block) {
      $build = \Drupal::entityManager()->getViewBuilder('block')->view($block);
    }

    return $build;
  }

}
