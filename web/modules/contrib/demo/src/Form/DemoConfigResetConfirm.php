<?php

namespace Drupal\demo\Form;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * This class return the form demo_config_reset_confirm to make sure that you are about to reset your configuration.
 */
class DemoConfigResetConfirm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'demo_config_reset_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['dump'] = demo_get_config_dumps();

    drupal_set_message(t('This action cannot be undone.'), 'warning');

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Reset now'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Reset site to chosen snapshot.
    if ($path = $form_state->getValue('filename')) {
      try {
        $archiver = new ArchiveTar($path, 'gz');
        $files = [];
        foreach ($archiver->listContent() as $file) {
          $files[] = $file['filename'];
        }

        $archiver->extractList($files, config_get_config_directory(CONFIG_SYNC_DIRECTORY));

        drupal_set_message($this->t('Your configuration files were successfully uploaded and are ready for import.'));

        $form_state->setRedirect('demo.config_sync');
      }
      catch (\Exception $e) {
        drupal_set_message($this->t('Could not extract the contents of the tar file. The error message is <em>@message</em>', ['@message' => $e->getMessage()]), 'error');
      }
    }
  }
    // Do not redirect from the reset confirmation form by default, as it is
    // likely that the user wants to reset all over again (e.g., keeping the
    // browser tab open).
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('demo.manage_form');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to reset the site?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

}
