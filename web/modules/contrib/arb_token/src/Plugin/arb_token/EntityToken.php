<?php

namespace Drupal\arb_token\Plugin\arb_token;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Provides tokens for entities.
 *
 * @ArbitraryToken(
 *   id = "entity",
 *   label = @Translation("Entity"),
 * )
 */
class EntityToken extends ArbitraryTokenBase {

  /**
   * The selected entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $selectedEntity = NULL;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'type' => NULL,
      'id' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $build['type'] = [
      '#type' => 'select',
      '#title' => t('Entity type'),
      '#required' => TRUE,
      '#options' => $this->getEntityTypes(),
      '#default_value' => !empty($this->configuration['type']) ? $this->configuration['type'] : NULL,
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $form['#wrapper_id'],
      ],
    ];

    $type = $form_state->getValue(['configuration', 'type']) ?: $this->configuration['type'];
    if ($type) {
      $build['id'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => $type,
        '#title' => t('Entity'),
        '#required' => TRUE,
        '#default_value' => $this->configuration['id'],
      ];

      if ($type == $this->configuration['type']) {
        $build['id']['#default_value'] = $this->getSelectedEntity();
      }
      else {
        $this->configuration['id'] = NULL;
        $build['id']['#default_value'] = NULL;
      }
    }

    return $build;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function tokenInfo() {
    $entity = $this->getSelectedEntity();

    return [
      'name' => $entity->label(),
      'description' => t('@type: @id.', [
        '@type' => $this->configuration['type'],
        '@id' => $this->configuration['id'],
      ]),
      'type' => $this->configuration['type'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tokens($tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];

    $entity_tokens = $this->getTokenService()->findWithPrefix($tokens, $this->token->id());
    if ($entity_tokens) {
      $replacements += $this->getTokenService()->generate(
        $this->configuration['type'],
        $entity_tokens,
        [$this->configuration['type'] => $this->getSelectedEntity()],
        $options,
        $bubbleable_metadata
      );
      $bubbleable_metadata->addCacheTags(['arb_token:' . $this->token->id()]);
      $bubbleable_metadata->addCacheTags([$this->configuration['type'] . ':' . $this->configuration['id']]);
    }

    return $replacements;

  }

  /**
   * Gets available entity types.
   *
   * @return array
   *   Entity type labels, keyed by entity type ID.
   */
  protected function getEntityTypes() {
    $types = array_map(function ($type) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $type */
      return $type->getLabel();
    }, $this->getEntityTypeManager()->getDefinitions());
    return $types;
  }

  /**
   * Gets the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  protected function getEntityTypeManager() {
    return \Drupal::entityTypeManager();
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    $type = $this->getPluginDefinition()['label'];

    if ($this->configuration['type']) {
      $type .= ' ' . $this->t('(@type: @id)', [
        '@type' => $this->configuration['type'],
        '@id' => $this->configuration['id'],
      ]);
    }

    return $type;
  }

  /**
   * Gets the entity selected by this plugin.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The selected entity.
   */
  protected function getSelectedEntity() {
    if (!$this->configuration['type']) {
      return NULL;
    }

    return $this->getEntityStorage($this->configuration['type'])
      ->load($this->configuration['id']);
  }

  /**
   * Get the entity storage for the specified type.
   *
   * @param string $entityType
   *   The entity type.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  protected function getEntityStorage($entityType) {
    return \Drupal::entityTypeManager()->getStorage($entityType);
  }

  /**
   * Gets the token service.
   *
   * @return \Drupal\Core\Utility\Token
   *   The token service.
   */
  protected function getTokenService() {
    return \Drupal::token();
  }

}
