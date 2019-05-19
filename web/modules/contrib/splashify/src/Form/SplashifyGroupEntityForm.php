<?php

namespace Drupal\splashify\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Splashify group entity edit forms.
 *
 * @ingroup splashify
 */
class SplashifyGroupEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\splashify\Entity\SplashifyGroupEntity */
    $entity = $this->entity;
    $form = parent::buildForm($form, $form_state);

    // Use drupal states.
    $form['field_list_pages']['widget'][0]['value']['#states'] = array(
      'visible' => array(
        ':input[name="field_where"]' => array('value' => 'list'),
      ),
      'required' => array(
        ':input[name="field_where"]' => array('value' => 'list'),
      ),
    );
    $form['field_roles']['#states'] = array(
      'visible' => array(
        ':input[name="field_restrict[value]"]' => array('checked' => TRUE),
      ),
    );
    $form['field_splash_mode']['#states'] = array(
      'invisible' => array(
        ':input[name="field_mode"]' => array('value' => 'full_screen'),
      ),
    );
    $form['field_size']['widget'][0]['value']['#states'] = array(
      'visible' => array(
        ':input[name="field_mode"]' => array(
          array('value' => 'window'),
          array('value' => 'lightbox'),
        ),
      ),
      'required' => array(
        ':input[name="field_mode"]' => array(
          array('value' => 'window'),
          array('value' => 'lightbox'),
        ),
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Splashify group entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Splashify group entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.splashify_group_entity.canonical', ['splashify_group_entity' => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // If they have the opposite option checked, the "All" option should not be
    // selected.
    $values = $form_state->getValues();
    if ($values['field_opposite']['value']) {
      if ($values['field_where'][0]['value'] == 'all') {
        $form_state->setError($form['field_where']['widget'], $this->t('The "All" option cannot be selected when checking the "Opposite" option.'));
      }
    }

    // Make sure each path is valid.
    $paths = $values['field_list_pages'][0]['value'];
    $what_paths = preg_split('/[\n\r]+/', $paths);
    $errors = array();
    foreach ($what_paths as $path) {
      // If this is a valid Drupal path.
      if ($is_valid = \Drupal::service('path.validator')->isValid($path)) {
        continue;
      }

      // Now check if this is a url value.
      if (substr($path, 0, 7) == 'http://') {
        continue;
      }

      // This path is not an alias or the source url.
      $errors[] .= t('The path "@path" is not valid.', array('@path' => $path));
    }

    // Since there could be multiple errors for this one field, we want to
    // break each error into a separate line.
    if (count($errors) > 0) {
      $form_state->setError($form['field_list_pages']['widget'], implode('<br />', $errors));
    }

    // Require field_list_pages values.
    if (empty($paths) && $values['field_where'][0]['value'] == 'list') {
      $form_state->setError($form['field_list_pages']['widget'], $this->t("Field 'List Pages' can't be empty"));
    }
  }
}
