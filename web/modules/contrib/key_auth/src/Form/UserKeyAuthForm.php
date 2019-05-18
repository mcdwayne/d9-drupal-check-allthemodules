<?php

namespace Drupal\key_auth\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\key_auth\KeyAuth;
use Drupal\key_auth\KeyAuthInterface;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactory;

/**
 * Class UserKeyAuthForm.
 *
 * Provides a form to manage the user's key for authentication.
 */
class UserKeyAuthForm extends FormBase {

  /**
   * The key authentication service.
   *
   * @var \Drupal\key_auth\KeyAuthInterface
   */
  protected $keyAuth;

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new UserKeyAuthForm.
   *
   * @param \Drupal\key_auth\KeyAuthInterface $key_auth
   *   The key authentication service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   */
  public function __construct(KeyAuthInterface $key_auth, ConfigFactory $config_factory) {
    $this->keyAuth = $key_auth;
    $this->config = $config_factory->get('key_auth.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('key_auth'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_key_auth_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    // Extract the user's key.
    $key = $user->api_key->value;

    // Store the user ID.
    $form['#uid'] = $user->id();

    $form['key'] = [
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Key'),
      ],
      'key' => [
        '#type' => 'item',
        '#markup' => $key ? $key : $this->t('You currently do not have a key'),
      ],
    ];

    $form['auth'] = [
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Authentication options'),
      ],
      '#access' => (bool) $key,
    ];

    // Check if header detection is enabled.
    if (in_array(KeyAuth::DETECTION_METHOD_HEADER, $this->config->get('detection_methods'))) {
      $form['auth']['header'] = [
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'h5',
          '#value' => $this->t('Header'),
        ],
        'instructions' => [
          '#type' => 'item',
          '#markup' => $this->t('Include the following header in your API requests.'),
        ],
        'example' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => $this->config->get('param_name') . ': ' . $key,
        ],
      ];
    }

    // Check if query detection is enabled.
    if (in_array(KeyAuth::DETECTION_METHOD_QUERY, $this->config->get('detection_methods'))) {
      $form['auth']['query'] = [
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'h5',
          '#value' => $this->t('Query'),
        ],
        'instructions' => [
          '#type' => 'item',
          '#markup' => $this->t('Include the following query in the URL of your API requests.'),
        ],
        'example' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => '?' . $this->config->get('param_name') . '=' . $key,
        ],
      ];
    }

    $form['actions'] = [
      'new' => [
        '#type' => 'submit',
        '#value' => $this->t('Generate new key'),
      ],
      'delete' => [
        '#type' => 'submit',
        '#value' => $this->t('Delete current key'),
        '#access' => (bool) $key,
        '#submit' => ['::deleteKey'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Generate a new key.
    User::load($form['#uid'])
      ->set('api_key', $this->keyAuth->generateKey())
      ->save();

    // Alert the user.
    drupal_set_message($this->t('A new key has been generated.'));
  }

  /**
   * Submit handler to delete the key.
   */
  public function deleteKey(array &$form, FormStateInterface $form_state) {
    // Delete the key.
    User::load($form['#uid'])
      ->set('api_key', NULL)
      ->save();

    // Alert the user.
    drupal_set_message($this->t('Your key has been deleted.'));
  }

  /**
   * Access handler for the form.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity being edited.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(UserInterface $user) {
    // Load the current user.
    $current_user = User::load($this->currentUser()->id());

    // Check if the user being edited is not the current user.
    if ($user->id() != $current_user->id()) {
      // Check admin-access.
      $access = AccessResult::allowedIfHasPermission($current_user, 'administer users');
    }
    else {
      $access = AccessResult::allowedIf($this->keyAuth->access($current_user));
    }

    // Add caching.
    $access->addCacheContexts(['user.permissions']);
    $access->addCacheableDependency($user);

    return $access;
  }

}
