<?php

namespace Drupal\User_prune\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Userprune settings for this site.
 */
class UserPruneSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'Userprune_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['user_prune.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::config('user_prune.settings');

    $form['year_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Year'),
      '#default_value' => $config->get('year_select'),
      '#options' => [
        '0'         => $this->t('0 Years'),
        '31556926'  => $this->t('1 Year'),
        '63113852'  => $this->t('2 Years'),
        '94670778'  => $this->t('3 Years'),
        '126227704' => $this->t('4 Years'),
        '157784630' => $this->t('5 Years'),
        '189341556' => $this->t('6 Years'),
        '220898482' => $this->t('7 Years'),
        '252455408' => $this->t('8 Years'),
        '284012334' => $this->t('9 Years'),
        '315569260' => $this->t('10 Years'),
      ],
    ];

    $form['month_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Month'),
      '#default_value' => $config->get('month_select'),
      '#options' => [
        '0'         => $this->t('0 Months'),
        '2629743'   => $this->t('1 Month'),
        '5259486'   => $this->t('2 Months'),
        '7889229'   => $this->t('3 Months'),
        '10518972'  => $this->t('4 Months'),
        '13148715'  => $this->t('5 Months'),
        '15778458'  => $this->t('6 Months'),
        '18408201'  => $this->t('7 Months'),
        '21037944'  => $this->t('8 Months'),
        '23667687'  => $this->t('9 Months'),
        '26297430'  => $this->t('10 Months'),
        '28927173'  => $this->t('11 Months'),
        '31556916'  => $this->t('12 Months'),
      ],
    ];

    $form['day_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Day Year'),
      '#default_value' => $config->get('day_select'),
      '#options' => [
        '0'        => $this->t('0 Days'),
        '86400'    => $this->t('1 Day'),
        '172800'   => $this->t('2 Days'),
        '259200'   => $this->t('3 Days'),
        '345600'   => $this->t('4 Days'),
        '432000'   => $this->t('5 Days'),
        '518400'   => $this->t('6 Days'),
        '604800'   => $this->t('7 Days'),
        '691200'   => $this->t('8 Days'),
        '777600'   => $this->t('9 Days'),
        '864000'   => $this->t('10 Days'),
        '950400'   => $this->t('11 Days'),
        '1036800'  => $this->t('12 Days'),
        '1123200'  => $this->t('13 Days'),
        '1209600'  => $this->t('14 Days'),
        '1296000'  => $this->t('15 Days'),
        '1382400'  => $this->t('16 Days'),
        '1468800'  => $this->t('17 Days'),
        '1555200'  => $this->t('18 Days'),
        '1641600'  => $this->t('19 Days'),
        '1728000'  => $this->t('20 Days'),
        '1814400'  => $this->t('21 Days'),
        '1900800'  => $this->t('22 Days'),
        '1987200'  => $this->t('23 Days'),
        '2073600'  => $this->t('24 Days'),
        '2160000'  => $this->t('25 Days'),
        '2246400'  => $this->t('26 Days'),
        '2332800'  => $this->t('27 Days'),
        '2419200'  => $this->t('28 Days'),
        '2505600'  => $this->t('29 Days'),
        '2592000'  => $this->t('30 Days'),
        '2678400'  => $this->t('31 Days'),
      ],
    ];

    $form['user_number_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the number of user to delete on cronrun'),
      '#default_value' => $config->get('user_number_select'),
      '#options' => [
        '10'  => $this->t('10'),
        '25'  => $this->t('25'),
        '50'  => $this->t('50'),
        '75'  => $this->t('75'),
        '100' => $this->t('100'),
        '200' => $this->t('200'),
        '500' => $this->t('500'),
      ],
    ];

    $form['user_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('User status'),
      '#default_value' => $config->get('user_status'),
      '#options' => [
        'all' => $this->t('All users'),
        'blocked' => $this->t('Just the blocked users'),
        'active' => $this->t('Just the active users'),
      ],
    ];

    // User never logged in.
    $form['user_never_logged_in_label'] = [
      '#markup' => "<label for='logged-in'>" . $this->t('Never logged in users') . "</div>",
    ];

    $form['user_never_logged_in'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete only users who never logged in.'),
      '#default_value' => $config->get('user_never_logged_in'),
    ];

    // Existing content settings.
    $form['content_display_label'] = [
      '#markup' => "<label for='additonal-settings'>" . $this->t('Already Existing User Content') . "</label><br>",
    ];
    $form['user_with_comment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not delete users who posted comments.'),
      '#default_value' => $config->get('user_with_comment'),
    ];

    $form['delete_users_on_cron_lable'] = [
      '#markup' => "<label for='logged-in'>" . $this->t('On Cron') . "</div>",
    ];

    $form['delete_users_on_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete users on each cron run.'),
      '#default_value' => $config->get('delete_users_on_cron'),
    ];

    $form['preview_button'] = [
      '#type' => 'submit',
      '#value' => 'Preview',
      '#submit' => ['preview_button_action'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('user_prune.settings');
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    drupal_set_message($this->t("Configuration saved!"));
  }

}
