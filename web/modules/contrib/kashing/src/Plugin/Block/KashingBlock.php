<?php

namespace Drupal\kashing\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\kashing\Entity\KashingValid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a user details block.
 *
 * @Block(
 *     id = "kashing_block",
 *     admin_label=@Translation("Kashing block"),
 * )
 */
class KashingBlock extends BlockBase implements ContainerFactoryPluginInterface, BlockPluginInterface {

  private $formBuilder;

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $data, $formBuilder) {
    $this->formBuilder = $formBuilder;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Build.
   */
  public function build() {

    $build = [];
    $error = FALSE;
    $error_info = '';
    $page_info = '<div class="kashing-frontend-notice kashing-errors">';

    $kashing_validate = new KashingValid();

    $config = $this->getConfiguration();
    if (isset($config)) {
      $argument = $config['kashing_form_settings'];
    }
    else {
      $argument = NULL;
      $error = TRUE;
      $error_info .= '<li>' . $this->t('Block configuration arguments missing.') . '</li>';
    }

    $api_error = $kashing_validate->validateApiKeys();
    if (is_string($api_error)) {
      $error_info .= $api_error;
      $error = TRUE;
    }

    $amount = $argument['kashing_form_amount'];
    if (!$kashing_validate->validateRequiredField($amount)) {
      $error = TRUE;
      $error_info .= '<li>' . $this->t('No amount provided.') . '</li>';
    }
    elseif (!$kashing_validate->validateAmountField($amount)) {
      $error = TRUE;
      $error_info .= '<li>' . $this->t('Invalid amount provided.') . '</li>';
    }

    $description = $argument['kashing_form_description'];
    if (!$kashing_validate->validateRequiredField($description)) {
      $error = TRUE;
      $error_info .= '<li>' . $this->t('No description provided.') . '</li>';
    }

    if ($error == TRUE) {

      if ($kashing_validate->isAdmin()) {
        $base_url = Url::fromUri('internal:/')->setAbsolute()->toString();
        $page_info .= '<p><strong>' . $this->t('Kashing Payments plugin configuration errors:') . ' </strong></p><ul>';
        $page_info .= $error_info;
        $page_info .= '</ul><a href="' . $base_url . '/admin/config/kashing#edit-delete-mode' . '" target="_blank">' . $this->t('Visit the module settings') . '</a>';
        $page_info .= '</div>';

      }
      else {
        $page_info .= '<p>' . $this->t('Something went wrong. Please contact the site administrator.') . '</p>';
        $page_info .= '</div>';
      }

      $build['#markup'] = $page_info;

    }
    else {
      $build['form'] = $this->formBuilder->getForm('\Drupal\kashing\Form\KashingForm', $argument);
    }

    return $build;
  }

  /**
   * Block form content.
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['kashing_form_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General'),
    ];

    $form['kashing_form_settings']['kashing_form_amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#default_value' => isset($config['kashing_form_settings']['kashing_form_amount']) ? Html::escape($config['kashing_form_settings']['kashing_form_amount']) : '1.00',
      '#description' => $this->t('Enter the form amount that will be processed with the payment system.'),
      '#attributes' => [
        'id' => 'kashing-new-form-amount',
      ],
      '#required' => TRUE,
    ];

    $form['kashing_form_settings']['kashing_form_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => isset($config['kashing_form_settings']['kashing_form_description']) ? Html::escape($config['kashing_form_settings']['kashing_form_description']) : '',
      '#description' => $this->t('The form transaction description.'),
      '#required' => TRUE,
    ];

    $form['kashing_form_settings']['checkboxes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Optional Form Fields'),
    ];

    $form['kashing_form_settings']['checkboxes']['kashing_form_checkboxes'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'address2' => $this->t('Address 2'),
        'email' => $this->t('Email'),
        'phone' => $this->t('Phone'),
      ],
      'address2' => [
        '#default_value' => isset($config['kashing_form_settings']['kashing_form_checkboxes']['address2']) ? $config['kashing_form_settings']['kashing_form_checkboxes']['address2'] : '',
      ],
      'email' => [
        '#default_value' => isset($config['kashing_form_settings']['kashing_form_checkboxes']['email']) ? $config['kashing_form_settings']['kashing_form_checkboxes']['email'] : '',
      ],
      'phone' => [
        '#default_value' => isset($config['kashing_form_settings']['kashing_form_checkboxes']['phone']) ? $config['kashing_form_settings']['kashing_form_checkboxes']['phone'] : '',
      ],
      '#description' => $this->t('Enable selected form fields.'),
    ];

    return $form;
  }

  /**
   * Block content validation.
   */
  public function blockValidate($form, FormStateInterface $form_state) {

    $kashing_validate = new KashingValid();

    // Amount.
    $form_amount = $form_state->getValue(['kashing_form_settings', 'kashing_form_amount']);

    if (!$kashing_validate->validateRequiredField($form_amount)) {
      $form_state->setErrorByName('Amount', $this->t('Please enter a valid amount.'));
    }
    elseif (!$kashing_validate->validateAmountField($form_amount)) {
      $form_state->setErrorByName('Amount', $this->t('Please enter a valid amount.'));
    }
    else {
      // Valid amount.
    }

    // Description.
    $form_description = $form_state->getValue(['kashing_form_settings', 'kashing_form_description']);

    if (!$kashing_validate->validateRequiredField($form_description)) {
      $form_state->setErrorByName('Amount', $this->t('Please enter a description.'));
    }
    else {
      // Valid description.
    }

    parent::blockValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $amount = Html::escape($form_state->getValue(['kashing_form_settings', 'kashing_form_amount']));
    if (is_int($amount)) {
      $amount *= 1.0;
    }
    elseif (is_numeric($amount)) {
      // Ok.
    }
    else {
      $amount = 0.0;
    }
    $this->configuration['kashing_form_settings']['kashing_form_amount'] = $amount;

    $this->configuration['kashing_form_settings']['kashing_form_description'] =
            Html::escape($form_state->getValue([
              'kashing_form_settings',
              'kashing_form_description',
            ]));

    $this->configuration['kashing_form_settings']['kashing_form_checkboxes']['address2'] =
            Html::escape($form_state->getValue([
              'kashing_form_settings',
              'checkboxes',
              'kashing_form_checkboxes',
              'address2',
            ]));

    $this->configuration['kashing_form_settings']['kashing_form_checkboxes']['email'] =
            Html::escape($form_state->getValue([
              'kashing_form_settings',
              'checkboxes',
              'kashing_form_checkboxes',
            ])['email']);

    $this->configuration['kashing_form_settings']['kashing_form_checkboxes']['phone'] =
            Html::escape($form_state->getValue([
              'kashing_form_settings',
              'checkboxes', 'kashing_form_checkboxes',
              'phone',
            ]));

    parent::blockSubmit($form, $form_state);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('entity.manager'),
        $container->get('form_builder')
    );
  }

}
