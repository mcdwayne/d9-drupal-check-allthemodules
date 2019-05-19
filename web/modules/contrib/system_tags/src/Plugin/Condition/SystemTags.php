<?php

namespace Drupal\system_tags\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\system_tags\Entity\SystemTag;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SystemTags.
 *
 * @package Drupal\system_tags\Plugin\Condition
 *
 * @Condition(
 *   id = "system_tags",
 *   label = @Translation("System Tags")
 * )
 */
class SystemTags extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * SystemTags constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['system_tags' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $options = [];
    /** @var \Drupal\system_tags\Entity\SystemTagInterface $tag */
    foreach (SystemTag::loadMultiple() as $tag) {
      $options[$tag->id()] = $tag->label();
    }

    $form['system_tags'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('System Tags'),
      '#options' => $options,
      '#default_value' => $this->configuration['system_tags'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['system_tags'] = $form_state->getValue('system_tags');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $tags = array_filter($this->configuration['system_tags']);

    if (count($this->configuration['system_tags']) > 1) {
      $last = array_pop($tags);
      $tags = implode(', ', $tags);

      $output = $this->t('The System Tags are @tags or @last', [
        '@tags' => $tags,
        '@last' => $last,
      ]);
    }
    else {
      $output = $this->t('The system tag is @tag', ['@tag' => reset($tags)]);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $tags = array_filter($this->configuration['system_tags']);
    if (empty($tags) && !$this->isNegated()) {
      return TRUE;
    }

    // Find entity.
    $entity = NULL;
    foreach ($this->routeMatch->getParameters() as $param) {
      if ($param instanceof FieldableEntityInterface) {
        $entity = $param;
      }
    }
    if (!$entity) {
      // Could not determine an entity in the current request.
      return FALSE;
    }

    // Get System Tags.
    $referencePresent = FALSE;
    $systemTags = [];
    foreach ($entity->getFields(FALSE) as $field) {
      if ($field->getFieldDefinition()->getSetting('target_type') === 'system_tag') {
        $referencePresent = TRUE;

        foreach ($field->getValue() as $tag) {
          $systemTags[] = $tag['target_id'];
        }
      }
    }
    if (!$referencePresent) {
      // Could not find a reference to any System Tag.
      return FALSE;
    }

    // Match with configuration.
    $intersection = array_intersect($systemTags, $tags);

    return !empty($intersection);
  }

}
