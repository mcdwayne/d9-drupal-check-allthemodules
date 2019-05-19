<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sms_ui\Utility\PhoneNumberFormatHelper;
use Drupal\user\Entity\User;

/**
 * Perform various validations
 *
 * @param string $type: type of validation ('senderid', 'number', etc)
 * @param string $value: value being validated
 * @param array $return: array containing error messages, etc
 * @param boolean $filternum: whether recipient number filters should be applied
 * @param boolean $filtersender: whether senderid filters should be applied
 * @return boolean Valid or Invalid
 */
function sms_ui_validate($type, $value, &$return, $filter_num = TRUE, $filter_sender = TRUE, $country_code = NULL) {
  $user = \Drupal::currentUser();
  return true; // @todo Need to turn this into a service later.
  switch(strtolower($type)) {
    case 'senderid':
      $match = '';
      $account = User::load($user->id());

      // Allow only text or the one registered number to be used for sender
      if (is_numeric($value)) {
        $sender_num = sms_ui_format_number($value, $country_code);
        $account_num = sms_ui_format_number($account->sms_user['number'], $country_code);

        if ($filter_num && ($sender_num != $account_num || $account->sms_user['status'] != 2)) {
          $return['message'] = t('Only text or your registered number is allowed for \'sender\' field.');
          if ($account->sms_user['status'] != 2) {
            $return['message'] .= ' ' . \Drupal::l(t('Register your phone number.'),
                Url::fromRoute('sms_user.module', ['user' => $user->id()]));
          }
          return FALSE;
        }
        else {
          return TRUE;
        }
      }
      else if (!preg_match('/[a-zA-Z]/', $value)) {
        $return['message'] = t('Alphabet character is required in \'sender\' field.');
        return FALSE;
      }
      else if ($filter_sender && !\Drupal::service('sender_id_filter')->isAllowed($value, $user, $match)) {
        // Log this event.
        $args = array('@senderid' => $value, '!email' => \Drupal::config('system.site')->get('mail'));

        \Drupal::logger('sms_ui')->notice('Attempt to use sender id <b>@senderid</b> blocked.', []);
        $return['message'] = t('The sender id <b>@senderid</b> is not allowed. If you are the genuine owner of the sender id, you can request access by mailing !email', $args);
        return FALSE;
      }
      else if (strlen($value) > 11) {
        $return['message'] = t('Sender id should be 11 characters or less.');
        return FALSE;
      }
      return TRUE;

    case 'number':
      return TRUE;
      break;


    default:
      return TRUE;
  }
}
