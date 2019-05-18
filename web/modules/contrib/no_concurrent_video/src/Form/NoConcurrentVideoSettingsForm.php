<?php

namespace Drupal\no_concurrent_video\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Configure example settings for this site.
*/
class NoConcurrentVideoSettingsForm extends ConfigFormBase {
/**
* {@inheritdoc}
*/
public function getFormId() {
return 'no_concurrent_video_admin_settings';
}

/**
* {@inheritdoc}
*/
protected function getEditableConfigNames() {
return [
'no_concurrent_video.settings',
];
}

/**
* {@inheritdoc}
*/
public function buildForm(array $form, FormStateInterface $form_state) {
$config = $this->config('no_concurrent_video.settings');

$form['video_class'] = array(
'#type' => 'textfield',
'#title' => $this->t("Class name to use for video tag."),
'#default_value' => $config->get('video_class'),
);

return parent::buildForm($form, $form_state);
}

/**
* {@inheritdoc}
*/
public function submitForm(array &$form, FormStateInterface $form_state) {
  $this->config('no_concurrent_video.settings')
  ->set('video_class', $form_state->getValue('video_class'))
  ->save();

  parent::submitForm($form, $form_state);
}
}
