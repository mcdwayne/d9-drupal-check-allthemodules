<?php

namespace Drupal\extlinker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Defines a form that configures external link filter settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'extlinker_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'extlinker.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('extlinker.settings');

    $form['extlinker_domains'] = [
      '#type' => 'textarea',
      '#default_value' => implode("\n", $config->get('extlinker_domains')),
      '#title' => $this->t('Internal domains'),
      '#description' => $this->t('If your site spans multiple domains, specify
        each of them on a new line to prevent them from being seen as external
        sites. Make sure to include the right protocol; %example_right, and not
        %example_wrong, for instance. Asterisks are wildcards.', [
          '%example_right' => 'http://example.com',
          '%example_wrong' => 'example.com',
        ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $values = preg_split('#\s#', $values['extlinker_domains']);
    $domains = [];
    $errors = [];
    foreach ($values as $value) {
      // Remove trailing slashes, because not all users will use
      // those for their links.
      $value = trim($value, '/');
      if (strlen($value)) {
        if (!UrlHelper::isExternal($value)) {
          $errors[] = $value;
        }
        $domains[] = $value;
      }
    }
    if ($errors) {
      $form_state->setErrorByName('extlinker_domains', $this->formatPlural(count($errors), '%domain is not a valid external domain.', '%domain are no valid external domains', [
        '%domain' => implode(', ', $errors),
      ]));
    }
    else {
      $form_state->setValue('extlinker_domains', array_unique($domains));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('extlinker.settings')
      ->set('extlinker_domains', $values['extlinker_domains'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
