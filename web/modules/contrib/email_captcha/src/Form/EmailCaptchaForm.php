<?php
/**
 * @file
 * Contains Drupal\email_captcha\Form.
 */


namespace Drupal\email_captcha\Form;


use Drupal\Core\Entity\Entity;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\captcha\Entity\CaptchaPoint;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;


/**
 * Class EmailCaptchaForm.
 *
 * @package Drupal\email_captcha\Form
 */
class EmailCaptchaForm extends FormBase {
  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'email_captcha_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form['email_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'email-wrapper'],
    ];

    $form['email_wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show email'),
      '#ajax' => [
        'callback' => '::show_email',
        'wrapper' => 'email-wrapper',
      ],
    ];

    $form['#title'] = 'Are you robot?';

    $form_state->setCached(FALSE);

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Ajax callback for email_captcha_form.
   */
  public function show_email(&$form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {
      return $form;
    }

    $query_params = \Drupal::request()->query->all();
    $entity_id = $query_params['entity_id'];
    $entity_type = $query_params['entity_type'];;
    $field_name = $query_params['field_name'];;
    $delta = $query_params['delta'];
    $entity_manager = \Drupal::entityTypeManager();
    $entity = $entity_manager->getStorage($entity_type)->load($entity_id);
    $items = $entity->get($field_name);

    foreach ($items as $key => $item) {
      if ($key == $delta) {
        $email = $item->value;
      }
    }

    $form['email_wrapper']['email'] = [
      '#markup' => $email,
    ];

    unset($form['email_wrapper']['submit']);
    return $form;
  }

}
