<?php

namespace Drupal\follow_unfollow\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\follow_unfollow\BlockVisibilityAccessCheck;

/**
 * Provides a 'Follow Unfollow' Block.
 *
 * @Block(
 *   id = "follow_unfollow_block",
 *   admin_label = @Translation("Follow Unfollow Block"),
 * )
 */
class FollowUnfollowBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The instantiated account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The block visibility.
   *
   * @var \Drupal\follow_unfollow\BlockVisibilityAccessCheck
   */
  protected $blockVisibility;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxy $account, RequestStack $requestStack, FormBuilderInterface $form_builder, BlockVisibilityAccessCheck $blockVisibility) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->requestStack = $requestStack;
    $this->formBuilder = $form_builder;
    $this->blockVisibility = $blockVisibility;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('form_builder'),
      $container->get('access_check_block.visiblity')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the current argument.
    $path = $this->requestStack->getCurrentRequest()->getpathInfo();

    // Checking block visibility based on configuration.
    if (isset($path) && !empty($path)) {
      $access = $this->blockVisibility->checkAccess($path);
    }

    // User is authenticate.
    $user = $this->account->isAuthenticated();
    // Show block if user authenticate and access true based on setting form.
    if ($user && $access) {
      $form = $this->formBuilder->getForm('Drupal\follow_unfollow\Form\FollowUnfollowForm');
      return $form;
    }
  }

}
