<?php

namespace Drupal\elf\Form;

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
    return 'elf_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'elf.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('elf.settings');

    $form['elf_domains'] = [
      '#type' => 'textarea',
      '#default_value' => implode("\n", $config->get('elf_domains')),
      '#title' => $this->t('Internal domains'),
      '#description' => $this->t('If your site spans multiple domains, specify
        each of them on a new line to prevent them from being seen as external 
        sites. Make sure to include the right protocol; %example_right, and not 
        %example_wrong, for instance. Asterisks are wildcards.', [
        '%example_right' => 'http://example.com',
        '%example_wrong' => 'example.com',
      ]),
    ];
    $form['elf_window'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('elf_window'),
      '#title' => $this->t('Use JavaScript to open external links in a new window'),
    ];
    $form['elf_redirect'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('elf_redirect'),
      '#title' => $this->t('Redirect users to external websites via %url_path.', [
        '%url_path' => '/elf/redirect',
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $values = preg_split('#\s#', $values['elf_domains']);
    $domains = [];
    $errors = [];
    foreach ($values as $value) {
      // Remove trailing slashes, because not all users will use those for their links.
      $value = trim($value, '/');
      if (strlen($value)) {
        if (!UrlHelper::isExternal($value)) {
          $errors[] = $value;
        }
        $domains[] = $value;
      }
    }
    if ($errors) {
      $form_state->setErrorByName('elf_domains', $this->formatPlural(count($errors), '%domain is not a valid external domain.', '%domain are no valid external domains', [
        '%domain' => implode(', ', $errors),
      ]));
    }
    else {
      $form_state->setValue('elf_domains', array_unique($domains));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('elf.settings')
      ->set('elf_domains', $values['elf_domains'])
      ->set('elf_window', $values['elf_window'])
      ->set('elf_redirect', $values['elf_redirect'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
