<?php

namespace Drupal\parameter_message\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class: MessageForm.
 */
class MessageForm extends ContentEntityForm {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $default_language = $entity->getUntranslated()->language()->getId();

    if (!empty($entity->langcode->value)) {
      $default_language = $entity->langcode->value;
    }

    // @codingStandardsIgnoreLine
    $languages = \Drupal::languageManager()->getCurrentLanguage();

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $default_language,
      '#empty_option' => $this->t('- Any -'),
    ];

    if (!empty($languages) && count($languages) == 1) {

      $disabled = ['disabled' => 'disabled'];

      $form['langcode']['#attributes'] = $disabled;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Completed'));
    $form_state->setRedirect('parameter_message.default');
    $entity = $this->getEntity();
    $entity->save();
  }

}
