<?php

namespace Drupal\sticky_sharrre_bar\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\block\Entity\Block;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'StickySharrreBarBlock' block.
 *
 * @Block(
 *  id = "sticky_sharrre_bar_block",
 *  admin_label = @Translation("Sticky sharrre bar"),
 * )
 */
class StickySharrreBarBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access sticky_sharrre_bar');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'providers' => [
        'googlePlus' => 'googlePlus',
        'facebook' => 'facebook',
        'twitter' => 'twitter',
        'linkedin' => 'linkedin',
      ],
      'use_module_css' => 1,
      'use_custom_css_selector' => '',
      'use_google_analytics_tracking' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = \Drupal::config('sticky_sharrre_bar.settings');
    $form['block_sticky_sharrre_bar'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sticky Sharrre Bar settings'),
    ];
    $form['block_sticky_sharrre_bar']['providers'] = [
      '#type' => 'checkboxes',
      '#options' => $config->get('providers_list'),
      '#title' => $this->t('Main share providers'),
      '#description' => $this->t('Choose which providers you want to show in this block instance.'),
      '#default_value' => $this->configuration['providers'],
    ];
    // To add the field only if "google_analytics' is enabled.
    if (\Drupal::moduleHandler()->moduleExists('google_analytics')) {
      $form['block_sticky_sharrre_bar']['use_google_analytics_tracking'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allows tracking social interaction with "Google Analytics".'),
        '#description' => $this->t('For more details see the :url.',
          [
            ':url' => Link::fromTextAndUrl($this->t('"Sharrre" documentation'),
              Url::fromUri('http://sharrre.com/track-social.html',
                ['attributes' => ['target' => '_blank']]))
              ->toString(),
          ]
        ),
        '#default_value' => $this->configuration['use_google_analytics_tracking'],
      ];
    }
    $form['block_sticky_sharrre_bar']['use_module_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use the css of the module.'),
      '#description' => $this->t('Disable if you want override the styles in your theme.'),
      '#default_value' => $this->configuration['use_module_css'],
    ];
    $form['block_sticky_sharrre_bar']['use_custom_css_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom CSS selector'),
      '#size' => 60,
      '#description' => $this->t('In some cases, module can not find the right region selector in your theme. You can manually set it. Examples: "#navbar", ".header". Is empty by default.'),
      '#default_value' => $this->configuration['use_custom_css_selector'],

    ];

    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValue('block_sticky_sharrre_bar');

    $this->configuration['providers'] = $values['providers'];
    $this->configuration['use_module_css'] = $values['use_module_css'];
    $this->configuration['use_custom_css_selector'] = $values['use_custom_css_selector'];
    $this->configuration['providers'] = $values['providers'];

    if (isset($values['use_google_analytics_tracking'])) {
      $this->configuration['use_google_analytics_tracking'] = $values['use_google_analytics_tracking'];
    }
  }

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   *
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $enabled_providers = [];

    foreach ($this->configuration['providers'] as $key => $provider) {
      if ($provider != '0') {
        $enabled_providers[$key] = $provider;
      }
    }

    if (!empty($enabled_providers)) {
      $request = \Drupal::request();
      $route_match = \Drupal::routeMatch();
      $title = \Drupal::service('title_resolver')
        ->getTitle($request, $route_match->getRouteObject());

      if ($title == '') {
        $title = \Drupal::config('system.site')->get('name');
      }

      // FIXME: need load block info and get id of region.
      $instance = Block::load('stickysharrrebar');
      $region = $instance->get('region');
      $custom_css_selector = $this->configuration['use_custom_css_selector'];

      $js_variables = [
        'providers' => $enabled_providers,
        'useGoogleAnalyticsTracking' => $this->configuration['use_google_analytics_tracking'],
        'blockRegion' => ($custom_css_selector != '') ? $custom_css_selector : $region,
        // TODO: fix region.
        'isCustomSelector' => ($custom_css_selector != '') ? TRUE : FALSE,
      ];

      $build['content'] = [
        '#theme' => 'sticky_sharrre_bar_block',
        '#providers' => $enabled_providers,
        '#url' => \Drupal::request()->getUri(),
        '#title' => Html::escape($title),
        '#attached' => [
          'drupalSettings' => ['stickySharrreBar' => $js_variables],
          'library' => [],
        ],
      ];
      // Add and initialise plugins.
      $build['content']['#attached']['library'][] = 'sticky_sharrre_bar/jquery-waypoints';
      $build['content']['#attached']['library'][] = 'sticky_sharrre_bar/sharrre';
      $build['content']['#attached']['library'][] = 'sticky_sharrre_bar/sticky_sharrre_bar_js';
      // Very important place. We should cache the result by URL and Language,
      // otherwise a node title can be the same for all pages.
      $build['content']['#cache'] = [
        'contexts' => ['url', 'languages'],
      ];
      if ($this->configuration['use_module_css'] == 1) {
        $build['content']['#attached']['library'][] = 'sticky_sharrre_bar/sticky_sharrre_bar_css';
      }

    }

    return $build;
  }

}
