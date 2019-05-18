<?php

namespace Drupal\file_downloader;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;


/**
 * Interface DownloadOptionPluginInterface
 *
 * @package Drupal\file_downloader
 */
interface DownloadOptionPluginInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface, CacheableDependencyInterface, DerivativeInspectionInterface{

  /**
   * @param \Drupal\file\FileInterface $file
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function deliver(FileInterface $file);

  /**
   * Returns the configuration form elements specific to this download option plugin.
   *
   * Download options that need to add form elements to the normal download option configuration
   * form should implement this method.
   *
   * @param array $form
   *   The form definition array for the download option configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The renderable form array representing the entire configuration form.
   */
  public function downloadOptionForm($form, FormStateInterface $form_state);

  /**
   * Adds download option specific validation for the download option form.
   *
   * Note that this method takes the form structure and form state for the full
   * download option configuration form as arguments, not just the elements defined in
   * DownloadOptionPluginInterface::downloadOptionForm().
   *
   * @param array $form
   *   The form definition array for the full download option configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\file_downloader\DownloadOptionPluginInterface::downloadOptionForm()
   * @see \Drupal\file_downloader\DownloadOptionPluginInterface::downloadOptionSubmit()
   */
  public function downloadOptionValidate($form, FormStateInterface $form_state);

  /**
   * Adds download option specific submission handling for the download option form.
   *
   * Note that this method takes the form structure and form state for the full
   * download option configuration form as arguments, not just the elements defined in
   * DownloadOptionPluginInterface::downloadOptionForm().
   *
   * @param array $form
   *   The form definition array for the full download option configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\file_downloader\DownloadOptionPluginInterface::downloadOptionForm()
   * @see \Drupal\file_downloader\DownloadOptionPluginInterface::downloadOptionValidate()
   */
  public function downloadOptionSubmit($form, FormStateInterface $form_state);

  /**
   * Check if the file to be downloaded is on the server.
   *
   * @param FileInterface $file
   * @return bool
   */
  public function downloadFileExists(FileInterface $file);

  /**
   * Validate plugin specific access on the availability of the download option.
   *
   * @param \Drupal\file_downloader\AccountInterface $account
   * @param \Drupal\file\FileInterface $file
   *
   * @return mixed
   */
  public function access(AccountInterface $account, FileInterface $file);

  /**
   * Get the uri to the file that will be offered as a download.
   *
   * @param \Drupal\file\FileInterface $file
   *
   * @return string
   */
  public function getFileUri(FileInterface $file);

}
