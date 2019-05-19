<?php

namespace Drupal\webform_digests\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform_digests\Entity\WebformDigestInterface;
use Drupal\Core\Url;

/**
 * Class WebformDigestConditionsFormBase.
 */
abstract class WebformDigestConditionsFormBase extends FormBase {

  protected $webformDigest;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_digest_conditions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformDigestInterface $webform_digest = NULL) {

    $this->webformDigest = $webform_digest;
    $webform = Webform::load($this->webformDigest->getWebform());

    $form['conditional_logic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Conditional logic'),
    ];
    $form['conditional_logic']['conditions'] = [
      '#type' => 'webform_element_states',
      '#state_options' => [
        'enabled' => $this->t('Enabled'),
        'disabled' => $this->t('Disabled'),
      ],
      '#selector_options' => $webform->getElementsSelectorOptions(),
      '#multiple' => FALSE,
      '#default_value' => $this->webformDigest->getConditions(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
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
    $this->webformDigest->setConditions($form_state->getValue('conditions'));
    $this->webformDigest->save();

    $form_state->setRedirectUrl(Url::fromRoute('entity.webform_digest.collection'));
  }

}
