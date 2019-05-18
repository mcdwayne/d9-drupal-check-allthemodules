<?php

namespace Drupal\kayak_integration\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Kayak widget Block with a flights searcher.
 *
 * @Block(
 *   id = "kayak_integration_flights",
 *   admin_label = @Translation("Kayak Flights Block"),
 *   category = @Translation("Kayak Widget"),
 * )
 */
class KayakFlights extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
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

    $form['kayak_language'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Kayak Language"),
      '#default_value' => $config['kayak_language'],
      '#attributes' => [
        'placeholder' => $this->t("Kayak language"),
      ],
    ];

    $form['kayak_cc'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Kayak Country Code"),
      '#default_value' => $config['kayak_cc'],
      '#attributes' => [
        'placeholder' => $this->t("Kayak Country Code"),
      ],
    ];

    $form['kayak_money'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Kayak Currency"),
      '#default_value' => $config['kayak_money'],
      '#attributes' => [
        'placeholder' => $this->t("Kayak Currency"),
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
    $this->configuration['kayak_language'] = $values['kayak_language'];
    $this->configuration['kayak_cc'] = $values['kayak_cc'];
    $this->configuration['kayak_money'] = $values['kayak_money'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    return [
      '#theme' => 'block__kayakFlights',
      '#lang' => $config['kayak_language'],
      '#councode' => $config['kayak_cc'],
      '#money' => $config['kayak_money'],
      '#attached' => [
        'library' => [
          'kayak_integration/kayak_integration',
        ],
      ],

    ];
  }

}
