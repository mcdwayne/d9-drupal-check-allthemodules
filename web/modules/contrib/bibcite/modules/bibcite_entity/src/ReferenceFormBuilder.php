<?php

namespace Drupal\bibcite_entity;

use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides reference form building and processing hotfix.
 *
 * @see https://www.drupal.org/project/bibcite/issues/2930990
 *
 * @ingroup form_api
 */
class ReferenceFormBuilder extends FormBuilder {

  /**
   * Get entity if stores in cache.
   *
   * If we have in cache our entity,
   * we need to apply it to ReferenceEntityFormBuilder.
   *
   * @return bool
   *   Return entity from cache. FALSE if not in cache.
   */
  public function restoreFromCache() {
    $request = $this->requestStack->getCurrentRequest();
    $form_state = (new FormState())->setFormState([]);
    $form_state->setRequestMethod($request->getMethod());
    $input = $form_state->getUserInput();
    if (!isset($input)) {
      $input = $form_state->isMethodType('get') ? $request->query->all() : $request->request->all();
    }

    if (isset($input['form_build_id'])) {
      $current_user_id = \Drupal::currentUser()->id();
      $cid = 'bibcite_entity_populate:' . $current_user_id . ':' . $input['form_build_id'];
      if ($cache = \Drupal::cache()->get($cid)) {
        \Drupal::cache()->delete($cid);
        return $cache->data;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm($form_id, FormStateInterface &$form_state) {

    $form = parent::buildForm($form_id, $form_state);

    $form_id = $this->getFormId($form_id, $form_state);
    $input = $form_state->getUserInput();
    $check_cache = isset($input['form_id']) && $input['form_id'] == $form_id && !empty($input['form_build_id']);
    if (!$check_cache) {
      // If it is first form build (form not cached),
      // we need to save entity for second form build.
      $info = $form_state->getBuildInfo();
      $callback_object = $info['callback_object'];
      $entity = $callback_object->getEntity();
      $current_user_id = \Drupal::currentUser()->id();
      \Drupal::cache()->set('bibcite_entity_populate:' . $current_user_id . ':' . $form['#build_id'], $entity, \Drupal::time()->getRequestTime() + 3600);
    }
    return $form;
  }

}
