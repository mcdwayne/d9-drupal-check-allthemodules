<?php

namespace Drupal\flashpoint\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Request Path' condition.
 *
 * @Condition(
 *   id = "flashpoint_request_path",
 *   label = @Translation("Flashpoint"),
 * )
 */
class FlashpointRequestPath extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * An alias manager to find the alias for the current system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs a RequestPath condition plugin.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   An alias manager to find the alias for the current system path.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher, RequestStack $request_stack, CurrentPathStack $current_path, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->requestStack = $request_stack;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('path.alias_manager'),
      $container->get('path.matcher'),
      $container->get('request_stack'),
      $container->get('path.current'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['user_profile' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['user_profile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User Profile'),
      '#default_value' => $this->configuration['user_profile'],
      '#description' => $this->t("Show on user profile page."),
    ];
    $form = parent::buildConfigurationForm($form, $form_state);
    unset ($form['negate']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['user_profile'] = $form_state->getValue('user_profile');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {

  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Convert path to lowercase. This allows comparison of the same path
    // with different case. Ex: /Page, /page, /PAGE.
    $user_profile = mb_strtolower($this->configuration['user_profile']);
    if (!$user_profile) {
      return TRUE;
    }
    $request = $this->requestStack->getCurrentRequest();
    // Compare the lowercase path alias (if any) and internal path.
    $path = $this->currentPath->getPath($request);
    // Do not trim a trailing slash if that is the complete path.
    $path = $path === '/' ? $path : rtrim($path, '/');

    $path_alias = mb_strtolower($this->aliasManager->getAliasByPath($path));
    $profile_match = $this->pathMatcher->matchPath($path_alias, '/user/*') || (($path != $path_alias) && $this->pathMatcher->matchPath($path, '/user/*'));
    $profile_derivative = $this->pathMatcher->matchPath($path_alias, '/user/*/*') || (($path != $path_alias) && $this->pathMatcher->matchPath($path, '/user/*/*'));

    return $profile_match && !$profile_derivative;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.path';
    return $contexts;
  }

}
