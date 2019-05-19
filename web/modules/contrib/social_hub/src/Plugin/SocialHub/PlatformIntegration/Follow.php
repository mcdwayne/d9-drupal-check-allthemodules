<?php

namespace Drupal\social_hub\Plugin\SocialHub\PlatformIntegration;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\social_hub\PlatformIntegrationPluginBase;

/**
 * Plugin implementation of the social_platform.
 *
 * @PlatformIntegration(
 *   id = "follow",
 *   label = @Translation("Follow"),
 *   description = @Translation("Allow platforms to be rendered as 'Follow' links.")
 * )
 *
 * @internal
 *   Plugin classes are internal.
 *
 * @phpcs:disable Drupal.Commenting.InlineComment.InvalidEndChar
 * @phpcs:disable Drupal.Commenting.PostStatementComment.Found
 */
class Follow extends PlatformIntegrationPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['platform_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Platform URL'),
      '#description' => $this->t('The platform full URL, shall include the protocol and no trailing slash. E.g. https://example.com'), // NOSONAR
      '#required' => TRUE,
      '#default_value' => $this->configuration['platform_url'],
    ];

    $form['follow_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Follow path'),
      '#description' => $this->t('The follow path in the platform, with no preceding slash. E.g. user/my_id.'), // NOSONAR
      '#required' => TRUE,
      '#default_value' => $this->configuration['follow_path'],
      '#field_suffix' => [
        '#theme' => 'token_tree_link',
        '#text' => $this->t('Tokens'),
        '#token_types' => 'all',
        '#theme_wrappers' => ['container'],
      ],
    ];

    $form += $this->buildLinkSectionForm();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $context = []) {
    $context = $this->prepareContext($context);
    /** @var \Drupal\social_hub\PlatformInterface $platform */
    $platform = $context['platform'] ?? NULL;
    $this->metadata = new BubbleableMetadata();
    $options = ['absolute' => TRUE, 'external' => TRUE];
    $path = $this->token->replace($this->configuration['follow_path'], $context, [], $this->metadata);
    $uri = sprintf('%s/%s', $this->configuration['platform_url'], $path);
    $build = [
      '#theme' => $this->getPluginId(),
      '#url' => Url::fromUri($uri, $options)->toString(),
      '#attributes' => [
        'id' => Html::getUniqueId($platform->id() . '_' . $this->getPluginId()),
        'class' => [
          Html::getClass($platform->id()) . '_' . Html::getClass($this->getPluginId()),
        ],
        'target' => '_blank',
      ],
    ];

    if (!empty($this->configuration['link']['text'])) {
      $build['#title'] = $this->token->replace($this->configuration['link']['text'], $context, [], $this->metadata);
    }

    if (!empty($this->configuration['link']['title'])) {
      $build['#attributes']['title'] = $this->token->replace($this->configuration['link']['title'], $context, [], $this->metadata);
    }

    if (!empty(trim($this->configuration['link']['classes']))) {
      $classes = explode(' ', trim($this->configuration['link']['classes']));
      $build['#attributes']['class'] = $classes;
    }

    $this->metadata->applyTo($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default = [
      'platform_url' => NULL,
      'follow_path' => NULL,
    ];

    return $default + parent::defaultConfiguration();
  }

}
