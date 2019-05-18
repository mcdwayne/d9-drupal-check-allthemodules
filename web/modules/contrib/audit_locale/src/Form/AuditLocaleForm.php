<?php

namespace Drupal\audit_locale\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 *
 */
class AuditLocaleForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $module = NULL, $id = 0) {
    // During the initial form build, add this form object to the form state and
    // allow for initial preparation before form building and processing.
    if (!$form_state->has('entity_form_initialized')) {
      $this->init($form_state);
    }

    // Ensure that edit forms have the correct cacheability metadata so they can
    // be cached.
    if (!$this->entity->isNew()) {
      \Drupal::service('renderer')->addCacheableDependency($form, $this->entity);
    }

    // Retrieve the form array using the possibly updated entity in form state.
    $form = $this->form($form, $form_state, $module, $id);

    // Retrieve and add the form actions array.
    $actions = $this->actionsElement($form, $form_state);
    if (!empty($actions)) {
      $form['actions'] = $actions;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, $module = NULL, $id = 0) {
    $form = parent::form($form, $form_state);

    $audit_users = [];

    $this->module = $module;
    $this->module_id = $id;
    if (!empty($module)) {
      $has_aids = \Drupal::service('audit_locale.audit_localeservice')->getModuleAuditLocale($module, $id);
      $audit_users = \Drupal::service('audit_locale.audit_localeservice')->getAuditUsers($has_aids);
    }
    $account = \Drupal::currentUser();
    $user = user_load($account->id());

    $tid = $user->get('company')->value;

    $tree = \Drupal::service('audit_locale.audit_localeservice')->getCompanyArchTree($tid);

    $form['treedata'] = [
      '#markup' => $tree,
    ];

    $form['audit_user'] = [
      '#markup' => $audit_users,
    ];

    $form['#theme'] = 'audit_locale_rule_edit_form';

    $form['#attached']['library'] = ['audit_locale/audit_locale_rule_edit_form'];

    $form['#attached']['drupalSettings']['audit_locale']['module'] = $module;
    $form['#attached']['drupalSettings']['audit_locale']['id'] = $id;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $form_state->module = $this->module;
    $form_state->module_id = $this->module_id;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {

    if ($form_state->module_id) {
      $form_state->setRedirectUrl(new Url('audit_locale.rule.specied.overview', ['module' => $form_state->module, 'id' => $form_state->module_id]));
    }
    else {
      $form_state->setRedirectUrl(new Url('audit_locale.rule.overview', ['module' => $form_state->module]));
    }
    drupal_set_message('保存成功');
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if ($actions['submit']) {
      $actions['submit']['#attributes']['class'] = ['kuma-button kuma-button-mblue'];
    }
    return $actions;
  }

}
