<?php

/**
 * @file
 * Contains \Drupal\measuremail\Form\MeasuremailSubscribeForm.
 */

namespace Drupal\measuremail\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MeasuremailSubscribeForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\measuremail\MeasuremailInterface
   */
  protected $entity;

  /**
   * The measuremail entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $measuremailStorage;

  /**
   * Constructs a base class for measuremail elements add and edit forms.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $measuremail_storage
   *   The measuremail entity storage.
   */
  public function __construct(EntityStorageInterface $measuremail_storage) {
    $this->measuremailStorage = $measuremail_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('measuremail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\measuremail\MeasuremailInterface $measuremail */
    $measuremail = $this->entity;

    $settings = $measuremail->getSettings();
    $email_field = $settings['email_field'];
    $email_exist = FALSE;
    $subscription_url = $this->getRequest()->getUri(); // The current URL.
    $form = [];

    if (empty($settings['id']) || empty($settings['endpoint'])) {
      $form['error'] = [
        '#markup' => t('The site administrator has not yet configured a Measuremail URL and Subscription ID.'),
      ];
      return $form;
    }

    // Display the form Title.
    $form['title'] = [
      '#markup' => '<h1>' . t($measuremail->label()) . '</h1>',
    ];

    $language = \Drupal::languageManager()->getCurrentLanguage();
    $subscription_url = $this->getRequest()->getUri();

    if ($settings['callback_type'] == 'newpage') {
      if (UrlHelper::isExternal($settings['callback_url'])) {
        $callback_url = $settings['callback_url'];
      } else {
        // The internal link should be absolute cause it will be sent to measuremail.
        $callback_url = Url::fromUri('internal:' . $settings['callback_url'], ['absolute' => TRUE])->toString();
      }
    }

    // Perform a language check, as some sites may want to disable the newsletter
    // for given languages.
    $languages_enabled = $settings['languages_enabled'];
    if (\Drupal::moduleHandler()->moduleExists('language') && !empty(array_filter($languages_enabled))) {
      if (!in_array($language->getId(), array_filter($languages_enabled))) {
        $language_links = [
          '#theme' => 'links',
          '#attributes' => [
            'class' => [
              'links',
            ],
          ],
        ];
        foreach ($languages_enabled as $language_enabled) {
          $list = \Drupal::languageManager()->getLanguages();
          if (isset($list[$language_enabled])) {
            $languageaware_url = URL::fromUri('internal:' . \Drupal::service('path.current')
                ->getPath(), ['language' => $list[$language_enabled]]);
            $language_links['#links'][] = [
              'title' => $list[$language_enabled]->getName(),
              'url' => $languageaware_url,
            ];
          }
        }
        $form['error'] = [
          '#markup' => t('Please choose a newsletter language: @languages', ['@languages' => render($language_links)]),
        ];

        return $form;
      }
    }

    $fields = $measuremail->getElements();

    if (!empty($fields)) {

      /** @var \Drupal\measuremail\MeasuremailElementsInterface $field */
      foreach ($fields as $field) {

        $field_configuration = $field->getConfiguration()['data'];
        $field_measuremail_id = $field_configuration['id'];

        $form[$field_measuremail_id] = $field->render();


        // emailaddress is a required field, so it needs a special treatment.
        if ($field_measuremail_id === $email_field) {
          $form[$field_measuremail_id] = [
            '#type' => $field->getPluginId(),
            '#title' => t($field_configuration['label']),
            '#default_value' => t($field_configuration['default_value']),
            '#required' => ($field_configuration['required'] || $field_measuremail_id === $email_field) ? TRUE : FALSE,
          ];
          $email_exist = TRUE;
        }
        else {
          $form[$field_measuremail_id] = $field->render();
        }
      }
    }

    // The email is required whatsoever, so add it if not listed in the admin.
    if (!$email_exist) {
      $form[$email_field] = [
        '#type' => 'textfield',
        '#title' => t('Email'),
        '#required' => TRUE,
      ];
    }

    // Required fields to be addded.
    $form['subscription'] = [
      '#type' => 'hidden',
      '#value' => $settings['id'],
    ];
    $form['callbackurl'] = [
      '#type' => 'hidden',
      '#value' => (isset($callback_url)) ? $callback_url : $subscription_url,
    ];

    // GDPR Required fields.
    $form['metadata.FormUrl'] = [
      '#type' => 'hidden',
      '#value' => $subscription_url,
      '#attributes' => ['id' => 'FormUrl'],
    ];
    $form['metadata.FormVersion'] = [
      '#type' => 'hidden',
      '#value' => $settings['formversion'],
      '#attributes' => ['id' => 'FormVersion'],
    ];
    $form['metadata.PrivacyUrl'] = [
      '#type' => 'hidden',
      '#value' => $settings['privacyurl'],
      '#attributes' => ['id' => 'PrivacyUrl'],
    ];
    $form['metadata.PrivacyVersion'] = [
      '#type' => 'hidden',
      '#value' => $settings['privacyversion'],
      '#attributes' => ['id' => 'PrivacyVersion'],
    ];

    $submit_text = $settings['submit_button'];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t($submit_text),
    ];

    // Extra: add honeypot protection if the module is enabled.
    if (\Drupal::moduleHandler()->moduleExists('honeypot')) {
      honeypot_add_form_protection($form, $form_state, [
        'honeypot',
        'time_restriction',
      ]);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\measuremail\MeasuremailInterface $measuremail */
    $measuremail = $this->entity;
    $settings = $measuremail->getSettings();
    $email_field = $settings['email_field'];

    if (!\Drupal::service('email.validator')
      ->isValid(trim($form_state->getValue($email_field)), TRUE, TRUE)) {
      $form_state->setErrorByName('email', t('Please enter a valid email address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\measuremail\MeasuremailInterface $measuremail */
    $measuremail = $this->entity;
    $settings = $measuremail->getSettings();
    $email_field = $settings['email_field'];
    $endpoint = $settings['endpoint'];
    /** @var \Drupal\measuremail\MeasuremailElementsPluginCollection $fields */
    $fields = $measuremail->getElements();
    $submitted_fields = $form_state->getValues();

    if (!empty($fields)) {
      /** @var \Drupal\measuremail\Plugin\MeasuremailElementsBase $field */
      foreach ($fields as $field) {
        $pluginId = $field->getPluginId();
        $field = $field->getConfiguration();
        $field_id = $field['data']['id'];

        if ($field_id == $email_field) {
          continue;
        }

        if (array_key_exists($field_id, $submitted_fields)) {
          // Measuremail doesn't accept array inputs, let's clear them here.
          if (is_array($submitted_fields[$field_id])) {
            $submitted_fields[$field_id] = implode(' | ', $submitted_fields[$field_id]);
          }

          // Measuremail requires that checkbox and radio values are "true" or "false" strings.
          if (in_array($pluginId, ['checkbox', 'radios'])) {
            if (is_array($submitted_fields[$field_id])) {
              $submitted_fields[$field_id] = (reset($submitted_fields[$field_id])) ? 'true' : 'false';
            } else {
              $submitted_fields[$field_id] = ($submitted_fields[$field_id]) ? 'true' : 'false';
            }
          }

          $submitted_fields['modl.customer.' . $field_id] = $submitted_fields[$field_id];
          unset($submitted_fields[$field_id]);
        }
      }
    }

    $options = [
      'form_params' => $submitted_fields,
      'timeout' => 30,
      'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
      'allow_redirects' => FALSE,
    ];

    $client = \Drupal::httpClient();
    try {
      $request = $client->request('POST', $endpoint, $options);
      parse_str(parse_url($request->getHeaderLine('Location'), PHP_URL_QUERY), $query_params);
      $result = $query_params['result'];
    } catch (GuzzleException $e) {
      drupal_set_message($settings['message_error'], 'error');
      return FALSE;
    }

    $message_return_type = $settings['callback_type'];
    if ($message_return_type == 'newpage') {
      // Redirect to callback URL
      $url = $request->getHeaderLine('Location');
      if (UrlHelper::isValid($url, TRUE)) {
        $response = new RedirectResponse($url, 302);
        $response->send();
      }
    }
    else {
      if ($message_return_type == 'inlinemessage') {
        // Set drupal message.
        if (in_array($result, [1])) {
          drupal_set_message($settings['message_success'], 'status');
        } else if (in_array($result, [2])) {
          drupal_set_message($settings['message_update'], 'status');
        }
        else {
          drupal_set_message($settings['message_error'], 'error');
          return FALSE;
        }
      }
    }

    return TRUE;
  }

}
