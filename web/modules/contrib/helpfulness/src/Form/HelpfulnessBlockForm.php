<?php

namespace Drupal\helpfulness\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;

/**
 * Defines a block form to leave helpfulness feedback.
 */
class HelpfulnessBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'helpfulness_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Include the css.
    $form['#attached']['library'] = [
      'helpfulness/helpfulness-block-form',
    ];

    $form['helpfulness_rating'] = [
      '#type' => 'radios',
      '#options' => [1 => t('Yes'), 0 => t('No')],
    ];

    // Build the title markup.
    $config = \Drupal::config('helpfulness.settings');
    $title = '<div class="helpfulness_yes_title">' . $config->get('helpfulness_yes_title') . '</div >';
    $title .= '<div class="helpfulness_no_title">' . $config->get('helpfulness_no_title') . '</div >';

    // Build the description markup.
    $description = '<div class="helpfulness_yes_description">' . $config->get('helpfulness_yes_description') . '</div >';
    $description .= '<div class="helpfulness_no_description" >' . $config->get('helpfulness_no_description') . '</div >';

    $form['helpfulness_comments'] = [
      '#type' => 'textarea',
      '#title' => $title,
      '#description' => $description,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('helpfulness.settings');

    if (NULL == ($form_state->getValue('helpfulness_rating'))) {
      $form_state->setErrorByName('helpfulness_rating', $this->t('Please indicate if this page is helpful or not.'));
    }

    $comment_required = $config->get('helpfulness_comment_required', 0);
    if ($comment_required != 0) {
      $comment_text = strip_tags($form_state->getValue('helpfulness_comments'));
      if (strlen($comment_text) < 1) {
        $form_state->setErrorByName('helpfulness_comments', $config->get('helpfulness_comment_required_message', ''));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the useragent, with a fall back in case the access is blocked.
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      $user_agent = Html::escape(trim($_SERVER['HTTP_USER_AGENT']));
    }
    else {
      $user_agent = 'not available';
    }

    // Validate the comments.
    $comments = Unicode::substr(trim(strip_tags($form_state->getValue('helpfulness_comments'))), 0, 1024);
    if (empty($comments)) {
      $comments = t('None');
    }

    // Get the authenticated user ID (if any).
    $user_id = \Drupal::currentUser()->id();

    // Form values.
    $fields = [
      'uid' => $user_id,
      'status' => HELPFULNESS_STATUS_OPEN,
      'system_path' => \Drupal::service('path.current')->getPath(),
      'path_alias' => Url::fromRoute("<current>")->toString(),
      'base_url' => $GLOBALS['base_url'],
      'helpfulness' => $form_state->getValue('helpfulness_rating'),
      'message' => $comments,
      'useragent' => $user_agent,
      'timestamp' => REQUEST_TIME,
    ];

    // Get the database connection.
    $db = Database::getConnection();

    // Insert the data to the helpfulness table.
    $db->insert('helpfulness')
      ->fields($fields)
      ->execute();

    // Get the configuration.
    $config = $this->config('helpfulness.settings');
    $notification_email = $config->get('helpfulness_notification_email');

    if (!empty($notification_email)) {
      $params['helpfulness_rating'] = $form_state->getValue('helpfulness_rating');
      $params['helpfulness_comments'] = $form_state->getValue('helpfulness_comments');

      // Get the site email address.
      $site_mail = \Drupal::config('system.site')->get('mail');

      // Default language of the site.
      $language = \Drupal::languageManager()->getDefaultLanguage();

      // Send the email.
      \Drupal::service('plugin.manager.mail')->mail('helpfulness', 'new_feedback_notification', $notification_email, $language, $params, $site_mail, TRUE);
    }

    drupal_set_message(t('Thank you for your feedback.'));
  }

}
