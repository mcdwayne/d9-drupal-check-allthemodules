<?php

namespace Drupal\notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class QueueForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_queue_settings';
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

    $period = $config->get('notify_period', 86400);
    $since = $config->get('notify_send_last', 0) - $period;
    $lastdate = \Drupal::service('date.formatter')->format($since, 'short');

    if (NULL !== ($config->get('notify_send_start'))) {
      $start = \Drupal::time()->getRequestTime();
    }
    else {
      $start = $config->get('notify_send_start', 0);
    }

    $startdate = \Drupal::service('date.formatter')->format($start, 'short');
    $notify_send_last  = $config->get('notify_send_last', 0);
    if (!isset($notify_send_last)) {
      $notify_send_last = \Drupal::time()->getRequestTime();
    }
    $next_last = _notify_next_notificaton($notify_send_last);

    if ($next_last == -1) {
      $batch_msg = t('No more notifications scheduled');
    }
    elseif ($next_last == 0) {
      $batch_msg = t('The next notification is scheduled for the next cron run');
    }
    else {
      $next = \Drupal::service('date.formatter')->format($next_last, 'short');
      $batch_msg = t('The next notification is scheduled for the first cron run after ') . $next;
    }

    $form['process'] = array(
      '#type' => 'radios',
      '#title' => t('Notification queue operations'),
      '#default_value' => 0,
      '#options' => array(t('Send batch now'), t('Truncate queue'), t('Override timestamp')),
      '#description' => t('Select &#8220;Send batch now&#8220; to send next batch of e-mails queued for notifications. Select &#8220;Truncate queue&#8220; to empty queue of pending notification <em>without</em> sending e-mails. Select &#8220;Override timestamp&#8220; to override the last notification timestamp. Press &#8220;Submit&#8220; to execute.'),
    );

    $send_last = \Drupal::service('date.formatter')->format($notify_send_last, 'custom', 'Y-m-d H:i:s');

    $form['lastdate'] = array(
      '#type' => 'textfield',
      '#title' => t('Last notification timestamp'),
      '#default_value' => $send_last,
      '#size' => 19,
      '#maxlength' => 19,
      '#description' => t('To explicitly set the last notification timestamp, change the value of this field and select the &#8220;Override timestamp&#8220; option above, then press &#8220;Submit&#8220; to execute.'),
    );

    $form['batch'] = array(
      '#type' => 'fieldset',
      '#title' => t('Status'),
      '#collapsible' => TRUE,
    );

    list ($np, $cp, $nn, $cn, $nu, $cu) = _notify_count($config);

    $npcp = $np + $cp;
    if ($npcp) {
      $queue_msg = t('Notifications about at least @item queued', array(
        '@item' => \Drupal::translation()->formatPlural($npcp, '1 item is', '@count items are'),
      ));
    }
    else {
      $queue_msg = t('No notifications queued');
    }
    $flagcnt = count($config->get('notify_skip_nodes', array())) + count($config->get('notify_skip_comments', array()));
    if ($flagcnt) {
      $skip_msg = t('@item flagged for skipping', array(
        '@item' => \Drupal::translation()->formatPlural($flagcnt, '1 item is', '@count items are'),
      ));
    }
    else {
      $skip_msg = t('No item is flagged for skipping');
    }

    if (($np && $nu) || ($cp && $cu)) {
      $nonew_msg = '';
    }
    else {
      $nonew_msg = t(', no notification about unpublished items are queued');
    }
    if ($nu + $cu) {
      $unpub_msg = t('Unpublished: @nodeup and @commup', array(
          '@nodeup' => \Drupal::translation()->formatPlural($nu, '1 node', '@count nodes'),
          '@commup' => \Drupal::translation()->formatPlural($cu, '1 comment', '@count comments'),
        )) . $nonew_msg;
    }
    else {
      $unpub_msg = t('No unpublished items');
    }

    $sent = $config->get('notify_num_sent', 0);
    $fail = $config->get('notify_num_failed', 0);
    $batch_remain = count($config->get('notify_users', array()));

    $creat_msg = t('There are @nodes and @comms created', array(
      '@nodes' => \Drupal::translation()->formatPlural($np, '1 node', '@count nodes'),
      '@comms' => \Drupal::translation()->formatPlural($cp, '1 comment', '@count comments'),
    ));
    if ($nn + $cn) {
      $publ_msg = t(', and in addition @noderp and @commrp published,', array(
        '@noderp' => \Drupal::translation()->formatPlural($nn, '1 node', '@count nodes'),
        '@commrp' => \Drupal::translation()->formatPlural($cn, '1 comment', '@count comments'),
      ));
    }
    else {
      $publ_msg = '';
    }
    if ($batch_remain) {
      $intrv_msg = t('between @last and @start', array(
        '@last' => $lastdate,
        '@start' => $startdate,
      ));
      $sent_msg = t('Batch not yet complete.  So far @sent has been sent (@fail, @remain to go)', array(
        '@sent' => \Drupal::translation()->formatPlural($sent, '1 e-mail', '@count e-mails'),
        '@fail' => \Drupal::translation()->formatPlural($fail, '1 failure', '@count failures'),
        '@remain' => \Drupal::translation()->formatPlural($batch_remain, '1 user', '@count users'),
      ));
    }
    else {
      $intrv_msg = t('since @last', array(
        '@last' => $lastdate,
      ));
      $sent_msg = t('Last batch:') . ' ';
      if ($sent == 0) {
        $sent_msg = t('No e-mails were sent');
      }
      else {
        $sent_msg .= t('sent @sent', array(
          '@sent' => \Drupal::translation()->formatPlural($sent, '1 e-mail', '@count e-mails'),
        ));
      }
      if ($fail > 0) {
        $sent_msg .= ', ' . t('@fail', array(
            '@fail' => \Drupal::translation()->formatPlural($fail, '1 failure', '@count failures'),
          ));
      }
      elseif ($sent) {
        $sent_msg .= ', ' . t('no failures');
      }
    }
    $mailsystem = $config->get('mail_system', NULL);
    $ms = isset($mailsystem['default-system']) ? $mailsystem['default-system'] : t('system default');
    $form['batch']['schedule'] = array(
      '#markup' => $creat_msg . $publ_msg . ' ' . $intrv_msg . '.<br>'
        . $unpub_msg . '.<br>'
        . $queue_msg . '.<br>'
        . $skip_msg . '.<br>'
        . $sent_msg . '.<br>'
        . $batch_msg . '.<br>'
        . t('Default MailSystem: ') . $ms . '.'
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('notify.settings');
    $values = $form_state->getValues();

    $process= $values['process'];
    $notify_send_last = $config->get('notify_send_last', 0);
    $frform_send_last = strtotime($values['lastdate']);
    if (FALSE ===  $frform_send_last) {
//      form_set_error('notify_queue_settings', t('This does not look like a valid date format.'));
      drupal_set_message(t('This does not look like a valid date format.'), 'error');
      $form_state->setRebuild();
      return;
    }
    if ($process < 2) {
      if ($notify_send_last != $frform_send_last) {
        drupal_set_message(t('You must select &#8220;Override timestamp&#8221; to override the timestamp.'), 'error');
        $form_state->setRebuild();
        return;
      }
    }
    elseif ($process == 2) {
      if ($notify_send_last == $frform_send_last) {
        drupal_set_message(t('You selected &#8220;Override timestamp&#8221;, but the timestamp is not altered.'), 'error');
        $form_state->setRebuild();
        return;
      }
    }

    $watchdog_level = $config->get('notify_watchdog', 0);
    if (0 == $values['process']) { // flush
      list($num_sent, $num_fail) = _notify_send();

      if ($num_fail > 0) {
        drupal_set_message(t('@sent notification @emsent sent successfully, @fail @emfail could not be sent.',
          array(
            '@sent' => $num_sent, '@emsent' =>  \Drupal::translation()->formatPlural($num_sent, 'e-mail', 'e-mails'),
            '@fail' => $num_fail, '@emfail' =>  \Drupal::translation()->formatPlural($num_fail, 'notification', 'notifications'),
          )
        ), 'error');
//        $watchdog_status = WATCHDOG_ERROR;
      }
      elseif ($num_sent > 0) {
        drupal_set_message(t('@count pending notification @emails have been sent in this pass.', array('@count' => $num_sent, '@emails' =>  \Drupal::translation()->formatPlural($num_sent, 'e-mail', 'e-mails'))));
//        $watchdog_status = WATCHDOG_INFO;
      }
      if (0 == ($num_sent + $num_fail)) {
        drupal_set_message(t('No notifications needed to be sent in this pass.'));
      }
      else {
        if ($watchdog_level <= 1) {
          \Drupal::logger('notify')->notice('Notifications sent: @sent, failures: @fail.', array('@sent' => $num_sent, '@fail' => $num_fail));
        }
      }
      $num_sent += $config->get('notify_num_sent', 0);
      $num_fail += $config->get('notify_num_failed', 0);
      \Drupal::configFactory()->getEditable('notify.settings')
        ->set('notify_num_sent', $num_sent)
        ->set('notify_num_failed', $num_fail)
        ->set('notify_skip_nodes', array())
        ->set('notify_skip_comments', array())
        ->save();
    }
    elseif (1 == $values['process']) { // truncate
      list ($res_nodes, $res_comms, $res_nopub, $res_copub, $res_nounp, $res_counp) = _notify_select_content();
      foreach ($res_nopub as $row) {
        $q = \Drupal::database()->delete('notify_unpublished_queue', 'n');
        $q->condition('n.cid', 0);
        $q->condition('n.nid', $row->nid);
        $q->execute();
      }
      foreach ($res_copub as $row) {
        $q = \Drupal::database()->delete('notify_unpublished_queue', 'n');
        $q->condition('n.cid', $row->cid);
        $q->condition('n.nid', $row->nid);
        $q->execute();
      }

      \Drupal::configFactory()->getEditable('notify.settings')
        ->set('notify_send_start', \Drupal::time()->getRequestTime())
        ->set('notify_send_last', \Drupal::time()->getRequestTime())
        ->set('notify_cron_next', 0)
        ->set('notify_users', array())
        ->set('notify_skip_nodes', array())
        ->set('notify_skip_comments', array())
        ->save();
      drupal_set_message(t('The notification queue has been truncated. No e-mail were sent.'));
      if ($watchdog_level <= 1) {
        \Drupal::logger('notify')->notice('Notification queue truncated.');
      }
      return;
    }
    elseif (2 == $values['process']) { // override
      $last_date = strtotime($values['lastdate']);
      \Drupal::configFactory()->getEditable('notify.settings')
        ->set('notify_send_last', $last_date)
        ->set('notify_skip_nodes', array())
        ->set('notify_skip_comments', array())
        ->save();
      drupal_set_message(t('Timestamp overridden'));
    }
  }

}
