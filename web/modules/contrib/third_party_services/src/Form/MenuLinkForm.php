<?php

namespace Drupal\third_party_services\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\Form\MenuLinkDefaultForm;

/**
 * Customization of the link to configuration form.
 */
class MenuLinkForm extends MenuLinkDefaultForm {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\third_party_services\Controller\ConfigurationController::form()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $options = $this->menuLink->getOptions() + [
      'use_ajax' => FALSE,
      // Do not forget to update all values if changing hardcoded ones.
      'dialog_options' => [],
    ];

    // Prevent notices when configuration of nested item is not fully set.
    $options['dialog_options'] += [
      // @see ConfigurationController::form()
      'title' => $this->t('Services'),
      // @see third-party-services--placeholder.html.twig
      'width' => 600,
      // @see ConfigurationController::form()
      'addCancelButton' => FALSE,
    ];

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu link title'),
      '#weight' => 0,
      '#required' => TRUE,
      '#default_value' => $this->menuLink->getTitle(),
    ];

    $form['options'] = [
      '#tree' => TRUE,
    ];

    $form['options']['use_ajax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use AJAX'),
      '#description' => $this->t('Indicates in what type the configuration form will be used: on a new page or in modal window of current page.'),
      '#default_value' => $options['use_ajax'],
    ];

    $form['options']['dialog_options'] = [
      '#open' => TRUE,
      '#type' => 'details',
      '#title' => $this->t('Settings of modal window'),
      '#states' => [
        'visible' => [
          ':input[name*="use_ajax"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['options']['dialog_options']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of modal window'),
      '#default_value' => $options['dialog_options']['title'],
    ];

    $form['options']['dialog_options']['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Initial width'),
      '#default_value' => $options['dialog_options']['width'],
    ];

    $form['options']['dialog_options']['addCancelButton'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add cancel button'),
      '#default_value' => $options['dialog_options']['addCancelButton'],
      '#description' => $this->t('If checked, the button with "@text" will be added right after the button, which submits the form.', [
        // @see ConfigurationController::form()
        '@text' => $this->t('Cancel'),
      ]),
    ];

    unset($form['path'], $form['info'], $form['expanded']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(array &$form, FormStateInterface $form_state): array {
    $definition = parent::extractFormValues($form, $form_state);
    $definition['title'] = $form_state->getValue('title');
    $definition['options'] = $form_state->getValue('options', []);

    return $definition;
  }

}
