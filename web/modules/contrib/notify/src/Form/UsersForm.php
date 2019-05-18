<?php

namespace Drupal\notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class UsersForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_users';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'notify.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('notify.settings');

    $form['#tree'] = TRUE;
    $form['info'] = array(
      '#markup' => '<p>' . t('The following table shows all users that have notifications enabled:') . '</p>',
    );

    $form['users'] = array();

    // Fetch users with notify enabled.
    $q = \Drupal::database()->select('notify', 'n');
    $q->join('users', 'u', 'n.uid = u.uid');
    $q->join('users_field_data', 'v', 'n.uid = v.uid');
    $q->fields('v', array('uid', 'name', 'mail', 'langcode'));
    $q->fields('n', array('status', 'node', 'comment', 'attempts', 'teasers'));
    $q->condition('n.status', 1);
    $q->condition('v.status', 1);
    $q->orderBy('v.name');
    $uresult = $q->execute();

    foreach ($uresult as $user) {
      $form['users'][$user->uid] = array();
      $form['users'][$user->uid]['name'] = array(
        '#markup' => $user->name,
      );
      $form['users'][$user->uid]['mail'] = array(
        '#markup' => $user->mail,
      );
      $form['users'][$user->uid]['node'] = array(
        '#type' => 'checkbox',
        '#default_value' => $user->node,
      );
      $form['users'][$user->uid]['comment'] = array(
        '#type' => 'checkbox',
        '#default_value' => $user->comment,
      );
      $form['users'][$user->uid]['teasers'] = array(
        '#type' => 'select',
        '#default_value' => $user->teasers,
        '#options' => array(
          t('Title only'),
          t('Title + Teaser'),
          t('Title + Body'),
          t('Title + Body + Fields'),
        ),
      );
      $form['users'][$user->uid]['attempts'] = array(
        '#markup' => $user->attempts ? intval($user->attempts) : 0,
      );
    }

    $form['info2'] = array(
      '#markup' => '<p>' . t('You may check/uncheck the checkboxes and the &#8220;How much&#8220;-selection to change the users\' subscription. Press &#8220;Save settings&#8220; to save the settings.' . '</p>'),
    );

    $form['bulk'] = array(
      '#title' => t('Bulk subscribe all users'),
      '#type' => 'checkbox',
      '#default_value' => FALSE,
      '#description' => t('Subscribe all non-blocked users that do not already subscribe to notifications.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('notify.settings');
    $values = $form_state->getValues();

    if (isset($values['bulk']) && 1 == $values['bulk']) {
      $node = $config->get('notify_def_node', 1);
      $comment = $config->get('notify_def_comment', 0);
      $teasers = $config->get('notify_def_teasers', 0);

      $r = \Drupal::database()->select('notify', 'n');
      $r->fields('n', array('uid'));
      $r->execute();

      $q = \Drupal::database()->select('users', 'u');
      $q->join('users_field_data', 'v', 'u.uid = v.uid');
      $q->fields('v', array('uid','name'));
      $q->condition('u.uid', 0, '>');
      $q->condition('v.status', 1, '=');
      $q->condition('u.uid', $r, 'NOT IN');
      $result = $q->execute();

      foreach ($result as $record) {
        \Drupal::database()->insert('notify')
          ->fields(array(
            'uid' => $record->uid,
            'status' => 1,
            'node' => $node,
            'comment' => $comment,
            'teasers' => $teasers,
            'attempts' => 0,
          ))
          ->execute();
      }
    }
    elseif (!array_key_exists('users', $values)) {
      drupal_set_message(t('No users have notifications enabled.'), 'warning');
      return;
    }
    if (isset($values['users']) && $values['users']) {
      foreach ($values['users'] as $uid => $settings) {
        \Drupal::database()->update('notify')
          ->fields(array(
            'node' => $settings['node'],
            'teasers' => $settings['teasers'],
            'comment' => $settings['comment'],
            // 'attempts' => $settings['attempts'],
          ))
          ->condition('uid', $uid)
          ->execute();
      }
    }
    drupal_set_message(t('Users notify settings saved.'));
  }

}
