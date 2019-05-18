<?php

namespace Drupal\locker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Datetime\DateTime;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Edit locker variable form.
 */
class LockerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'locker_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['locker.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('locker.settings');

    $active_value = $config->get('locker_site_locked', NULL);
    if (empty($active_value)) {
      $active_value = 'no';
    }
    $active = ['no' => $this->t('No'), 'yes' => $this->t('Yes')];
    $form['locker_active'] = [
      '#type' => 'radios',
      '#default_value' => $active_value,
      '#title' => $this->t('Lock your Drupal site'),
      '#options' => $active,
    ];

    $locker_access_options = $config->get('locker_access_options', 'user_pass');
    if (!$locker_access_options) {
      $locker_access_options = 'user_pass';
    }
    $form['locker_access_options'] = [
      '#type' => 'radios',
      '#default_value' => $locker_access_options,
      '#title' => $this->t('Unlock options'),
      '#options' => [
        'passphrase' => $this->t('Passphrase only'),
        'user_pass' => $this->t('Username/Password'),
      ],
      '#states' => [
        'invisible' => [
          ':input[name="locker_active"]' => ['value' => 'no'],
        ],
      ],
    ];

    $url = $config->get('locker_custom_url', NULL);
    if (strlen($url) < 1) {
      $url = 'unlock.html';
    }
    $form['custom_url']['url'] = [
      '#type' => 'textfield',
      '#default_value' => $url,
      '#title' => $this->t('Custom URL (Default: unlock.html)'),
      '#description' => $this->t('i.e. coming-soon.html, auth/unlock'),
      '#states' => [
        'visible' => [
          ':input[name="locker_active"]' => ['value' => 'yes'],
        ],
      ],
    ];

    $form['passphrase']['passphrase'] = [
      '#type' => 'password',
      '#title' => $this->t('Passphrase'),
      '#description' => $this->t('Enter your desired passphrase to unlock the site.'),
      '#states' => [
        'visible' => [
          ':input[name="locker_access_options"]' => ['value' => 'passphrase'],
          ':input[name="locker_active"]' => ['value' => 'yes'],
        ],
      ],
    ];

    $form['passphrase']['passphrase_confirm'] = [
      '#type' => 'password',
      '#title' => $this->t('Confirm passphrase'),
      '#description' => $this->t('Confirm your passphrase.'),
      '#states' => [
        'visible' => [
          ':input[name="locker_access_options"]' => ['value' => 'passphrase'],
          ':input[name="locker_active"]' => ['value' => 'yes'],
        ],
      ],
    ];

    $active_username = $config->get('locker_user', NULL);
    $form['user_pass']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $active_username,
      '#description' => $this->t('Enter your desired username to unlock the site.'),
      '#states' => [
        'visible' => [
          ':input[name="locker_access_options"]' => ['value' => 'user_pass'],
          ':input[name="locker_active"]' => ['value' => 'yes'],
        ],
      ],
    ];

    $form['user_pass']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Enter your desired password to unlock the site.'),
      '#states' => [
        'visible' => [
          ':input[name="locker_access_options"]' => ['value' => 'user_pass'],
          ':input[name="locker_active"]' => ['value' => 'yes'],
        ],
      ],
    ];

    $form['user_pass']['password_confirm'] = [
      '#type' => 'password',
      '#title' => $this->t('Confirm password'),
      '#description' => $this->t('Confirm your password.'),
      '#states' => [
        'visible' => [
          ':input[name="locker_access_options"]' => ['value' => 'user_pass'],
          ':input[name="locker_active"]' => ['value' => 'yes'],
        ],
      ],
    ];

    $unlock_date = '';
    $unlock_datetime = $config->get('unlock_datetime');
    $unlock_timezone = $config->get('unlock_timezone');
    if (empty($unlock_timezone)) {
      $unlock_timezone = date_default_timezone_get();
    }
    if (!empty($unlock_datetime)) {
      $unlock_date = new DrupalDateTime($unlock_datetime, $unlock_timezone);
    }

    $form['unlock'] = [
      '#type' => 'checkbox',
      '#title' => t('Unlock site using specific time'),
      '#title_display' => 'before',
      '#default_value' => !empty($unlock_datetime),
      '#states' =>[
        'invisible' => [
          ':input[name="locker_active"]' => ['value' => 'no'],
        ],
      ],
      '#attributes' => [
        'onchange' => 'document.getElementById(\'edit-unlock-datetime-date\').value = \'\'',
      ],
    ];

    $form['unlock_datetime'] = [
      "#type" => "datetime",
      "#title" => t('Lock site till'),
      '#required' => FALSE,
      '#default_value' => $unlock_date,
      '#element_validate' => [[$this, 'unlock_datetimeValidator']],
      '#date_year_range' => date('Y') . ':2050',
      '#date_timezone' => $unlock_timezone,
      '#expose_timezone' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="locker_active"]' => ['value' => 'yes'],
          ':input[name="unlock"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Expose a timezone selector.
    $form['unlock_timezone'] = [
      '#type' => 'select',
      '#options' => array_combine(\DateTimeZone::listIdentifiers(), \DateTimeZone::listIdentifiers()),
      '#description' => t('Set launch date for this web site. Default template redirects to root drupal URI at this specific time.'),
      '#default_value' => $unlock_timezone,
      '#required' => $element['#required'],
      '#states' => [
        'visible' => [
          ':input[name="locker_active"]' => ['value' => 'yes'],
          ':input[name="unlock"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $locker_active = $form_state->getValue('locker_active');

    if ($locker_active == 'yes') {
      $locker_access_options = $form_state->getValue('locker_access_options');

      if ($locker_access_options == 'user_pass') {
        $user_validate = $form_state->getValue('username');
        $pass_validate = $form_state->getValue('password');
        $pass_confirm_validate = $form_state->getValue('password_confirm');
        if (strlen($user_validate) < 1) {
          $form_state->setErrorByName('username', $this->t('To lock your Drupal site insert a username.'));
        }
        if (strlen($pass_validate) < 1) {
          $form_state->setErrorByName('password', $this->t('To lock your Drupal site insert a password.'));
        }
        elseif ($pass_validate != $pass_confirm_validate) {
          $form_state->setErrorByName('password_confirm', $this->t('Passwords do not match.'));
        }
      }
      elseif ($locker_access_options == 'passphrase') {
        $passphrase_validate = $form_state->getValue('passphrase');
        $passphrase_confirm_validate = $form_state->getValue('passphrase_confirm');

        if (strlen($passphrase_validate) < 1) {
          $form_state->setErrorByName('passphrase', $this->t('To lock your Drupal site insert a passphrase.'));
        }
        elseif ($passphrase_validate != $passphrase_confirm_validate) {
          $form_state->setErrorByName('passphrase_confirm', $this->t('Passphrases do not match.'));
        }
      }
      elseif ($locker_access_options == 'roles') {
        $roles_validate = $form_state->getValue('roles');

        $rolesOne = FALSE;
        if (!empty($roles_validate)) {
          foreach ($roles_validate as $val) {
            if ($val) {
              $rolesOne = TRUE;
            }
          }
        }
        if (!$rolesOne) {
          $form_state->setErrorByName('roles', $this->t('Select at least one role.'));
        }
      }

      $url = $form_state->getValue('url');
      if (!$url || substr($url, 0, 1) == '/' || substr($url, -1) == '/' || !preg_match("@^([a-zA-Z0-9.\-_/\?=]{1,300})$@", $url)) {
        $form_state->setErrorByName('url', $this->t('Problem with url.'));
      }
    }

    $unlock_datetime = $form_state->getValue('unlock_datetime');

    if (is_array($unlock_datetime) && !empty($unlock_datetime['date']) && $unlock_datetime['object'] instanceof DrupalDateTime && !$unlock_datetime['object']->checkErrors()) {
      $date_timezone = new \DateTime($unlock_datetime['object']->format('Y-m-d H:i:s'), new \DateTimeZone($form_state->getValue('unlock_timezone')));
      $date = DrupalDateTime::createFromDateTime($date_timezone);
      $unlock_datetime_iso = $date->format('Y-m-d\TH:i:sP');
      if (strtotime($unlock_datetime_iso) < strtotime('now')) {
        $form_state->setErrorByName('unlock_datetime', t('Unlock datetime [@date] must be older then current time.', ['@date' => $unlock_datetime_iso]));
      }
    }
  }

  /**
   * Implements callback_form_element_validate() for #type 'datetime'.
   */
  public function unlock_datetimeValidator(&$element, FormStateInterface $form_state, &$complete_form) {
    $unlock_datetime = $form_state->getValue('unlock_datetime');
    if (is_array($unlock_datetime) && !empty($unlock_datetime['date'])) {
      if (!isset($unlock_datetime['object']) || !($unlock_datetime['object'] instanceof DrupalDateTime)) {
        $form_state->setErrorByName('unlock_datetime', t('Unlock is not correct.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('locker.settings');
    $active_radio = $form_state->getValue('locker_active');
    if ($active_radio == 'yes') {
      $locker_access_options = $form_state->getValue('locker_access_options');
      $config->set('locker_access_options', $locker_access_options);
      $locker_url = $form_state->getValue('url');
      $config->set('locker_custom_url', $locker_url);
      if ($locker_access_options == 'user_pass') {
        $user = $form_state->getValue('username');
        $pass = $form_state->getValue('password');
        $passmd5 = md5($pass);
        $config
          ->set('locker_user', $user)
          ->set('locker_password', $passmd5);
      }
      elseif ($locker_access_options == 'passphrase') {
        $passphrase = $form_state->getValue('passphrase');
        $config
          ->set('locker_passphrase', md5($passphrase));
      }

      $unlock_datetime = $form_state->getValue('unlock_datetime');
      if (is_array($unlock_datetime)) {
        $unlock_datetime = $unlock_datetime['object'];
      }
      if ($unlock_datetime instanceof DrupalDateTime && !$unlock_datetime->checkErrors()) {
        $date_timezone = new \DateTime($unlock_datetime->format('Y-m-d H:i:s'), new \DateTimeZone($form_state->getValue('unlock_timezone')));
        $date = DrupalDateTime::createFromDateTime($date_timezone);
        $unlock_datetime_iso = $date->format('Y-m-d\TH:i:sP');
        $config->set('unlock_datetime', $unlock_datetime_iso);
        $config->set('unlock_timezone', $form_state->getValue('unlock_timezone'));
      }
      else {
        $config->set('unlock_datetime', '');
        $config->set('unlock_timezone', '');
      }

      $config->set('locker_site_locked', $active_radio)->save();
      \Drupal::service("router.builder")->rebuild();
      unset($_SESSION['locker_unlocked']);
    }
    elseif ($active_radio == 'no') {
      $config->delete();
      \Drupal::service("router.builder")->rebuild();
      unset($_SESSION['locker_unlocked']);
      Markup::create($this->t('Successfully unlocked.'), 'status');
    }

    parent::submitForm($form, $form_state);
  }

}
