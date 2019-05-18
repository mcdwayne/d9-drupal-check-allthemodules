<?php
namespace Drupal\frameprevention;

class FramepreventionAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'frameprevention_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('frameprevention.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }


  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = array();

    $form['frameprevention_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable CSS and Javascript-based frame-breaker'),
      '#default_value' => variable_get('frameprevention_enabled', 0),
      '#description' => t('Based on recommended frame-breaking code from the !link. Having Javascript enabled on client web browsers will become a requirement when this is enabled, otherwise no content will display.', array('!link' => l(t('OWASP Clickjacking Defense Cheat Sheet'), 'https://www.owasp.org/index.php/Clickjacking_Defense_Cheat_Sheet'))),
    );
    $form['frameprevention_x_frame_options'] = array(
      '#title' => t('X-Frame-Options HTTP response header'),
      '#type' => 'select',
      '#options' => array(
        '' => t('disabled'),
        'DENY' => 'DENY',
        'SAMEORIGIN' => 'SAMEORIGIN',
      ),
      '#description' => t('DENY prevents any domain from framing the content. SAMEORIGIN allows the current site to frame the content.'),
      '#default_value' => variable_get('frameprevention_x_frame_options', 'SAMEORIGIN'),
    );
    $form['frameprevention_pages'] = array(
      '#type' => 'textarea',
      '#title' => t('Pages to ignore'),
      '#default_value' => variable_get('frameprevention_pages', "img_assist/*\nfile/ajax/*"),
      '#cols' => 30,
      '#rows' => 5,
      '#description' => t('List of pages where the module is <b>disabled</b>. Use the <em>*</em> character for wildcard, each entry in a new line.'),
    );

    return parent::buildForm($form, $form_state);
  }
}
