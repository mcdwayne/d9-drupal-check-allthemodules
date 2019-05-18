<?php

namespace Drupal\groupmediaplus\Plugin\views\argument_default;

use Drupal\Core\Form\FormStateInterface;
use Drupal\groupmediaplus\GroupMediaPlus;
use Drupal\token\Token;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to extract a group ID from a path's context.
 *
 * @ViewsArgumentDefault(
 *   id = "groupmediaplus_groups_from_node_path",
 *   title = @Translation("Group ID from node path")
 * )
 */
class GroupIdsFromNodePath extends ArgumentDefaultPluginBase {

  /** @var \Drupal\token\TokenInterface */
  protected $tokenService;

  /**
   * GroupIdsFromPathContext constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\token\Token $tokenService
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $tokenService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tokenService = $tokenService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token')
    );
  }


  /**
   * @inheritDoc
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['path'] = ['default' => '[current-page:query:original_path]'];
    $options['path'] = ['default' => TRUE];
    $options['entity_type'] = ['default' => []];
    return $options;
  }

  /**
   * @inheritDoc
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The path to get group context from'),
      '#description' => $this->t('With leading slash. You can use tokens. For media browser this is "[current-page:query:original_path]".'),
      '#default_value' => $this->options['path'],
    ];
    $form['extract_group'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Extract group'),
      '#description' => $this->t('Extracts the group directly from a path like /group/23/node/create/page.'),
      '#default_value' => $this->options['extract_group'],
    ];
    $form['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('The entity type'),
      '#description' => $this->t('Extracts the entity\'s groups from a path like /node/42/edit.'),
      '#options' => GroupMediaPlus::getAllGroupContentEntityTypeOptions(),
      '#default_value' => $this->options['entity_types'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $path = $this->tokenService->replace($this->options['path'], [], ['clear' => TRUE]);
    $groupIds = GroupMediaPlus::getGroupIdsFromEntityPath($path, $this->options['extract_group'], $this->options['entity_types']);
    $return = implode('+', $groupIds);
    return $return;
  }

}
