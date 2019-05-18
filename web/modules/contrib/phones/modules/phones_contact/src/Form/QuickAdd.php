<?php

namespace Drupal\phones_contact\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\phones\Controller\PhoneClear;
use Drupal\phones_contact\Controller\ContactCreate;

/**
 * Implements the form controller.
 */
class QuickAdd extends FormBase {
  private $wrapper = 'contact-add-form';

  /**
   * AJAX ajaxPrev.
   */
  public function ajaxSubmit(array &$form, $form_state) {
    $phone = PhoneClear::clear($form_state->getValue('phone'));
    $name = $form_state->getValue('name');
    $type = $form_state->getValue('type');
    $org = $form_state->getValue('org');
    $response = new AjaxResponse();
    if ($phone && $name) {
      $data = [
        'phone' => $phone,
        'name' => $name,
        'type' => $type,
        'org' => $org,
      ];
      $id = ContactCreate::cr($data);
      if ($id) {
        $otvet = "Создан <a href='/phones/contact/$id'>$name</a>";
      }
      else {
        $otvet = $this->t('Error');
      }
    }
    else {
      $otvet = $this->t('Wrong name OR phone');
    }
    $response->addCommand(new HtmlCommand("#" . $this->wrapper, $otvet));
    return $response;
  }

  /**
   * AJAX Type Change.
   */
  public function ajaxChange(array &$form, $form_state) {
    $access = TRUE;
    if ($form_state->getValue('type') == 'organization') {
      $access = FALSE;
    }
    $form['org-wrapper']['org']['#access'] = $access;
    return $form['org-wrapper'];
  }

  /**
   * Build the simple form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $phone = NULL) {
    $form_state->extra = $phone;
    $form_state->setCached(FALSE);
    $options = [
      'person' => $this->t('Person'),
      'organization' => $this->t('Organization'),
    ];
    $form["type"] = [
      '#type' => 'radios',
      '#attributes' => ['class' => ['inline']],
      "#options" => $options,
      '#default_value' => 'person',
      '#ajax'   => [
        'callback' => '::ajaxChange',
        'wrapper'  => 'org-wrapper',
      ],
    ];
    $form["phone"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#default_value' => PhoneClear::clear($phone),
      '#required' => TRUE,
    ];
    $form["name"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];
    $form['org-wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'org-wrapper'],
      'org' => [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Organization'),
        '#target_type' => 'phones_contact',
        '#selection_settings' => [
          'target_bundles' => ['organization'],
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#ajax'   => [
          'callback' => '::ajaxSubmit',
        ],
      ],
      '#suffix' => '<div id="' . $this->wrapper . '"></div>',
    ];
    return $form;
  }

  /**
   * Implements a form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quick_add_contact';
  }

}
