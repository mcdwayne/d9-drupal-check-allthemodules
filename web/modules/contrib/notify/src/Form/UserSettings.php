<?php

namespace Drupal\notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

if (!defined('NOTIFY_NODE_TYPE')) {
  define('NOTIFY_NODE_TYPE', 'notify_node_type_');
}

/**
 * Defines a form that configures forms module settings.
 */
class UserSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_user_settings';
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

    $user = \Drupal::currentUser();
    $userprofile = \Drupal::routeMatch()->getParameter('user');

    if ($user->id() != $userprofile && !\Drupal::currentUser()->hasPermission('administer notify')) {
      drupal_access_denied();
      return;
    }

    $account = \Drupal\user\Entity\User::load($userprofile);
    if (!is_object($account)) {
      return;
    }

    $result = \Drupal::database()->select('users', 'u');
    $result->leftjoin('users_field_data', 'v', 'u.uid = v.uid');
    $result->leftjoin('notify', 'n', 'u.uid = n.uid');
    $result->fields('u', array('uid'));
    $result->fields('v', array('name','mail'));
    $result->fields('n', array('node','teasers', 'comment', 'status'));
    $result->condition('u.uid', $userprofile);
    $result->allowRowCount = TRUE;
    $notify = $result->execute()->fetchObject();

    if (0 == $notify) {
    // Internal error.
      $notify = NULL;
    }

    $form = array();
    if (!$notify->mail) {
      $url = '/user/' . $userprofile . '/edit';
      drupal_set_message(t('Your e-mail address must be specified on your <a href="@url">my account</a> page.', array('@url' => $url)), 'error');
    }

    $form['notify_page_master'] = array(
      '#type' => 'fieldset',
      '#title' => t('Master switch'),
    );
    // If user existed before notify was enabled, these are not set in db.
    if (!isset($notify->status)) {
      $notify->status = 0;
      $notify->node = 0;
      $notify->teasers = 0;
      $notify->comment = 0;
    }
    if (\Drupal::service('module_handler')->moduleExists('advanced_help')) {
      $output = theme('advanced_help_topic', array(
        'module' => 'notify',
        'topic' => 'users',
      ));
    }
    else {
      $output = '';
    }

    $form['notify_page_master']['status'] = array(
      '#type' => 'radios',
      '#title' => t('Notify status'),
      '#default_value' => $notify->status,
      '#options' => array(t('Disabled'), t('Enabled')),
      '#description' => $output . '&nbsp;' . t('The master switch overrides all other settings for Notify.  You can use it to suspend notifications without having to disturb any of your settings under &#8220;Detailed settings&#8221; and &#8220;Subscriptions&#8221;.'),
    );

    $form['notify_page_detailed'] = array(
      '#type' => 'fieldset',
      '#title' => t('Detailed settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => t('These settings will only be effective if the master switch is set to &#8220;Enabled&#8221;.'),
    );
    $form['notify_page_detailed']['node'] = array(
      '#type' => 'radios',
      '#title' => t('Notify new content'),
      '#default_value' => $notify->node,
      '#options' => array(t('Disabled'), t('Enabled')),
      '#description' => t('Include new posts in the notification mail.'),
    );
    $form['notify_page_detailed']['comment'] = array(
      '#type' => 'radios',
      '#access' => \Drupal::service('module_handler')->moduleExists('comment'),
      '#title' => t('Notify new comments'),
      '#default_value' => $notify->comment,
      '#options' => array(t('Disabled'), t('Enabled')),
      '#description' => t('Include new comments in the notification mail.'),
    );
    $form['notify_page_detailed']['teasers'] = array(
      '#type' => 'radios',
      '#title' => t('How much to include?'),
      '#default_value' => $notify->teasers,
      '#options' => array(
        t('Title only'),
        t('Title + Teaser/Excerpt'),
        t('Title + Body'),
        t('Title + Body + Fields'),
      ),
      '#description' => t('Select the amount of each item to include in the notification e-mail.'),
    );

    $set = 'notify_page_nodetype';
    $form[$set] = array(
      '#type' => 'fieldset',
      '#title' => t('Subscriptions'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => t('Tick the content types you want to subscribe to.'),
    );
    $alltypes = \Drupal\node\Entity\NodeType::loadMultiple();
    $enatypes = array();

    foreach (\Drupal\node\Entity\NodeType::loadMultiple() as $type => $object) {
      if ($config->get(NOTIFY_NODE_TYPE . $type, 0)) {
        $enatypes[] = array($type, $object->label());
      }
    }
    if (\Drupal::currentUser()->hasPermission('administer notify queue', $account) || empty($enatypes )) {
      $enatypes = array();
      foreach ($alltypes as $type => $obj) {
        $enatypes[] = array($type, $obj->label());
      }
    }

    //TODO: FIX '_notify_user_has_subscriptions' LATER ON
    $exists = _notify_user_has_subscriptions($userprofile);
    if ($exists) {
      // Custom subscriptions exists, use those.
      foreach ($enatypes as $type) {
        $field = \Drupal::database()->select('notify_subscriptions', 'n')
          ->fields('n', array('uid','type'))
          ->condition('uid', $userprofile)
          ->condition('type', $type[0])
          ->execute()->fetchObject();
        $default = $field ? TRUE : FALSE;
        $form[$set][NOTIFY_NODE_TYPE . $type[0]] = array(
          '#type' => 'checkbox',
          '#title' => $type[1],
          '#return_value' => 1,
          '#default_value' => $default,
        );
      }
    }
    else {
      // No custom subscriptions, so inherit default.
      foreach ($enatypes as $type) {
        $form[$set][NOTIFY_NODE_TYPE . $type[0]] = array(
          '#type' => 'checkbox',
          '#title' => $type[1],
          '#return_value' => 1,
          '#default_value' => TRUE,
        );
      }
    }


    $form['uid'] = array(
      '#type' => 'value',
      '#value' => $userprofile,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $uid = $values['uid'];
    \Drupal::database()->delete('notify')
      ->condition('uid', $uid)
      ->execute();

    $id = \Drupal::database()->insert('notify')
      ->fields(array(
        'uid' => $uid,
        'status' => $values['status'],
        'node' => $values['node'],
        'teasers' => $values['teasers'],
        'comment' => $values['comment'],
      ))
      ->execute();
    $subscriptions = array();
    // Remember that this is a custom subscriber.
    $subscriber = _notify_user_has_subscriptions($uid);
    if (!$subscriber) {
      \Drupal::database()->insert('notify_subscriptions')
        ->fields(array(
          'uid' => $uid,
          'type' => 'magic custom subscription',
        ))
        ->execute();
    }

    foreach ($values as $key => $value) {
      if (preg_match("/^" . NOTIFY_NODE_TYPE . "/", $key)) {
        $key = substr($key, 17);

        $id = \Drupal::database()->select('notify_subscriptions', 'n')
          ->fields('n', array('id','uid','type'))
          ->condition('uid', $uid)
          ->condition('type', $key)
          ->execute()->fetchObject();
        if ($id) {
          $id = $id->id;
          if (!$value) {
            \Drupal::database()->delete('notify_subscriptions')
              ->condition('id', $id)
              ->execute();
          }
        }
        else {
          if ($value) {
            \Drupal::database()->insert('notify_subscriptions')
              ->fields(array(
                'uid' => $uid,
                'type' => $key,
              ))
              ->execute();
          }
        }
      }
    }

    drupal_set_message(t('Notify settings saved.'));
  }

}
