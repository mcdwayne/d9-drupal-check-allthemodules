<?php

namespace Drupal\third_party_services;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Basic implementation of FieldOptionalRenderInterface.
 */
class FieldOptionalRender implements FieldOptionalRenderInterface {

  use StringTranslationTrait;

  /**
   * Instance of the "MODULE.mediator" service.
   *
   * @var MediatorInterface
   */
  protected $mediator;
  /**
   * Formatter settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(MediatorInterface $mediator) {
    $this->mediator = $mediator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('third_party_services.mediator'));
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function process(array &$element, FieldItemListInterface $items): \Iterator {
    if ($this->isEnabled()) {
      $field_name = $items->getName();

      /* @var \Drupal\Core\Field\FieldItemInterface $item */
      foreach ($items as $delta => $item) {
        if ($this->mediator->placeholder($item->value, $element[$delta], $field_name, $delta)) {
          yield $delta => $element[$delta];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable placeholders for third-party services'),
      '#default_value' => $this->isEnabled(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    if ($this->isEnabled()) {
      $summary[] = $this->t('Third-party services filtration is <b>enabled</b>.');
    }
    else {
      $summary[] = $this->t('Third-party services filtration is <b>disabled</b>.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return isset($this->settings['enable']) ? (bool) $this->settings['enable'] : FALSE;
  }

}
