<?php

namespace Drupal\module_maker\Form;

/**
 * @file
 * Contains \Drupal\module_maker\Form\AdvanceModuleForm.
 */
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Contains \Drupal\module_maker\Form\AdvanceModuleForm.
 */
class AdvanceModuleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_module_form';
  }

  /**
   * Build Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['module_details'] = [
      '#type' => 'details',
      '#title' => t('Module Details'),
      '#open' => TRUE,
    ];
    $form['module_details']['module_name'] = [
      '#type' => 'textfield',
      '#title' => t('Module Name:'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['module-maker-module-name']],
      '#ajax' => [
        'callback' => '::nameCallback',
        'event' => 'change',
      ],
    ];
    $form['module_details']['message'] = [
      '#type' => 'hidden',
      '#prefix' => '<div id="name-validation">',
      '#suffix' => '</div">',
    ];
    $form['module_details']['package'] = [
      '#type' => 'textfield',
      '#title' => t('Package Name:'),
      '#required' => TRUE,
      '#default_value' => 'custom',
    ];
    $form['module_details']['module_description'] = [
      '#type' => 'textarea',
      '#title' => t('Module Description:'),
      '#required' => TRUE,
    ];
    $form['module_details']['module_option'] = [
      '#type' => 'radios',
      '#title' => $this->t('Enable Or Download Module'),
      '#options' => [2 => $this->t('Enable Module'), 1 => $this->t('Download Module')],
    ];
    $form['controller'] = [
      '#type' => 'details',
      '#title' => t('Controller settings'),
      '#open' => FALSE,
    ];
    $form['controller']['default_controller'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create Controller'),
    ];
    $form['controller']['default_controller_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Controller Name'),
      '#attributes' => ['class' => ['module-maker-controller-name']],
      '#states' => [
        'invisible' => [
          ':input[name="default_controller"]' => ['checked' => FALSE],
        ],
      ],
      '#ajax' => [
        'callback' => '::nameCallbackController',
        'event' => 'change',
      ],
    ];
    $form['controller']['controller_message'] = [
      '#type' => 'hidden',
      '#prefix' => '<div id="controller-name-validation">',
      '#suffix' => '</div">',
    ];
    $form['add_form'] = [
      '#type' => 'details',
      '#title' => t('Form settings'),
      '#open' => FALSE,
    ];
    $form['add_form']['default_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create Form'),
    ];
    $form['add_form']['default_form_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form Name'),
      '#attributes' => ['class' => ['module-maker-form-name']],
      '#states' => [
        'invisible' => [
          ':input[name="default_form"]' => ['checked' => FALSE],
        ],
      ],
      '#ajax' => [
        'callback' => '::nameCallbackForm',
        'event' => 'change',
      ],
    ];
    $form['add_form']['form_message'] = [
      '#type' => 'hidden',
      '#prefix' => '<div id="form-name-validation">',
      '#suffix' => '</div">',
    ];
    $form['add_block'] = [
      '#type' => 'details',
      '#title' => t('Block settings'),
      '#open' => FALSE,
    ];
    $form['add_block']['default_block'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create Block'),
    ];
    $form['add_block']['default_block_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block Name'),
      '#attributes' => ['class' => ['module-maker-block-name']],
      '#states' => [
        'invisible' => [
          ':input[name="default_block"]' => ['checked' => FALSE],
        ],
      ],
      '#ajax' => [
        'callback' => '::nameCallbackBlock',
        'event' => 'change',
      ],
    ];
    $form['add_block']['block_message'] = [
      '#type' => 'hidden',
      '#prefix' => '<div id="block-name-validation">',
      '#suffix' => '</div">',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['reset'] = [
      '#type' => 'button',
      '#button_type' => 'reset',
      '#value' => t('Clear'),
      '#validate' => [],
      '#attributes' => [
        'onclick' => 'this.form.reset(); return false;',
      ],
      '#ajax' => [
        'callback' => '::nameCallbackBlock',
        'event' => 'click',
      ],
    ];
    return $form;
  }

  /**
   * Page callback for validate form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $name = $form_state->getValue('module_name');
    if (!empty($name)) {
      $module_name = preg_replace('@[^a-z0-9-]+@', '_', strtolower($name));
      $module_dir = 'modules';
      $file_extensions = 'info.yml';
      $extensions = str_replace(" ", "|", $file_extensions);
      $files = file_scan_directory($module_dir, '/^.*\\.(' . $extensions . ')$/i');
      $filenames = [];
      foreach ($files as $file_name => $file_array) {
        if (!empty($file_array->filename)) {
          $explode_filename = explode('.info.yml', $file_array->filename);
          $filenames[] = $explode_filename[0];
        }
      }
      if (in_array($module_name, $filenames)) {
        return $form_state->setErrorByName('module_name', t('Sorry this madule name is already used.'));
      }
    }
    else {
      return $form_state->setErrorByName('module_name', t('Please give valid module name.'));
    }
    if (!empty($form_state->getValue('default_controller_name'))) {
      $controller_name = $form_state->getValue('default_controller_name');
      if (preg_match('/[^a-zA-Z0-9-_\.]/', $controller_name)) {
        return $form_state->setErrorByName('default_controller_name', t('Please enter valid Controller name,do not use spaces and special characters.'));
      }
    }
    if (!empty($form_state->getValue('default_block_name'))) {
      $block_name = $form_state->getValue('default_block_name');
      if (preg_match('/[^a-zA-Z0-9-_\.]/', $block_name)) {
        return $form_state->setErrorByName('default_controller_name', t('Please enter valid Block name,do not use spaces and special characters.'));
      }
    }
    if (!empty($form_state->getValue('default_form_name'))) {
      $form_name = $form_state->getValue('default_form_name');
      if (preg_match('/[^a-zA-Z0-9-_\.]/', $form_name)) {
        return $form_state->setErrorByName('default_controller_name', t('Please enter valid Form name,do not use spaces and special characters.'));
      }
    }
  }

  /**
   * Page callback ajax.
   */
  public function nameCallback(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('module_name');
    $module_name = preg_replace('@[^a-z0-9-]+@', '_', strtolower($name));
    $module_dir = 'modules';
    $file_extensions = 'info.yml';
    $extensions = str_replace(" ", "|", $file_extensions);
    $files = file_scan_directory($module_dir, '/^.*\\.(' . $extensions . ')$/i');
    $filenames = [];
    foreach ($files as $file_name => $file_array) {
      if (!empty($file_array->filename)) {
        $explode_filename = explode('.info.yml', $file_array->filename);
        $filenames[] = $explode_filename[0];
      }
    }
    if (in_array($module_name, $filenames)) {
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new InvokeCommand('.module-maker-module-name', 'addClass', ['error']));
      $ajax_response->addCommand(new HtmlCommand('#name-validation', '<b>Machine Name:' . $module_name . '</b>' . '<p style="color:red">Sorry this madule name is already used.</p>'));
      return $ajax_response;
    }
    else {
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new InvokeCommand('.module-maker-module-name', 'removeClass', ['error']));
      $ajax_response->addCommand(new HtmlCommand('#name-validation', '<b>Machine Name:' . $module_name . '</b>'));
      return $ajax_response;
    }
  }

  /**
   * Callback for nameCallbackController.
   */
  public function nameCallbackController(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('default_controller_name'))) {
      $controller_name = $form_state->getValue('default_controller_name');
      if (preg_match('/[^a-zA-Z0-9-_\.]/', $controller_name) || !ctype_upper($controller_name{0})) {
        $ajax_response = new AjaxResponse();
        $ajax_response->addCommand(new InvokeCommand('.module-maker-controller-name', 'addClass', ['error']));
        $ajax_response->addCommand(new HtmlCommand('#controller-name-validation', '<p style="color:red">Please Do not use spaces,special characters and first letter must be Capital.</p>'));
        return $ajax_response;
      }
      else {
        $ajax_response = new AjaxResponse();
        $ajax_response->addCommand(new InvokeCommand('.module-maker-controller-name', 'removeClass', ['error']));
        $ajax_response->addCommand(new HtmlCommand('#controller-name-validation', ''));
        return $ajax_response;
      }
    }
  }

  /**
   * Callback for nameCallbackBlock.
   */
  public function nameCallbackBlock(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('default_block_name'))) {
      $block_name = $form_state->getValue('default_block_name');
      if (preg_match('/[^a-zA-Z0-9-_\.]/', $block_name) || !ctype_upper($block_name{0})) {
        $ajax_response3 = new AjaxResponse();
        $ajax_response3->addCommand(new InvokeCommand('.module-maker-block-name', 'addClass', ['error']));
        $ajax_response3->addCommand(new HtmlCommand('#block-name-validation', '<p style="color:red">Please Do not use spaces,special characters and first letter must be Capital.</p>'));
        return $ajax_response3;
      }
      else {
        $ajax_response3 = new AjaxResponse();
        $ajax_response3->addCommand(new InvokeCommand('.module-maker-block-name', 'removeClass', ['error']));
        $ajax_response3->addCommand(new HtmlCommand('#block-name-validation', ''));
        return $ajax_response3;
      }
    }
  }

  /**
   * Callback for nameCallbackForm.
   */
  public function nameCallbackForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('default_form_name'))) {
      $form_name = $form_state->getValue('default_form_name');
      if (preg_match('/[^a-zA-Z0-9-_\.]/', $form_name) || !ctype_upper($form_name{0})) {
        $ajax_response2 = new AjaxResponse();
        $ajax_response2->addCommand(new InvokeCommand('.module-maker-form-name', 'addClass', ['error']));
        $ajax_response2->addCommand(new HtmlCommand('#form-name-validation', '<p style="color:red">Please Do not use spaces,special characters and first letter must be Capital.</p>'));
        return $ajax_response2;
      }
      else {
        $ajax_response2 = new AjaxResponse();
        $ajax_response2->addCommand(new InvokeCommand('.module-maker-form-name', 'removeClass', ['error']));
        $ajax_response2->addCommand(new HtmlCommand('#form-name-validation', ''));
        return $ajax_response2;
      }
    }
  }

  /**
   * Callback for submitForm.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    module_maker_build_module($values);
  }

}
