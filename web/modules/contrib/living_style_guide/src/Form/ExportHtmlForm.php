<?php

namespace Drupal\living_style_guide\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\living_style_guide\Controller\StyleGuideController;

/**
 * Class ExportHtmlForm.
 *
 * @package Drupal\living_style_guide\Form
 */
class ExportHtmlForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'living_style_guide.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'living_style_guide_export_html_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['actions']['submit']['#value'] = $this->t('Export HTML');

    $styleGuideController = StyleGuideController::create(\Drupal::getContainer());
    $entityTypes = $styleGuideController->getEntityTypes();
    $bundles = $styleGuideController->getAllBundles();
    $options = [];

    foreach ($entityTypes as $entityType) {
      foreach (array_keys($bundles[$entityType]) as $bundle) {
        $options[$entityType . '&' . $bundle] = '[' . $entityType . '] ' . $bundle;
      }
    }

    $form['bundles'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Bundles'),
    ];

    $config = $this->config('living_style_guide.settings');

    $form['destination_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination directory'),
      '#default_value' => $config->get('destination_directory'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = $form_state->getValue('bundles');
    $bundles = array_filter($bundles);
    $destinationDirectory = $form_state->getValue('destination_directory');

    $config = $this->config('living_style_guide.settings');
    $config->set('destination_directory', $destinationDirectory)->save();

    try {
      $this->createDirectoryIfNotExists($destinationDirectory);
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName('destination_directory');
      return;
    }

    $styleGuideController = StyleGuideController::create(\Drupal::getContainer());

    foreach ($bundles as $bundle) {
      $separatedBundleType = explode('&', $bundle);

      try {
        $content = $styleGuideController->getHtmlGuide($separatedBundleType[0], $separatedBundleType[1]);
      }
      catch (\Exception $exception) {
        drupal_set_message($this->t("Something went wrong while exporting '[@type] @bundle', the error message has been logged", [
          '@type' => $separatedBundleType[0],
          '@bundle' => $separatedBundleType[1],
        ]), 'error');

        $this->logger('living_style_guide')->error($exception);

        return;
      }

      $patternHttp = "/[a-zA-Z0-9\/]+\/http\//";
      $patternHttps = "/[a-zA-Z0-9\/]+\/https\//";
      $replaceHttp = "http://";
      $replaceHttps = "https://";

      $content = preg_replace([$patternHttp, $patternHttps], [$replaceHttp, $replaceHttps], $content);

      file_put_contents($destinationDirectory . '/' . $bundle . '.html', $content);
    }

    drupal_set_message($this->t('Successfully exported HTML'));
  }

  /**
   * Creates a directory if it doesn't exist already.
   *
   * @param string $path
   *   The directory path.
   *
   * @throws \Exception
   *   If directory doesn't exist and can't be created.
   */
  private function createDirectoryIfNotExists($path) {
    if (is_dir($path)) {
      return;
    }

    $success = mkdir($path, 0777, TRUE);

    if ($success) {
      drupal_set_message($this->t('Successfully created directory at @path', ['@path' => $path]));
    }
    else {
      drupal_set_message($this->t('Could not create directory at @path', ['@path' => $path]), 'error');

      throw new \Exception('Could not create directory at path: ' . $path);
    }
  }

}
