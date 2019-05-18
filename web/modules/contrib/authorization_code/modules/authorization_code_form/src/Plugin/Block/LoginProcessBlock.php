<?php

namespace Drupal\authorization_code_form\Plugin\Block;

use Drupal\authorization_code\Entity\LoginProcess;
use Drupal\authorization_code_form\Form\LoginProcessForm;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block containing the LoginProcess login form.
 *
 * @Block(
 *   id = "login_process_block",
 *   admin_label = @Translation("Login process"),
 *   category = @Translation("Forms")
 * )
 */
class LoginProcessBlock extends BlockBase implements ContainerFactoryPluginInterface {

  const FORBIDDEN_ROUTES = ['user.login', 'user.logout'];

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  private $formBuilder;

  /**
   * The route matcher service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The login process entity storage service.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  private $loginProcessStorage;

  /**
   * LoginProcessBlock constructor.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $login_process_storage
   *   The login process entity storage service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, RouteMatchInterface $route_match, FormBuilderInterface $form_builder, ConfigEntityStorageInterface $login_process_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->loginProcessStorage = $login_process_storage;
    $this->routeMatch = $route_match;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('form_builder'),
      $container->get('entity_type.manager')->getStorage('login_process')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $route_name = $this->routeMatch->getRouteName();

    if ($account->isAnonymous() && !in_array($route_name, static::FORBIDDEN_ROUTES)) {
      return AccessResult::allowed()
        ->addCacheContexts(['route.name', 'user.roles:anonymous']);
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   */
  public function build() {
    $formObj = new LoginProcessForm(LoginProcess::load($this->loginProcessId()));
    $form = $this->formBuilder->getForm($formObj);
    return [
      'login_process_form' => $form,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return NestedArray::mergeDeep(parent::defaultConfiguration(), [
      'config' => [$this->loginProcessId()],
      'module' => ['authorization_code'],
    ]);
  }

  /**
   * The login process entity id.
   *
   * @return string|null
   *   The login process entity id.
   */
  private function loginProcessId() {
    return NestedArray::getValue($this->configuration, ['login_process']);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['login_process'] = [
      '#type' => 'select',
      '#title' => $this->t('Login Process'),
      '#options' => $this->loginProcessOptions(),
      '#required' => TRUE,
      '#default_value' => $this->loginProcessId(),
      '#description' => $this->t('The login process to use with this login block.'),
    ];

    return $form;
  }

  /**
   * Login process form options.
   *
   * @return array
   *   The login process entity form options
   */
  private function loginProcessOptions(): array {
    $options = [];
    foreach ($this->loginProcessStorage->loadMultiple() as $id => $login_process) {
      $options[$id] = $login_process->label();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['login_process'] = $form_state->getValue('login_process');
  }

}
