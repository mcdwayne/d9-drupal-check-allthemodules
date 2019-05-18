<?php
/**
 * @file
 * Contains \Drupal\time_spent\Form\TimeSpentConfigForm.
 */

namespace Drupal\imageproperty_check\Form;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

//use Drupal\node\Entity\NodeType;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

class ImagepropertyCheckUpdateAspectRatioImages extends FormBase {
  public function getFormId() {
    return 'imageproperty_check_update_aspect_ratio_images';
  }
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
  $form = array();
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update Image Aspect Ratio Report'),
  );
  //$form['#submit'][] = 'imageproperty_checkbutton_form_submit';
    return $form;
  }


public function submitForm(array &$form, FormStateInterface $form_state) {
    return new RedirectResponse(\Drupal::url('system.run_cron'));
  }
}
