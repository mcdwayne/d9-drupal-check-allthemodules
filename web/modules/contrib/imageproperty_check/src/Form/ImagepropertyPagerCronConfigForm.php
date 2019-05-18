<?php

/**
 * @file
 * Contains \Drupal\time_spent\Form\TimeSpentConfigForm.
 */

namespace Drupal\imageproperty_check\Form;
use Drupal\Core\Form\ConfigFormBase;
//use Drupal\node\Entity\NodeType;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

class ImagepropertyPagerCronConfigForm extends ConfigFormBase {
  public function getFormId() {
    return 'imageproperty_check_pager_cron_config_form';
  }
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
  $form['html'] = array(
    '#type' => 'markup',
    '#markup' => t('Pager and Cron Configuration'),
  );
  $form['imageproperty_check_pager'] = array(
    '#type' => 'textfield',
    '#title' => 'Pager configuration',
    '#description' => t("Number of images to be displayed on a page"),
    //'#default_value' => variable_get('imageproperty_check_pager', 10),
    '#default_value' => $this->config('imageproperty_check_pager_cron.settings')->get('imageproperty_check_pager'),
  );
  $form['imageproperty_check_cron'] = array(
    '#type' => 'textfield',
    '#title' => 'Cron hour configuration',
    '#description' => t("Number of hours after which mail should be sent"),
    '#default_value' => $this->config('imageproperty_check_pager_cron.settings')->get('imageproperty_check_cron')
  );
   return parent::buildForm($form, $form_state);
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $userInputValues = $form_state->getUserInput();
    $config = $this->configFactory->get('imageproperty_check_pager_cron.settings');
    $pager = $userInputValues['imageproperty_check_pager'];
    $cron = $userInputValues['imageproperty_check_cron'];
    $config->set('imageproperty_check_pager' , $pager);
    $config->set('imageproperty_check_cron' , $cron);
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
