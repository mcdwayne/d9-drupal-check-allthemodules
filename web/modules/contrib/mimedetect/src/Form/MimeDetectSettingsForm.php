<?php

namespace Drupal\mimedetect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mimedetect\MimeDetectService;

/**
 * Configure MimeDetect settings for this site.
 */
class MimeDetectSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mimedetect_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mimedetect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mimedetect.settings');

    // Mime detection engines.
    $form['engines'] = [
      '#type'          => 'fieldgroup',
      '#title'         => $this->t('MIME detection engines'),
    ];

    // PHP Fileinfo.
    $form['engines']['fileinfo_enable'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('PHP fileinfo'),
      '#default_value' => $config->get('fileinfo.enable'),
      '#description'   => $this->t('Use the <a href="@url">PHP file information extension</a>. This is the preferred method.', ['@url' => 'http://php.net/manual/en/book.fileinfo.php']),
    ];

    // UNIX file command.
    $form['engines']['unixfile_enable'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('UNIX file'),
      '#default_value' => $config->get('unixfile.enable'),
      '#description'   => $this->t('System call to the file command. Used when PHP fileinfo fails or is not available.'),
    ];

    $form['engines']['unixfile_binary'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Path to file command executable'),
      '#default_value' => $config->get('unixfile.binary') ?: '/usr/bin/file',
      '#description'   => $this->t("The path to the executable 'file' binary."),
      '#states'        => [
        'visible'        => [
          ':input[name="unixfile_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Default file name extension mapping.
    $form['engines']['fileextension_enable'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('File extension'),
      '#default_value' => TRUE,
      '#disabled'      => TRUE,
      '#description'   => $this->t('MIME detection based on filename extension. This is the system default method, used as fall back when all others fail or are not available.'),
    ];

    // Custom MIME 'magic' file.
    $form['magicfile'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t("Custom 'magic' file path"),
      '#default_value' => $config->get('magicfile'),
      '#description'   => $this->t('Used by any magic based engine. Leave blank to rely on system magic file or PHP internal info.'),
      '#states'        => [
        'enable'      => [
          [
            ':input[name="fileinfo_enable"]' => ['checked' => TRUE],
          ],
          [
            ':input[name="unixfile_enable"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $msg = '';

    // Test custom magic file path.
    $magic_file = $form_state->getValue('magicfile');
    if (!empty($magic_file) && !MimeDetectService::checkMagicfile($form_state->getValue('magicfile'), $msg)) {
      $form_state->setErrorByName('magicfile', $msg);
      return;
    }

    // Test fileinfo settings.
    if ($form_state->getValue('fileinfo_enable') &&
      !MimeDetectService::checkFileinfo($magic_file, $msg)) {
      if (!empty($magic_file)) {
        $form_state->setErrorByName('magicfile', $msg);
      }
      else {
        $form_state->setErrorByName('fileinfo_enable', $msg);
      }
    }

    // Test file binary settings.
    if ($form_state->getValue('unixfile_enable') &&
      !MimeDetectService::checkUnixfile($form_state->getValue('unixfile_binary'), $magic_file, $msg)) {
      if (!empty($magic_file)) {
        $form_state->setErrorByName('magicfile', $msg);
      }
      else {
        $form_state->setErrorByName('unixfile_binary', $msg);
      }
    }

    // Warning about no real MIME detection enable.
    if (!$form_state->getValue('fileinfo_enable') && !$form_state->getValue('unixfile_enable')) {
      drupal_set_message($this->t("MimeDetect is using the browser supplied filename for file extension lookups. It is strongly recommended that you install and configure the PHP Fileinfo Extension or the UNIX 'file' command to provide more accurate sever-side mime type detection."), 'warning');
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save settings changes.
    $config = $this->config('mimedetect.settings');

    $config->set('fileinfo.enable', $form_state->getValue('fileinfo_enable'));
    $config->set('unixfile.enable', $form_state->getValue('unixfile_enable'));
    $config->set('unixfile.binary', $form_state->getValue('unixfile_binary'));
    $config->set('magicfile', $form_state->getValue('magicfile'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
