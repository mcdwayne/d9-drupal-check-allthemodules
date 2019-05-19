<?php

namespace Drupal\weather_city\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Weather Forecasts Block.
 *
 * @Block(
 *   id = "weather_city",
 *   admin_label = @Translation("Weather City Block"),
 *   category = @Translation("Weather City Block"),
 * )
 */
class Weather extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('language_manager'),
      $container->get('entity_type.manager')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['weather_div_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Weather ID"),
      '#default_value' => $config['weather_div_id'],
      '#attributes' => [
        'placeholder' => $this->t("Weather ID"),
      ],
    ];

    $form['weather_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Weather Link"),
      '#default_value' => $config['weather_link'],
      '#attributes' => [
        'placeholder' => $this->t("Weather Link"),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['weather_div_id'] = $values['weather_div_id'];
    $this->configuration['weather_link'] = $values['weather_link'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    return [
      '#theme' => 'block__weather',
      '#id' => $config['weather_div_id'],
      '#link' => $config['weather_link'],
      '#attached' => [
        'library' => [
          'weather_city/weather_city',
        ],
      ],
    ];
  }

}
