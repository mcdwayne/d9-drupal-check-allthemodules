<?php
/**
 * @file
 * Contains \Drupal\getresponse\Form\GetresponseConfigForm.
 */
namespace Drupal\getresponse\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\getresponse\Service\Api;
use Drupal\getresponse\Service\Rss;
use Drupal\Core\Url;

/**
 * Class GetresponseConfigForm
 * @package Drupal\getresponse\Form
 */
class GetresponseConfigForm extends ConfigFormBase {

  const API_URL_360_COM = 'https://api3.getresponse360.com/v3';
  const API_URL_360_PL = 'https://api3.getresponse360.pl/v3';
  const API_URL_SMB = 'https://api.getresponse.com/v3';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['getresponse.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'getresponse_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('getresponse.settings');
    $api = new Api($config->get('api_key'), $config->get('api_url'), $config->get('domain'));

    $form['#attached']['library'][] = 'getresponse/getresponse.settings.form';

    $form['left'] = array(
      '#type' => 'item',
    );

    $form['right'] = array(
      '#type' => 'item',
    );

    $form['left']['account'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('GetResponse Account'),
      '#weight' => 1,
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );

    if ($config->get('api_key')) {
      $account = $api->accounts();

      $form['left']['account']['info'] = array(
        '#type' => 'item',
        '#markup' => '<br />' . $account->firstName . ' ' . $account->lastName . ', ' . $account->email . '<br />' . $account->street . ', ' . $account->zipCode . ' ' . $account->city . ', ' . $account->countryCode->countryCode . '<br />' . $account->state . ', ' . $account->countryCode->countryCode . '<br /><br />',
      );

      $api_button = Link::createFromRoute(
        $this->t('Disconnect'),
        'getresponse.disconnect',
        array(),
        array(
          'attributes' => array(
            'id' => 'gr-disconnect-btn',
            'class' => array('button')
          )
        )
      )->toRenderable();
    }
    else {
      $api_button = Link::createFromRoute(
        $this->t('Connect'),
        'getresponse.settings_form',
        array(),
        array(
          'attributes' => array(
            'class' => array('button'),
            'id' => 'gr-connect'
          )
        )
      )->toRenderable();
    }

    $form['left']['account']['api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $config->get('api_key'),
      '#size' => 32,
      '#minlength' => 12,
      '#description' => $api_button,
    );

    if (NULL === $config->get('api_key')) {

      $form['left']['account']['is_enterprise'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('GetResponse 360'),
        '#size' => 32,
        '#attributes' => array('id' => array('is-enterprise'))
      );

      $form['left']['account']['api_url'] = array(
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#options' => array(
          self::API_URL_360_PL => $this->t('GetResponse 360 PL'),
          self::API_URL_360_COM => $this->t('GetResponse 360 COM')
        ),
        '#prefix' => '<div class="enterprise-type">',
        '#suffix' => '</div>'
      );

      $form['left']['account']['domain'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Domain'),
        '#size' => 32,
        '#minlength' => 12,
        '#prefix' => '<div class="enterprise-type" style="display: none">',
        '#suffix' => '</div>',
      );
    }

    $form['left']['account']['api_key_info'] = array(
      '#type' => 'item',
      '#markup' => '<br />' . $this->t('You can find your API key in <a href="https://app.getresponse.com/manage_api.html" target="_blank">integration settings</a> of your GetResponse account.<br>Log in to GetResponse and go to <strong>My account > API & OAuth</strong> to find the key.') . '<br />' .
        '<div id="gr-disconnect-modal" class="hidden">' .
        '<h2 class="gr-modal-title">' . $this->t('Are you sure you want to disconnect from GetResponse?') . '</h2>' .
        '<p>' . $this->t('This stops new subscribtions via forms, comments,<br>or during account registration.') . '</p>' .
        '<div class="gr-modal-buttons">' .
        '<a id="gr-stay-connected" class="button gr-std-btn" href="#">' . $this->t('Stay connected') . '</a>' .
        '<a class="button gr-red-btn" id="gr-disconnect-confirm" href="#">' . $this->t('Yes, disconnect') . '</a>' .
        '</div>' .
        '</div>'
    );

    if ($config->get('api_key')) {
      $form['left']['settings'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('How do you want to get subscribers?'),
        '#weight' => 1,
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
      );

      $campaigns = $api->getCampaigns();
      $old_webform_ids = $new_webform_ids = array();

      if (!empty($campaigns)) {
        $c = array();

        foreach ($campaigns as $v) {
          $c[$v->campaignId] = $v->name;
        }

        $webforms = $api->getWebforms();

        foreach ($webforms as $id => $webform) {
          if ('enabled' == $webform->status) {
            $old_webform_ids[$webform->scriptUrl] = $webform->name . ' (' . $c[$webform->campaign->campaignId] . ')';
          }
        }

        $webforms = $api->getForms();

        foreach ($webforms as $id => $webform) {
          if ('published' == $webform->status) {
            $new_webform_ids[$webform->scriptUrl] = $webform->name . ' (' . $c[$webform->campaign->campaignId] . ')';
          }
        }
      }

      $form['left']['settings']['title1'] = array(
        '#type' => 'item',
        '#markup' => '<br /><h3>' . $this->t('Subscribe via a form') . '</h3>'
      );

      if (!empty($new_webform_ids) || !empty($old_webform_ids)) {
        $form_options = array();

        if (!empty($old_webform_ids) && empty($new_webform_ids)) {
          $form_options = $old_webform_ids;
        }
        elseif (!empty($new_webform_ids) && empty($old_webform_ids)) {
          $form_options = $new_webform_ids;
        }
        else {
          $form_options['New webforms'] = $new_webform_ids;
          $form_options['Old webforms'] = $old_webform_ids;
        }

        $form['left']['settings']['webform_on'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Allow subscriptions via forms'),
          '#default_value' => $config->get('webform_on')
        );

        $form['left']['settings']['script_url'] = array(
          '#type' => 'select',
          '#title' => $this->t('Select the form'),
          '#default_value' => $config->get('script_url'),
          '#options' => $form_options,
          '#description' => '<small>' . $this->t('The list is automatically grabbed from your GetResponse account.') . '</small>',
          '#attributes' => array(
            'style' => array('width: 370px;'),
          )
        );

        $form['left']['settings']['forms_hint'] = array(
          '#type' => 'item',
          '#markup' => '<div><a class="gr-hint" href="#gr-hint-forms">' . $this->t('Need help with forms?') . '</a><div id="gr-hint-forms" class="hidden"><strong>' . $this->t('How to place the form?') . '</strong><br>' . $this->t('Select a form and go to block layout panel to place the form.') . '</div></div>'
        );
      }
      else {
        $form['left']['settings']['webformid'] = array(
          '#type' => 'item',
          '#markup' => $this->t('Looks like you don’t have any forms. <a href="https://app.getresponse.com/webform_manage.html" target="_blank">Create a form</a>.'),
        );
      }

      $form['left']['settings']['title2'] = array(
        '#type' => 'item',
        '#markup' => '<br /><h3>' . $this->t('Subscribe via comments') . '</h3>',
      );

      if (!empty($campaigns)) {

        $form['left']['settings']['comment_on'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Allow subscriptions when visitors comment'),
          '#default_value' => $config->get('comment_on')
        );

        $campaign_ids = array();
        foreach ($campaigns as $id => $campaign) {
          $campaign_ids[$campaign->campaignId] = $campaign->name;
        }

        $form['left']['settings']['comment_campaign'] = array(
          '#type' => 'select',
          '#title' => $this->t('Select the campaign'),
          '#default_value' => $config->get('comment_campaign'),
          '#options' => $campaign_ids,
          '#description' => '<small>' . $this->t('The list is automatically grabbed from your GetResponse account.') . '</small>',
          '#attributes' => array(
            'style' => array('width: 370px;'),
          )
        );

        $comment_text = $config->get('comment_label');

        if (empty($comment_text)) {
          $comment_text = $this->t('Sign up for our newsletter!');
        }

        $form['left']['settings']['comment_label'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Enter signup message'),
          '#default_value' => $comment_text,
          '#size' => 0,
          '#minlength' => 7,
          '#attributes' => array(
            'style' => array('width: 370px;'),
          )
        );

        $form['left']['settings']['comment_hint'] = array(
          '#type' => 'item',
          '#markup' => '<div><a class="gr-hint" href="#gr-hint-comm">' . $this->t('Need help with comments?') . '</a><div id="gr-hint-comm" class="hidden"><strong>' . $this->t('How do I allow anonymous comments?') . '</strong><br>' . $this->t('You need to enable “view comments” and “post comments” permissions for anonymous users.') . '<br>' . $this->t('Go to ') .'<strong>' . $this->t('People > Permissions > Comments') . '</strong> ' . $this->t('to set this.') . '</div></div>',
        );

        $form['left']['settings']['title3'] = array(
          '#type' => 'item',
          '#markup' => '<br /><h3>' . $this->t('Subscribe via registration form') . '</h3>',
        );

        $form['left']['settings']['register_user'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Allow subscriptions when visitors register'),
          '#default_value' => $config->get('register_user')
        );

        $form['left']['settings']['register_campaign'] = array(
          '#type' => 'select',
          '#title' => $this->t('Select the campaign'),
          '#default_value' => $config->get('register_campaign'),
          '#options' => $campaign_ids,
          '#description' => '<small>' . $this->t('The list is automatically grabbed from your GetResponse account.') . '</small>',
          '#attributes' => array(
            'style' => array('width: 370px;'),
          )
        );

        $register_text = $config->get('register_label');

        if (empty($register_text)) {
          $register_text = $this->t('Sign up for our newsletter!');
        }

        $form['left']['settings']['register_label'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Enter signup message'),
          '#default_value' => $register_text,
          '#size' => 0,
          '#minlength' => 7,
          '#attributes' => array(
            'style' => array('width: 370px;'),
          )
        );


        $form['left']['settings']['register_hint'] = array(
          '#type' => 'item',
          '#markup' => '<div><a class="gr-hint" href="#gr-hint-reg">' . $this->t('Need help with registrations?') . '</a><div id="gr-hint-reg" class="hidden"><strong>' . $this->t('How do I enable account registration for all visitors?') . '</strong><br>' . $this->t('Go to') . ' <strong>' . $this->t('Administration > Configuration > People > Account settings.') . '</strong><br>' . $this->t('In the Registration and cancellation section select Visitors, but administrator approval is required.') .'</div></div>',
        );
      }
      else {
        $form['left']['settings']['no_campaigns'] = array(
          '#type' => 'item',
          '#markup' => $this->t('No Campaigns')
        );
      }
    }

    $form['right']['rss'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Check out what\'s new on our blog'),
      '#weight' => 2,
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => array(
        'class' => array('gr-fieldset-legend'),
      )
    );

    $rss = new Rss();

    $form['right']['rss']['content'] = array(
      '#type' => 'item',
      '#markup' => $rss->renderRss(10)
    );

    $form['right']['social'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Follow GetResponse'),
      '#weight' => 3,
      '#collapsible' => FALSE,
      '#collapsed' => FALSE
    );

    $form['right']['social']['facebook'] = array(
      '#type' => 'item',
      '#markup' => \Drupal::service('link_generator')->generate(
        $this->t('Facebook'),
        Url::fromUri('http://www.facebook.com/getresponse')
      )
    );

    $form['right']['social']['twitter'] = array(
      '#type' => 'item',
      '#markup' => \Drupal::service('link_generator')->generate(
        $this->t('Twitter'),
        Url::fromUri('http://twitter.com/getresponse')
      )
    );

    $form['right']['social']['linkedin'] = array(
      '#type' => 'item',
      '#markup' => \Drupal::service('link_generator')->generate(
        $this->t('LinkedIn'),
        Url::fromUri('https://www.linkedin.com/company/getresponse')
      )
    );

    $form['right']['social']['blog'] = array(
      '#type' => 'item',
      '#markup' => \Drupal::service('link_generator')->generate(
        $this->t('Blog'),
        Url::fromUri('http://blog.getresponse.com')
      )
    );
    $return = parent::buildForm($form, $form_state);
    $return['actions']['submit']['#value'] = $this->t('Save subscription settings');
    $return['actions']['submit']['#attributes'] = array('style' =>
      array('display:none'));

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $api_key = $this->config('getresponse.settings')->get('api_key');
    $input = $form_state->getUserInput();
    $api_url = $input['api_url'];
    $domain = $input['domain'];

    $isEnterprise = (bool) $input['is_enterprise'];

    if ($isEnterprise) {

      if (!in_array($api_url, array(self::API_URL_360_PL, self::API_URL_360_COM))) {
        $form_state->setErrorByName('api_url', $this->t('Invalid type.'));
      }

      if (empty($domain)) {
        $form_state->setErrorByName('domain', $this->t('Invalid domain.'));
      }

    } else {
      $api_url = self::API_URL_SMB;
      $domain = null;
    }

    if (empty($input['api_key']) && empty($api_key)) {
      $form_state->setErrorByName('api_key', $this->t('Empty api key.'));
    }

    if (empty($api_key) && $api_key != $input['api_key']) {
      $api = new Api($input['api_key'], $api_url, $domain);
      $ping = $api->ping();

      if (isset($ping->code)) {
        $form_state->setErrorByName('api_key',
          $this->t('The API key seems incorrect. Please check if you typed or pasted it correctly. If you recently generated a new key, please make sure you’re using the right one.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('getresponse.settings');
    $current_api_key = $config->get('api_key');

    // update credentials only, if api key not exists in database.
    if (empty($current_api_key)) {
      $config->set('api_key', $form_state->getValue('api_key'));

      if ((bool) $form_state->getValue('is_enterprise')) {
        $config->set('domain', $form_state->getValue('domain'));
        $config->set('api_url', $form_state->getValue('api_url'));
      }
    }

    $config->set('webform_on', $form_state->getValue('webform_on'))
        ->set('script_url', $form_state->getValue('script_url'))
        ->set('comment_on', $form_state->getValue('comment_on'))
        ->set('comment_label', $form_state->getValue('comment_label'))
        ->set('comment_campaign', $form_state->getValue('comment_campaign'))
        ->set('register_user', $form_state->getValue('register_user'))
        ->set('register_label', $form_state->getValue('register_label'))
        ->set('register_campaign', $form_state->getValue('register_campaign'))
        ->save();

    $actual_form = $config->get('script_url');

    if ($actual_form != $form_state->getValue('script_url')) {
      drupal_flush_all_caches();
    }

    unset($_SESSION['messages']);
    if ($config->get('api_key')) {
      drupal_set_message($this->t('Subscription settings successfully saved.'));
    } else {
      drupal_set_message($this->t('You connected your Drupal to GetResponse.'));
    }
  }
}