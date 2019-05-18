<?php

namespace Drupal\revue\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Subscription' block to let users subscribe to Revue newsletters.
 *
 * @Block(
 *   id = "revue_subscription",
 *   admin_label = @Translation("Revue Subscription block"),
 *   category = @Translation("Revue")
 * )
 */
class Subscription extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    if (empty($config['api_key'])) {
      return [
        '#markup' => $this->t('Revue API key missing! Please configure the Revue API key in the block configuration.'),
      ];
    }

    return $this->formBuilder->getForm('Drupal\revue\Form\Subscription', $config);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Revue API key'),
      '#description' => $this->t('You can find the API key at the bottom of the integrations page in your Revue account.'),
      '#required' => TRUE,
      '#default_value' => isset($config['api_key']) ? $config['api_key'] : '',
    ];

    $form['optional_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Optional fields to show in the subscription form'),
      '#options' => [
        'first_name' => $this->t('First name'),
        'last_name' => $this->t('Last name'),
      ],
      '#default_value' => isset($config['optional_fields']) ? $config['optional_fields'] : [
        'first_name',
        'last_name',
      ],
    ];

    $form['old_issues_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add a link to old issues of the newsletter in the success message after subscribing'),
      '#default_value' => isset($config['old_issues_link']) ? $config['old_issues_link'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['api_key'] = $form_state->getValue('api_key');
    $this->configuration['optional_fields'] = $form_state->getValue('optional_fields');
    $this->configuration['old_issues_link'] = $form_state->getValue('old_issues_link');
  }

}
