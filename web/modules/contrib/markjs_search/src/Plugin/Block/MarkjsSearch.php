<?php

namespace Drupal\markjs_search\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\markjs_search\MarkjsProfileManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define the markjs search block plugin.
 *
 * @Block(
 *   id = "markjs_search",
 *   admin_label = @Translation("MarkJS Search"),
 *   category = @Translation("MarkJS")
 * )
 */
class MarkjsSearch extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\markjs_search\MarkjsProfileManagerInterface
   */
  protected $markjsProfileManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('markjs_search.profile_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MarkjsProfileManagerInterface $markjs_profile_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->markjsProfileManager = $markjs_profile_manager;
  }

  /**
   * {@inheritdoc}
   */
  function defaultConfiguration() {
    return [
      'profile' => NULL,
      'selector' => 'body',
      'placeholder' => 'Search Keyword'
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Profile'),
      '#description' => $this->t('Select a MarkJS profile.'),
      '#options' => $this->markjsProfileManager->getProfileOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
      '#default_value' => $config['profile'],
    ];
    $form['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Context'),
      '#description' => $this->t('Input the context selector on which to search 
        within for the keyword.'),
      '#required' => TRUE,
      '#size' => 15,
      '#default_value' => $config['selector'],
    ];
    $form['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Placeholder'),
      '#description' => $this->t('Input the placeholder text for the keyword 
        input.'),
      '#size' => 25,
      '#default_value' => $config['placeholder'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('profile', $form_state->getValue('profile'));
    $this->setConfigurationValue('selector', $form_state->getValue('selector'));
    $this->setConfigurationValue('placeholder', $form_state->getValue('placeholder'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['search_box'] = [
      '#theme' => 'markjs_search_box',
      '#attached' => [
        'library' => [
          'markjs_search/markjs.search'
        ]
      ]
    ];
    $settings = [];
    $settings['configs'] = $this->getConfiguration();

    /** @var \Drupal\markjs_search\Entity\MarkjsProfileEntity $profile */
    if ($profile = $this->loadMarkjsProfile()) {
      $settings['options'] = $profile->getFormattedOptions();

      // Update the search box cache tags to account for changes to the
      // MarkJS profile configuration.
      $build['search_box']['#cache'] = [
        'tags' => Cache::mergeTags($this->getCacheTags(), $profile->getCacheTags()),
        'context' => Cache::mergeContexts($this->getCacheContexts(), $profile->getCacheContexts()),
        'max-age' => Cache::mergeMaxAges($this->getCacheMaxAge(), $profile->getCacheMaxAge())
      ];
    }
    $build['search_box']['#attached']['drupalSettings']['markjs_search'] = $settings;

    return $build;
  }

  /**
   * Load MarkJS profile.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The MarkJS profile instance; otherwise NULL if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadMarkjsProfile() {
    $config = $this->getConfiguration();

    if (!isset($config['profile'])) {
      return NULL;
    }

    return $this->markjsProfileManager
      ->loadProfile($config['profile']);
  }
}
