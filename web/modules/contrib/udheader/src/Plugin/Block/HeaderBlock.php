<?php

namespace Drupal\udheader\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Ubuntu Drupal Header' block.
 *
 * @Block(
 *   id = "udheader_block",
 *   admin_label = @Translation("Ubuntu Drupal Header"),
 *   category = @Translation("Ubuntu Drupal"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"), multiple = false)
 *   }
 * )
 */
class HeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    $entities = [];
    if ($node instanceof \Drupal\node\NodeInterface) {
      $entities = \Drupal::entityTypeManager()
        ->getStorage('udheader')
        ->loadByProperties(['node' => $node->getType()]);
    }

    if (empty($entities)) {
      $entities = \Drupal::entityTypeManager()
        ->getStorage('udheader')
        ->loadByProperties(['node' => 'udheader_default']);
    }

    if (empty($entities)) {
      return null;
    }

    $data = array_shift($entities);

    $image['left'] = base_path() . drupal_get_path('module', 'udheader') . "/images/left/" . $data->left_image->value;
    $image['center'] = base_path() . drupal_get_path('module', 'udheader') . "/images/center/" . $data->center_image->value;
    $image['right'] = base_path() . drupal_get_path('module', 'udheader') . "/images/right/" . $data->right_image->value;

    if ($data->center_image->value === 'center-tall.png') {
      $height = '163px';
    } else {
      $height =  '87px';
    }

    $center_text = $data->center_text->view();
    $right_text = $data->right_text->view();

    $center_text['#label_display'] = $right_text['#label_display'] = 'hidden';

    return [
      '#theme' => 'udheader_block',
      '#attached' => [
        'library' => ['udheader/udheader'],
      ],
      '#height' => $height,
      '#left_image' => $image['left'],
      '#center_image' => $image['center'],
      '#right_image' => $image['right'],
      '#center_text' => $center_text,
      '#right_text' => $right_text,
    ];
  }
}
