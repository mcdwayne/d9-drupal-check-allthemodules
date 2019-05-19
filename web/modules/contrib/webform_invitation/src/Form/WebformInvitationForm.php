<?php

namespace Drupal\webform_invitation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformInterface;

/**
 * Enable or disable invitations for the current webform.
 */
class WebformInvitationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_invitation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL) {
    $elements = $webform->getElementsDecodedAndFlattened();
    // Check if current webform has enabled invitations.
    $enabled = isset($elements['webform_invitation_code']);

    $form['webform_invitation'] = [
      '#type' => 'details',
      '#title' => $this->t('Webform Invitation'),
      '#open' => TRUE,
    ];
    $form['webform_invitation']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable invitations for this webform'),
      '#default_value' => $enabled,
      '#description' => $this->t('If checked, invitations will be enabled for this webform.'),
    ];
    $form['webform'] = [
      '#type' => 'value',
      '#value' => $webform,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = $form_state->getValue('webform');
    // Check if user wants to enable invitations.
    $enable = boolval($form_state->getValue('enable'));

    $elements = $webform->getElementsDecodedAndFlattened();
    // Check if current webform has enabled invitations.
    $enabled = isset($elements['webform_invitation_code']);

    // User wants invitations to be enabled.
    if ($enable) {
      // Only if invitations are not enabled.
      if (!$enabled) {
        // Create new code element.
        $element = [
          'webform_invitation_code' => [
            '#type' => 'textfield',
            '#title' => $this->t('Invitation Code')->render(),
            '#default_value' => '[current-page:query:code:clear]',
            '#description' => $this->t('Enter your personal invitation code (only applies if the field is not populated yet).')
              ->render(),
            '#maxlength' => 64,
            '#required' => TRUE,
          ],
        ];
        // Prepend code element before others.
        $elements = $element + $elements;
        // Save changed elements into webform.
        $webform->setElements($elements);
        $webform->save();
      }
      drupal_set_message($this->t('Invitation mode has been activated. You should now <a href="@link">create some invitation codes</a>.', [
        '@link' => Url::fromRoute('entity.webform.invitation_generate', [
          'webform' => $webform->id(),
        ])->toString(),
      ]));
    }
    // User wants invitations to be disabled.
    else {
      // Only if invitations are enabled.
      if ($enabled) {
        // Delete code element from webform.
        $webform->deleteElement('webform_invitation_code');
        $webform->save();
      }
      drupal_set_message($this->t('Invitation mode has been disabled.'));
    }
  }

}
