<?php

namespace Drupal\welcome_username\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Welcome Username Login/Logout' Block.
 *
 * @Block(
 *   id = "welcome_username",
 *   admin_label = @Translation("Welcome Username Login/Logout"),
 *   category = @Translation("Welcome Username Login/Logout"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 */
class WelcomeUserNameBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'welcome_username_welcome_string' => $this->t('Welcome'),
      'welcome_username_logout_string' => $this->t('Logout'),
    ];
  }

  /**
   * The form_builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new WelcomeUserNameBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form_builder service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The current_user service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, AccountProxy $currentUser, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->currentUser = $currentUser;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['welcome_username_welcome_string'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Welcome String'),
      '#default_value' => $this->configuration['welcome_username_welcome_string'],
    ];

    $form['welcome_username_logout_string'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Logout String'),
      '#default_value' => $this->configuration['welcome_username_logout_string'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['welcome_username_welcome_string'] = $values['welcome_username_welcome_string'];
    $this->configuration['welcome_username_logout_string'] = $values['welcome_username_logout_string'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // For anonymous users load a simple login form.
    if ($this->currentUser->isAnonymous()) {
      $form = $this->formBuilder->getForm("Drupal\user\Form\UserLoginForm");

      // Placeholders.
      $form['name']['#attributes']['placeholder'] = $form['name']['#description'];
      unset($form['name']['#description']);

      $form['pass']['#attributes']['placeholder'] = $form['pass']['#description'];
      unset($form['pass']['#description']);

      $content['login_form'] = $this->renderer->render($form);
    }

    // For authenticated users show the 'Welcome username' block with a
    // link to the user account and a logout link.
    else {
      $config = $this->getConfiguration();
      // Load user object.
      $user_name = $this->currentUser->getDisplayName();

      // Load string from variable table if set.
      $welcome_string = $config['welcome_username_welcome_string'];
      $logout_string = $config['welcome_username_logout_string'];

      // Create a link to the user profile page.
      $content['profile_link'] = Link::fromTextAndUrl(t($welcome_string) . " " . $user_name, Url::fromRoute('user.page'));

      // Create a logout link.
      $content['logout_link'] = Link::fromTextAndUrl(t($logout_string), Url::fromRoute('user.logout'));
    }

    return [
      '#theme' => 'welcome_username_login',
      '#content' => $content,
    ];
  }

}
