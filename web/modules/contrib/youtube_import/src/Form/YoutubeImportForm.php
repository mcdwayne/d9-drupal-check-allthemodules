<?php

namespace Drupal\youtube_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure youtubeImport settings for this site.
 */
class YoutubeImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'youtubeImport_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /*
     * Declare the default values to meet coding standards
     * these will be filled by the extract function.
     */
    $apikey = $drupal_user = $username = $playlistid = $frequency = $contenttype = $lastrun = '';
    $mapping = [];
    // A flag to see if there is a youtube field.
    $has_youtube_field = FALSE;

    // Get the settings array and extract it locally.
    extract(youtube_import_get());

    $form['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('YouTube API key'),
      '#required' => TRUE,
      '#default_value' => $apikey,
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('YouTube user name or your channel ID'),
      '#description' => $this->t('This value is only used to get the playlist id. If you know the playlist id, you may leave this blank but be sure to fill in one or the other.'),
      '#default_value' => $username,
    ];
    $form['playlistid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('YouTube play list ID'),
      '#description' => $this->t('You may leave this blank if you have entered the YouTube username and it will be automatically updated to the "uploads" playlist of that user.'),
      '#default_value' => $playlistid,
    ];
    $form['frequency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cron Frequency'),
      '#description' => $this->t('Enter 0 to disable the cron job. Enter the time in seconds to have it run during cron.'),
      '#required' => TRUE,
      '#default_value' => $frequency,
    ];
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }
    $form['contenttype'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#options' => $contentTypesList,
      '#description' => $this->t('Select the content type that videos should import to.'),
      '#required' => TRUE,
      '#default_value' => $contenttype,
    ];
    $userStorage = \Drupal::service('entity_type.manager')->getStorage('user')->loadMultiple();
    $authorsList = [];
    foreach ($userStorage as $userDetail) {
      if ($userDetail->label() !== "Anonymous") {
        $authorsList[$userDetail->id()] = $userDetail->label();
      }
    }
    $form['drupal_user'] = [
      '#type' => 'select',
      '#title' => $this->t('Author'),
      '#options' => $authorsList,
      '#description' => $this->t('YouTube import will default to the current user or the user selected here.'),
      '#default_value' => $drupal_user,
    ];
    /*
     * If there is no playlistid or apikey, then this has to be new or broken
     * don't give run options to users that aren't ready.
     */
    if ($apikey && $playlistid) {
      // Create the run link html.
      $markup = \Drupal::l($this->t('Click here to run the import now.'), Url::fromRoute('youtube_import.import'));
      // If there is a lastrun date, lets display it.
      if ($lastrun) {
        $markup .= ' (Last run: ' . format_date((int) $lastrun, 'long') . ')';
      }
      // Add the link to the form.
      $form['youtube_import_run_link'] = [
        '#markup' => "<p>{$markup}</p>",
      ];
    }
    /*
     * The form has 2 submit buttons because the mapping area
     * could get long and tedious to scroll through
     * this is the first one.
     */
    $form['submittop'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration Settings'),
    ];
    // If there is no content type, then we can't select fields.
    if (!empty($contenttype)) {
      /*
       * Just a heading to let the user know this is the
       * mapping section.
       */
      $form['mapheading'] = [
        '#type' => 'markup',
        '#markup' => '<h2>' . $this->t('Field Mapping') . '</h2>',
      ];
      /*
       * Initialize an array for the field names and labels
       * as well as add the ones that do not show up.
       */
      $fields = ['title' => $this->t('Title'), 'created' => 'Created'];
      /*
       * Loop through the fields and add them to our
       * more useful array.
       */
      foreach (\Drupal::entityManager()->getFieldDefinitions('node', $contenttype) as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle()) && $field_name != "promote") {
          // Need to mark youtube fields as they are always included.
          if ($field_definition->getType() == 'youtube') {
            $fields[$field_name] = $field_definition->getLabel() . '*';
            $has_youtube_field = TRUE;
          }
          else {
            $fields[$field_name] = $field_definition->getLabel();
          }
        }
      }
      /*
       * Get the properties that we can pull
       * from YouTube.
       */
      $properties = [
        '' => $this->t('None'),
        'title' => $this->t('Title'),
        'description' => $this->t('Description'),
        'publishedAt' => $this->t('Published Date'),
        'thumbnails' => $this->t('Thumbnail Image'),
        'id' => $this->t('Video ID'),
        'url' => $this->t('Share URL'),
        'duration' => $this->t('Duration'),
        'dimension' => $this->t('Dimension'),
        'definition' => $this->t('Definition'),
        'viewCount' => $this->t('Number of Views'),
        'likeCount' => $this->t('Number of Likes'),
        'dislikeCount' => $this->t('Number of dislikes'),
        'favoriteCount' => $this->t('Number of Favorites'),
        'commentCount' => $this->t('Number of comments'),
      ];

      // Create our indefinite field element.
      $form['mapping'] = [
        '#tree' => TRUE,
      ];

      /*
       * Loop through each of the fields in the
       * content type and create a mapping drop down
       * for each.
       */
      foreach ($fields as $fieldname => $label) {

        // YouTube fields are added automatically.
        if (strpos($label, '*') !== FALSE) {
          $form['mapping'][$fieldname] = [
            '#type' => 'select',
            '#title' => $this->t("@l <smal>@f</small>", ['@f' => $fieldname, '@l' => $label]),
            '#options' => $properties,
            '#value' => $this->t('url'),
            '#disabled' => TRUE,
          ];
        }
        else {
          // Create the mapping dropdown.
          $form["mapping"][$fieldname] = [
            '#type' => 'select',
            '#title' => $this->t("@l <small>@f</small>", ['@f' => $fieldname, '@l' => $label]),
            '#options' => $properties,
            '#default_value' => isset($mapping[$fieldname]) ? $mapping[$fieldname] : NULL,
          ];
        }
      }

      // If there is a youtube field, need to explain *.
      if ($has_youtube_field) {
        $form['youtube_markup'] = [
          '#type' => 'markup',
          '#markup' => '<p>' . $this->t('YouTube fields are automatically added to the mapping.') . '</p>',
        ];
      }

      // Create the submit button at the bottom of the form.
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save Configuration Settings'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('username')) && empty($form_state->getValue('playlistid'))) {
      $form_state->setErrorByName('username', $this->t("Both Username and Playlist Id fields cannot be blank."));
      $form_state->setErrorByName('playlistid');
    }
    if (!is_numeric($form_state->getValue('frequency'))) {
      $form_state->setErrorByName('frequency', $this->t('Cron Frequency input must be numeric.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the previous settings.
    $settings = youtube_import_get();
    // Get the youtube settings list (non mapping stuff).
    $setting_keys = [
      'username',
      'drupal_user',
      'apikey',
      'playlistid',
      'lastrun',
      'frequency',
      'contenttype',
    ];
    // Loop through the form values and see which matches we can find.
    foreach ($setting_keys as $key) {
      // Set the value or clear it depending on user submission.
      if (array_key_exists($key, $form_state->getValues())) {
        $settings[$key] = $form_state->getValues()[$key];
      }
      else {
        $settings[$key] = '';
      }
    }
    // Loop through the user updated mapping fields.
    if (array_key_exists('mapping', $form_state->getValues())) {
      foreach ($form_state->getValues()['mapping'] as $key => $value) {
        // Set the mapping value.
        $settings['mapping'][$key] = $value;
      }
    }
    // If the username was set and the playlist wasn't, let's get the default.
    if (empty($settings['playlistid'])) {
      $settings['playlistid'] = youtube_import_playlist_id($settings['username'], $settings['apikey']);
    }
    // Determine the level of success.
    if (!empty($settings['playlistid'])) {
      // Inform the user.
      drupal_set_message($this->t('YouTube Import settings saved successfully.'));
    }
    else {
      drupal_set_message($this->t('Unable to set the play list ID.'), 'error');
    }
    // Save our settings.
    youtube_import_set($settings);
  }

}
