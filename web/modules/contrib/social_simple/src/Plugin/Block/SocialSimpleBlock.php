<?php

namespace Drupal\social_simple\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\TitleResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\social_simple\SocialSimpleGenerator;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'SocialSimpleBlock' block.
 *
 * @Block(
 *  id = "social_simple_block",
 *  admin_label = @Translation("Social simple block"),
 * )
 */
class SocialSimpleBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Controller\TitleResolver definition.
   *
   * @var \Drupal\Core\Controller\TitleResolver
   */
  protected $titleResolver;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The Social Simple Generator.
   *
   * @var \Drupal\social_simple\SocialSimpleGenerator
   */
  protected $socialSimpleGenerator;

  /**
   * Constructs a new SocialSimpleBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Controller\TitleResolver $title_resolver
   *   The title resolver service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   * @param \Drupal\social_simple\SocialSimpleGenerator $social_simple_generator
   *   The social simple generator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TitleResolver $title_resolver, RequestStack $request_stack, CurrentRouteMatch $current_route_match, SocialSimpleGenerator $social_simple_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->titleResolver = $title_resolver;
    $this->requestStack = $request_stack;
    $this->currentRouteMatch = $current_route_match;
    $this->socialSimpleGenerator = $social_simple_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('title_resolver'),
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('social_simple.generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'social_networks' => [],
      'social_share_title' => $this->t('Share on'),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['social_share_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Social share title'),
      '#description' => $this->t('Set the title to use before the social links displayed'),
      '#default_value' => $this->configuration['social_share_title'],
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => 1,
    ];

    $options = $this->socialSimpleGenerator->getNetworks();

    $form['social_networks'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Social networks'),
      '#description' => $this->t('Select the social network share link to display'),
      '#options' => $options,
      '#default_value' => $this->configuration['social_networks'],
      '#weight' => 2,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['social_networks'] = $form_state->getValue('social_networks');
    $this->configuration['social_share_title'] = $form_state->getValue('social_share_title');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $title = $this->configuration['social_share_title'];
    $networks = array_filter($this->configuration['social_networks']);
    if (empty($networks)) {
      return $build;
    }

    /* @TODO Find a generic way to fetch from the route the entity its belongs.
     * If the entity is an instance of ContentEntityInterface.
     */
    $entity = NULL;
    if ($node = $this->currentRouteMatch->getParameter('node')) {
      $entity = $node;
    }
    elseif ($taxonomy_term = $this->currentRouteMatch->getParameter('taxonomy_term')) {
      $entity = $taxonomy_term;
    }
    $build = $this->socialSimpleGenerator->buildSocialLinks($networks, $title, $entity);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    /** @var \Drupal\node\NodeInterface $node */
    if ($node = $this->currentRouteMatch->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), $node->getCacheTags());
    }
    /** @var \Drupal\taxonomy\TermInterface $taxonomy_term */
    elseif ($taxonomy_term = $this->currentRouteMatch->getParameter('taxonomy_term')) {
      return Cache::mergeTags(parent::getCacheTags(), $taxonomy_term->getCacheTags());
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.path']);
  }

}
