<?php

/**
 * @file
 * Contains \Drupal\token_conditions\Plugin\Condition\TokenMatcher.
 *
 * @todo Is there a way to request a generic ContentEntity context?
 */

namespace Drupal\token_conditions\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;

/**
 * Provides a 'Token Matcher' condition.
 *
 * @Condition(
 *   id = "token_matcher",
 *   label = @Translation("Token Matcher"),
 * )
 */
class TokenMatcher extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates a new TokenMatcher instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\Token $token
   *   Token manager service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ModuleHandlerInterface $module_handler, Token $token, EntityManagerInterface $entity_manager, RequestStack $request_stack, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->token = $token;
    $this->entityManager = $entity_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('module_handler'),
      $container->get('token'),
      $container->get('entity.manager'),
      $container->get('request_stack'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form_id = $form_state->getBuildInfo()['form_id'];

    // @todo How to add multiple conditions on block form?
    // @see
    $form['token_match'] = array(
      '#title' => $this->t('Token String'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['token_match'],
      '#description' => $this->t('Enter token or string with multiple tokens'),
    );
    $form['check_empty'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check if value is empty'),
      '#description' => t('@todo Add description'),
      '#default_value' => $this->configuration['check_empty'],
    );
    $invisible_state = array(
      'invisible' => array(
        ':input[name="visibility[token_matcher][check_empty]"]' => array('checked' => TRUE),
      )
    );
    $form['value_match'] = array(
      '#title' => $this->t('Value String'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['value_match'],
      '#description' => $this->t('Enter string to check against. This can also contain tokens'),
      '#states' => $invisible_state,
    );
    $form['use_regex'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use regex match'),
      '#description' => t(''),
      '#default_value' => $this->configuration['use_regex'],
      '#states' => $invisible_state,
    );
    if ($this->moduleHandler->moduleExists('token')) {
      $form['token_tree'] = array(
        '#theme' => 'token_tree',
        '#token_types' => $this->getContentTokenTypes(),
        '#dialog' => FALSE,
        '#weight' => 100,
      );
    }
    return parent::buildConfigurationForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('@token_match = @value_match',
      array(
        '@token_match' => $this->configuration['token_match'],
        '@value_match' => $this->configuration['value_match'],
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $token_data = $this->getTokenData();
    $token_replaced = $this->token->replace($this->configuration['token_match'], $token_data);
    $value_replace = $this->token->replace($this->configuration['value_match'], $token_data);
    if ($this->configuration['check_empty']) {
      return empty($token_replaced);
    }
    if ($this->configuration['use_regex']) {
      return (boolean) preg_match($value_replace, $token_replaced);
    }

    return $token_replaced == $value_replace;
  }

  private function getTokenType(ContentEntityType $entity_type) {
    return $entity_type->get('token type') ? $entity_type->get('token type') : $entity_type->id();
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $user_values = $form_state->getValues();
    foreach ($user_values as $key => $value) {
      if ($key != 'negate') {
        $this->configuration[$key] = $value;
      }
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'token_match' => '',
      'value_match' => '',
      'check_empty' => 0,
      'use_regex' => 0,
    ) + parent::defaultConfiguration();
  }

  /**
   * Get an array of token data.
   *
   * @return array
   *   keys - entity types
   *   values - entities
   */
  protected function getTokenData() {
    $token_data = [];
    $token_types = $this->getContentTokenTypes();
    foreach ($token_types as $entity_type => $token_type) {
      if ($entity = $this->getPseudoContextValue($entity_type)) {
        $token_data[$token_type] = $entity;
      }
    }
    return $token_data;
  }

  protected function getContentTokenTypes() {
    $token_types = [];
    $allEntities = $this->entityManager->getDefinitions();
    foreach ($allEntities as $entity_type => $entity_type_info) {
      if ($entity_type_info instanceof ContentEntityType) {
        $token_types[$entity_type] = $this->getTokenType($entity_type_info);
      }
    }
    return $token_types;
  }

  /**
   * Get Entity by type from Request.
   *
   * This is a stop gap to until there is better way to get the values from
   * context.
   *
   * @param $entity_type
   *
   * @return mixed
   */
  protected function getPseudoContextValue($entity_type) {
    $attributes = $this->requestStack->getCurrentRequest()->attributes;
    if ($attributes->has($entity_type)) {
      $entity_attribute = $attributes->get($entity_type);
      if ($entity_attribute instanceof ContentEntityInterface) {
        return $entity_attribute;
      }
    }
    return FALSE;
  }
}
