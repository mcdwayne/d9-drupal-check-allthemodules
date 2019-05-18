<?php

namespace Drupal\devel_snippet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Devel Snippet edit forms.
 *
 * @ingroup devel_snippet
 */
class DevelSnippetForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\devel_snippet\Entity\DevelSnippet */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['actions']['execute'] = [
        '#type' => 'submit',
        '#value' => $this->t('Execute'),
        '#submit' => ['::executeCode'],
      ];
    }

    return $form;
  }

  /**
   * Execute snippet code.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function executeCode(array $form, FormStateInterface $form_state) {
    ob_start();
    $code = $form_state->getValue('code');
    $code = reset($code);
    $code = $code['value'];
    print eval($code);
    $_SESSION['devel_snippet_execute_code'] = $code;
    dpm(ob_get_clean());
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Devel Snippet.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Devel Snippet.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.devel_snippet.canonical', ['devel_snippet' => $entity->id()]);
  }

}
