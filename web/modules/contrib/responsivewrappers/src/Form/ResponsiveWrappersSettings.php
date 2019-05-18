<?php

namespace Drupal\responsivewrappers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Responsive wrappers settings.
 */
class ResponsiveWrappersSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'responsivewrappers_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['responsivewrappers.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('responsivewrappers.settings');

    $form['responsivewrappers_version'] = [
      '#type' => 'select',
      '#title' => $this->t('Bootstrap output version'),
      '#options' => [
        3 => $this->t('Bootstrap 3'),
        4 => $this->t('Bootstrap 4'),
      ],
      '#default_value' => $config->get('version'),
      '#description' => $this->t('The HTML output for responsive images or tables has changed between Bootstrap 3 and Bootstrap 4. You can choose witch output version you want, for example, bootstrap 3 use img-responsive class and bootstrap 4 img-fluid.'),
    ];
    $form['responsivewrappers_add_css'] = [
      '#type' => 'select',
      '#title' => $this->t('Attach responsive wrappers CSS'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('add_css'),
      '#description' => $this->t('If you are using a Bootstrap 3/4 theme or subtheme the responsive classes works without any extra CSS, if not you can use this to add the CSS styles needed to work.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('responsivewrappers.settings');
    $config
      ->set('add_css', $form_state->getValue('responsivewrappers_add_css'))
      ->set('version', $form_state->getValue('responsivewrappers_version'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
