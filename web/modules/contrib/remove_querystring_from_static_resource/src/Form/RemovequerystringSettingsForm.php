<?php

namespace Drupal\removequerystring\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Middleware to alter REST responses.
 */
class RemovequerystringSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'removequerystring_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'removequerystring.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = \Drupal::config('removequerystring.settings');
    $options = [
      "script" => "  SRC of Script Tag ",
      "css" => "  HREF of Link Tag [ if rel is empty OR rel='stylesheet' ]",
      "images" => "  SRC of Img Tag ",
    ];
    $chk_val = $config->get('removequerystring_appid');
    if ($chk_val == "") {
      $chk_val = [];
    }
    $form['removequerystring_appid'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => '',
      '#default_value' => $chk_val,
      '#prefix' => '<strong>Select checkbox where you want to remove query string.</strong> ',
    ];
    $form['removequerystring_exclude_javascript'] = [
      '#type' => 'textarea',
      '#title' => '',
      '#default_value' => $config->get('removequerystring_exclude_javascript'),
      '#prefix' => '<strong>Add comma separated url which you want to exclude for javascript.</strong> ',
      '#suffix' => "<font style='font-size:10px'><strong>Example:</strong> <br>http://www.anydomain.com/js/*<br>*/yourfolder/other-folder/myjs.js?queryparam=1<br>*/yourfolder/other-folder/*<br>http://www.anydomain.com/js/yourfolder/other-folder/myjs.js?queryparam=1</font><br><br><hr><br>",

    ];
    $form['removequerystring_exclude_css'] = [
      '#type' => 'textarea',
      '#title' => '',
      '#default_value' => $config->get('removequerystring_exclude_css'),
      '#prefix' => '<strong>Add comma separated url which you want to exclude for Link tag.</strong> ',
      '#suffix' => "<font style='font-size:10px'><strong>Example:</strong> <br>http://www.anydomain.com/css/*<br>*/yourfolder/other-folder/style.css?queryparam=1<br>*/yourfolder/other-folder/*<br>http://www.anydomain.com/css/yourfolder/other-folder/style.css?queryparam=1</font><br><br><hr><br>",
    ];
    $form['removequerystring_exclude_image'] = [
      '#type' => 'textarea',
      '#title' => '',
      '#default_value' => $config->get('removequerystring_exclude_image'),
      '#prefix' => '<strong>Add comma separated url which you want to exclude for image.</strong> ',
      '#suffix' => "<font style='font-size:10px'><strong>Example:</strong> <br>http://www.anydomain.com/img/*<br>*/yourfolder/other-folder/myimage.jpg?queryparam=1<br>*/yourfolder/other-folder/*<br>http://www.anydomain.com/images/yourfolder/other-folder/screen.png?queryparam=1</font>",
    ];
    return @parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('removequerystring.settings')->set('removequerystring_appid', $form_state->getValue('removequerystring_appid'))->save();
    $this->config('removequerystring.settings')->set('removequerystring_exclude_javascript', $form_state->getValue('removequerystring_exclude_javascript'))->save();
    $this->config('removequerystring.settings')->set('removequerystring_exclude_css', $form_state->getValue('removequerystring_exclude_css'))->save();
    $this->config('removequerystring.settings')->set('removequerystring_exclude_image', $form_state->getValue('removequerystring_exclude_image'))->save();

    @parent::submitForm($form, $form_state);
  }

}
