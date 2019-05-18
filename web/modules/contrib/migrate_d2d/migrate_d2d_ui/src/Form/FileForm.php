<?php

namespace Drupal\migrate_d2d_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Simple wizard step form.
 */
class FileForm extends DrupalMigrateForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_d2d_file_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    if ($this->connection($form_state)->schema()->tableExists('file_managed')) {
      $file_table = 'file_managed';
    }
    else {
      $file_table = 'files';
    }

    $file_count = $this->connection($form_state)->select($file_table, 'f')
      ->fields('f', array('fid'))
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($file_count) {
      if ($cached_values['user_migration'] && $cached_values['version'] == 6) {
        $form['picture_migration'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Perform import of user pictures'),
          '#default_value' => TRUE,
        ];
      }
      else {
        $form['picture_migration'] = array(
          '#type' => 'value',
          '#value' => FALSE,
        );
      }
      if ($cached_values['version'] == 6) {
        $title = $this->t('Perform import of files other than user pictures');
      }
      else {
        $title = $this->t('Perform import of files');
      }
      $form['file_migration'] = [
        '#type' => 'checkbox',
        '#title' => $title,
        '#default_value' => TRUE,
      ];

      if ($cached_values['version'] == 7) {
        $form['instructions'] = [
          '#markup' => $this->t('To be able to retrieve any uploaded files from your legacy site, we need to know where to find them. If your legacy site is on the same web server as the destination site, or you have copied your file directory to the web server, please provide the full file directory - for example, <em>/var/www/drupal/sites/example.com/files/</em>. Otherwise, we need the web address of that directory. To determine that address: 
<ol><li>Please visit a node on the site containing images, or a user profile with an uploaded picture. Try to find an example where the full resolution image is displayed.</li>
<li>Right-click on the picture and look for an operation such as <em>View Image</em>.</li>
<li>Choose that function and look at the URL of the image - it will usually look something like <em>http://example.com/sites/default/files/pictures/picture-3.jpg</em> (for user pictures) or <em>http://example.com/sites/default/files/my-photo.jpg</em> (for files uploaded to nodes).</li> 
<li>If you see after the <em>files</em> portion of the path something like <em>styles</em> or <em>imagecache</em>, you are most likely looking at an automatically generated variation of the image, not the original. It will take some trial and error, removing intermediate portions of the path (such as <em>styles/medium/</em>) to find the original image.</li>
<li>Please enter the full address of the files directory (<em>http://example.com/sites/default/files/</em>) below. Leave the field empty to skip migrating files.</li></ol>'),
        ];
      }
      else {
        $form['instructions'] = [
          '#markup' => $this->t('To be able to retrieve any uploaded files from your legacy site, we need to know where to find them. If your legacy site is on the same web server as the destination site, or you have copied your file directory to the web server, please provide the full file directory - for example, <em>/var/www/drupal/sites/example.com/files/</em>. Otherwise, please provide the address of your legacy site (e.g., <em>http://example.com/</em> below. Leave the field empty to skip migrating files.'),
        ];
      }

      $form['description'] = [
        '#markup' => $this->t('There are @count files (including user pictures) available to be migrated.', 
          ['@count' => $file_count]),
      ];

      $form['source_base_path'] = [
        '#type' => 'textfield',
        '#size' => 60,
        '#title' => $this->t('File prefix'),
      ];
    }
    else {
      $form['description'] = [
        '#markup' => $this->t('There are no files to be migrated from your source site.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $cached_values['file_migration'] = $form_state->getValue('file_migration');
    $cached_values['picture_migration'] = $form_state->getValue('picture_migration');
    $cached_values['source_base_path'] = $form_state->getValue('source_base_path');
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
