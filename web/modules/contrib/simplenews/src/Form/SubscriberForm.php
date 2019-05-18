<?php

namespace Drupal\simplenews\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the subscriber edit forms.
 *
 * The acting user is someone with administrative privileges managing other
 * users (not themselves).
 */
class SubscriberForm extends SubscriptionsFormBase {

  /**
   * {@inheritdoc}
   */
  protected $allowDelete = TRUE;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /* @var \Drupal\simplenews\SubscriberInterface $subscriber */
    $subscriber = $this->entity;

    $form['#title'] = $this->t('Edit subscriber @mail', array('@mail' => $subscriber->getMail()));

    $form['activated'] = array(
      '#title' => t('Status'),
      '#type' => 'fieldset',
      '#description' => t('Whether the subscription is active or blocked.'),
      '#weight' => 15,
    );
    $form['activated']['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Active'),
      '#default_value' => $subscriber->getStatus(),
      '#disabled' => !$subscriber->get('status')->access('edit'),
    );

    $language_manager = \Drupal::languageManager();
    if ($language_manager->isMultilingual()) {
      $languages = $language_manager->getLanguages();
      foreach ($languages as $langcode => $language) {
        $language_options[$langcode] = $language->getName();
      }
      $form['language'] = array(
        '#type' => 'fieldset',
        '#title' => t('Preferred language'),
        '#description' => t('The e-mails will be localized in language chosen. Real users have their preference in account settings.'),
        '#disabled' => FALSE,
      );
      if ($subscriber->getUserId()) {
        // Fallback if user has not defined a language.
        $form['language']['langcode'] = array(
          '#type' => 'item',
          '#title' => t('User language'),
          '#markup' => $subscriber->language()->getName(),
        );
      }
      else {
        $form['language']['langcode'] = array(
          '#type' => 'select',
          '#default_value' => $subscriber->language()->getId(),
          '#options' => $language_options,
          '#required' => TRUE,
        );
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSubmitMessage(FormStateInterface $form_state, $op, $confirm) {
    return $this->t('Subscriber %label has been updated.', array('%label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('view.simplenews_subscribers.page_1');
  }

}
