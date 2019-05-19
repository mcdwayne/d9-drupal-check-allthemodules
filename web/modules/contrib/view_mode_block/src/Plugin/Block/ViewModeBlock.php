<?php
/**
 * Provides a view mode block.
 *
 * @Block(
 *   id = "view_mode_block",
 *   admin_label = @Translation("View Mode Block"),
 * )
 */

namespace Drupal\view_mode_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\Core\Entity\EntityDisplayRepository;

class ViewModeBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $view_mode = $config['view_mode'] ?: '';

    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $node_to_display = node_view($node, $view_mode);
      return [
        'node' => $node_to_display,
      ];
    }

    return [
      '#markup' => $this->t('This would display node as @view_mode!', ['@view_mode' => $view_mode]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    // TODO: Change to a select list of available view modes for content.
    $form['view_mode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View Mode'),
      '#description' => $this->t('Machine Name of the view mode to use.'),
      '#default_value' => isset($config['view_mode']) ? $config['view_mode'] : '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('view_mode', $form_state->getValue('view_mode'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // This block must be cached per URL: every entity has its own canonical url
    // and its own fields.
    return ['url'];
  }
}
