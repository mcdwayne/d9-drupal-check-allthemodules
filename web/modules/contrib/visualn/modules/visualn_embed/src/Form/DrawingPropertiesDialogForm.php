<?php

namespace Drupal\visualn_embed\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\visualn_iframe\Entity\VisualNIFrame;
use Drupal\editor\EditorInterface;

/**
 * Class DrawingPropertiesDialogForm.
 *
 * @ingroup ckeditor_integration
 * @ingroup iframes_toolkit
 */
class DrawingPropertiesDialogForm extends FormBase {

  // @todo: move to iframe content provider
  const IFRAME_HANDLER_KEY = 'visualn_embed_key';


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drawing_properties_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EditorInterface $editor = NULL) {
    // @todo: maybe add sharing settings to a different form with its own ckeditor menu item
    //   or a link or a button to the properties dialog form that would open another sharing
    //   dialog form

    // @todo: the library should be connected outside of the form
    $form['#attached']['library'][] = 'visualn_embed/preview-drawing-dialog';

    // @todo: see core/modules/editor/src/Form/EditorImageDialog.php

    // @todo: check if it is the appropriate way to set drawing_id,
    //   e.g. could be set via controller and $form_state_additions (needs to be added then)
    $input = $form_state->getUserInput();
    //$drawing_id = isset($input['editor_object']['drawing_id']) ? $input['editor_object']['drawing_id'] : 0;

    $drawing_id = isset($input['editor_object']['data-visualn-drawing-id']) ? $input['editor_object']['data-visualn-drawing-id'] : 0;
    $align = isset($input['editor_object']['data-align']) ? $input['editor_object']['data-align'] : 'none';
    $width = isset($input['editor_object']['width']) ? $input['editor_object']['width'] : '';
    $height = isset($input['editor_object']['height']) ? $input['editor_object']['height'] : '';
    $hash = isset($input['editor_object']['data-visualn-drawing-hash']) ? $input['editor_object']['data-visualn-drawing-hash'] : '';

    $original_settings = isset($input['editor_object']['data-visualn-drawing-settings']) ? $input['editor_object']['data-visualn-drawing-settings'] : '';

    // @todo: maybe key it as ['editor_object']['drawing_id'] so that values form js call could be mapped
    //   directly into $formState->getValue()
    $form['drawing_id'] = [
      //'#type' => 'textfield',
      '#type' => 'hidden',
      '#default_value' => $drawing_id,
      //'#title' => $this->t('Drawing id'),
    ];

    $form['width'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 10000,
      '#title' => $this->t('Width'),
      '#default_value' => $width,
    ];
    $form['height'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 10000,
      '#title' => $this->t('Height'),
      '#default_value' => $height,
    ];
    $form['align'] = [
      '#type' => 'radios',
      '#title' => $this->t('Align'),
      '#options' => [
        'none' => $this->t('None'),
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $align,
      '#attributes' => ['class' => ['container-inline']],
    ];
    // keep original settings serialized value for keeping all extra settings
    // even if not supported by the properties form (see ajaxSubmitForm())
    $form['original_settings'] = [
      '#type' => 'hidden',
      '#default_value' => $original_settings,
    ];
    // @todo: generate new hash if needed (should be done in submit callback)
    $form['iframe_hash'] = [
      '#type' => 'hidden',
      // @todo: technically this allows to manually set other drawing's hash
      //   for the current one
      '#default_value' => $hash,
    ];

    // @todo: maybe also add margins properties
    //   though margins shouldn't override align 'center' properties
    //   which is done useing 'margin-left:auto; margin-right:auto;'

    // @todo: add iframe integration settings (sharing_enabled checkbox)
    //   by default use width and height from properties (if set) for iframe code

    // @todo: check if sharing allowed for that specific drawing, otherwise disable (or uncheck) the checkbox
    //   check for other options what to do in this case.
    //   Also check user permissions if it is allowed to enable sharing, do other required checks.
    if (\Drupal::service('module_handler')->moduleExists('visualn_iframe')) {
      // Get global defaults for iframes configuration forms and iframes builder service.
      $iframes_default_config = \Drupal::config('visualn_iframe.settings');
      $additional_config = \Drupal::config('visualn_embed.iframe.settings');
      if ($additional_config->get('allow_drawings_sharing')) {

        $settings = json_decode($original_settings, TRUE);
        // @todo: do settings items need to be validated before useing if form since taken from user input?
        $settings = is_array($settings) ? $settings : [];
        // @todo: add default settings for empty ones
        //   only add additional settings if visualn_iframe enabled (at least for submit handler)
        // @todo: add other required settings
        // @todo: rename to settings defaults (?)
        $settings += [
          'shared' => FALSE,
          'use_defaults' => FALSE,
          'show_link' => $iframes_default_config->get('default.show_link'),
          'origin_url' => $iframes_default_config->get('default.origin_url'),
          'origin_title' => $iframes_default_config->get('default.origin_title'),
          'open_in_new_window' => $iframes_default_config->get('default.open_in_new_window'),
        ];
        // Sharing settings should not be set manualy (i.e. typing in tag attribute),
        // by convention, for security reasons and other considerations but only using
        // drawing configuration form, by convention. Thus there is no practical reason to make
        // 'shared' a separate tag attribute (e.g. data-visualn-drawing-shared).
        // Also 'shared'should be stored as part of visualn_iframe entry (settings column)
        // whereas 'width' and 'height' are not.

        //$shared = isset($settings['shared']) ? $settings['shared'] : FALSE;


        // @todo: consider option when visualn_iframe was enabled and then disabled
        //   in this case configured embedded drawings info should no be lost
        //   also if module disabled, drawings (using sharing) cache should be reset to not
        //   show share code link and window (same for enabling)

        // add hash link to the form
        $hash_label = \Drupal::service('visualn_iframe.builder')->getHashLabel($hash);
        if ($hash_label) {
          $form['#attached']['library'][] = 'visualn_iframe/visualn-iframe-ui';
          $hash_label = " {$hash_label}";
        }

        // @todo: check element key name
        $form['sharing_enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable sharing') . $hash_label,
          // @todo: check if the value isn't already set for the ckeditor widget (tag)
          '#default_value' => $settings['shared'],
        ];

        $form['use_defaults'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Use defaults'),
          // @todo: add to iframe settings page
          '#default_value' => $settings['use_defaults'],
          '#states' => [
            'visible' => [
              ':input[name="sharing_enabled"]' => ['checked' => TRUE],
            ],
          ],
        ];

        // @todo: review the comments below in context of visualn_block module

        // @todo: maybe add a wrapper element to check use_defaults #states once
        // @todo: check key name
        $form['show_link'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show origin link'),
          '#default_value' => $settings['show_link'],
          '#states' => [
            'visible' => [
              ':input[name="sharing_enabled"]' => ['checked' => TRUE],
              ':input[name="use_defaults"]' => ['checked' => FALSE],
            ],
          ],
        ];
        // @todo: maybe save it as an iframe instance to store also the location (context info)
        //   page where it is shown for admin purposes
        // @todo: allow tokens e.g. for [current-page:url:alias]
        //   and give a link to tokens list in field description (admin/help/token)
        //   also add a setting to settings page to enable this option by default and option
        //   default values
        //   and if token not found then don't show broken links (or replace with front page link)
        // @todo: only show when token module enabled, otherwise provide info for user to
        //   download it
        // @todo: also provide link title textfield, otherwise use some default provided in settings
        $form['origin_url'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Origin url'),
          '#default_value' => $settings['origin_url'],
          '#description' => $this->t('Leave blank to use default origin url'),
          '#attributes' => [
            'placeholder' => $iframes_default_config->get('default.origin_url'),
          ],
          '#states' => [
            'visible' => [
              ':input[name="sharing_enabled"]' => ['checked' => TRUE],
              ':input[name="use_defaults"]' => ['checked' => FALSE],
              ':input[name="show_link"]' => ['checked' => TRUE],
            ],
          ],
          // @todo: try to validate user input (allow absolute or relative paths or tokens)
        ];
        $form['origin_title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Origin title'),
          '#default_value' => $settings['origin_title'],
          '#description' => $this->t('Leave blank to use default origin title'),
          '#attributes' => [
            'placeholder' => $iframes_default_config->get('default.origin_title'),
          ],
          '#states' => [
            'visible' => [
              ':input[name="sharing_enabled"]' => ['checked' => TRUE],
              ':input[name="use_defaults"]' => ['checked' => FALSE],
              ':input[name="show_link"]' => ['checked' => TRUE],
            ],
          ],
          // @todo: check if user input validation is needed
        ];
        $form['open_in_new_window'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Open in new window'),
          '#default_value' => $settings['open_in_new_window'],
          '#states' => [
            'visible' => [
              ':input[name="sharing_enabled"]' => ['checked' => TRUE],
              ':input[name="use_defaults"]' => ['checked' => FALSE],
              ':input[name="show_link"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }
    }

    // @todo: attach ajax callback and return a command to replace the tag
    //   see DrawingEmbedListDialogForm::ajaxSubmitForm() and EditorDialogSave() command usage
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
      ],
    ];
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $drawing_id = $form_state->getValue('drawing_id', 0);
    $align = $form_state->getValue('align', '');
    $width = $form_state->getValue('width', '');
    $height = $form_state->getValue('height', '');

    // @todo: check if not empty, otherwise do not set
    //   no need to check if module enabled since this is a self-contained property
    //   and should be preserved if set before.
    //   Maybe would better be moved into settings to other properties
    //   though will be not convenient to set manually then
    $iframe_hash = $form_state->getValue('iframe_hash');


    // the value is set in submitForm() callback
    $settings = $form_state->getValue('original_settings', '');

    // @todo: set all properties in one array (and check entity_embed module implementation)
    $properties = [];

    $data = [
      'drawing_id' => $drawing_id,
      'tag_attributes' => [
        'data-visualn-drawing-id' => $drawing_id,
        // add align and dimensions properties
        'width' => $width,
        'height' => $height,

        // @todo: this will reuse core AlignFilter filter
        //   also mention in documentation that the filter should be enabled in order it to work
        'data-align' => $align,
        //'data-visualn-drawing-align' => $align,
        'data-visualn-drawing-settings' => $settings,
        // @todo: move hash to settings (?)
        'data-visualn-drawing-hash' => $iframe_hash,
      ],
    ];

    // @todo: it will always add align since in any case (at least 'none' option)
    // exclude empty values and zeros
    foreach (array_keys($data['tag_attributes']) as $key) {
      if (empty($data['tag_attributes'][$key])) {
        unset($data['tag_attributes'][$key]);
      }
      elseif ($key == 'data-visualn-drawing-settings' && $data['tag_attributes'][$key] == '[]') {
        unset($data['tag_attributes'][$key]);
      }
    }

    $response->addCommand(new EditorDialogSave($data));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // @todo: reset cache tags for the hash, the cache tags are attached to build
    //   in EmbedDrawingFilter::process()
    //
    //   also cache tags should be reset in EmbedDrawingFilter::process() for the iframe hash build cache

    $drawing_id = $form_state->getValue('drawing_id', 0);


    // @todo: rename the key to settings, add description comment
    $original_settings = $form_state->getValue('original_settings', '');
    $original_settings = json_decode($original_settings, TRUE);
    // initially there are no original settings for the ckeditor widget
    // so set to an empty array
    $original_settings = is_array($original_settings) ? $original_settings : [];
    $settings = $original_settings;


    // @todo:

    // check if visualn_iframe module is enabled,
    //   otherwise the settigs will be added as part of $original_settings (i.e. extra settings)
    if (\Drupal::service('module_handler')->moduleExists('visualn_iframe')) {
      // @todo: what if drawings sharing is enabled while properties form and editing before submit?
      $additional_config = \Drupal::config('visualn_embed.iframe.settings');
      if ($additional_config->get('allow_drawings_sharing')) {
        // @todo: drawer properties form should check if the hash belongs to the given drawer
        // technically user can manually replace one hash by another and that one
        // will be used in drawer properties form
        // it is not possible to check it if the new hash belong to the drawing with the same id
        // though possible to check if is another id

        // also user may manually delete the hash, this can not be checked
        // since it may be the normal case when user embeds multiple drawings with the same id

        // @todo: if sharing settings empty and sharing is disabled for embedded drawings, do not add any values
        // @todo: check iframe settings

        // @todo: review keys names
        // @todo: only add overridden values
        $settings = [
          'shared' => $form_state->getValue('sharing_enabled'),
          'use_defaults' => $form_state->getValue('use_defaults'),
          'show_link' => $form_state->getValue('show_link'),
          'origin_url' => trim($form_state->getValue('origin_url')),
          'origin_title' => trim($form_state->getValue('origin_title')),
          'open_in_new_window' => $form_state->getValue('open_in_new_window'),
          // all other extra settings should be kept even if not supported by the
          //   properties form
        ] + $settings;

        // @todo: if defaults are used across the settings array, no need to
        //   create new visualn_iframe entry for every shared drawing
        //   (review this affirmation)

        // Set/create iframe hash property here if needed (if empty or defaults are used)
        $iframe_hash = $form_state->getValue('iframe_hash');
        // no need to check anything and create new hash if sharing is not enabled and no hash set, just do nothing
        if (empty($iframe_hash) && !$form_state->getValue('sharing_enabled')) {
          if (!isset($original_settings['shared'])) {
            // ignore all the other sharing settings if 'shared' is not set for original_settings
            // and thus supposed to be never shared
            $settings = $original_settings;
          }
          // else still allow to update sharing settings even if disabled
        }
        // @todo: also no need to generate hash for empty drawings - FALSE should be returned
        elseif (empty($iframe_hash)) {

          // @todo: generate new hash only if there are overridden default settings
          //   or non-empty extra settings in original_settings array
          //   so that a single visualn_iframe entry could be used for multiple drawings
          //   shared locations
          //   also remove custom hash if properties a set to defaults (maybe add a checkbox
          //   to show that defaults are used, the defaults themselves are taken from
          //   module settings on configuration page)



          // return FALSE if drawing with the given id doesn't exist
          $drawing_entity = \Drupal::entityTypeManager()->getStorage('visualn_drawing')->load($drawing_id);
          if (!$drawing_entity) {
            // @todo: do not create iframe entry if drawing doesn't exist
            //   though an older entry could already exist (then hash should be non-empty)
          }

          // Even though user may not save the changes and the iframe will be never used,
          // still create and iframe entry. Later such entries can be found by garbage
          // collector based on 'displayed' and viewed' fields values.
          // The entry itself is required to track the author of it (e.g. the user
          // who has enabled sharing for the current drawing embedded instance).
          // Initially the approach was to record only iframes when sharing link is
          // rendered though technically that may be other user who viewed the page
          // with the link for the first time.
          // @todo: though it doesn't help to track later changes, i.e. when some other
          //   user changes properties (settings) etc. It may have sense since
          //   author in iframes case is generally not an owner (or just an owner) but
          //   also a user responsible for some action.
          $hash = \Drupal::service('visualn_iframe.builder')->generateHash();
          $data = ['drawing_id' => $drawing_id];
          $params = [
            'drawing_id' => $drawing_id,
            'hash' => $hash,
            // @todo: is status required?
            'status' => 1,
            // @todo: check
            'langcode' => 'en',
            'name' => $drawing_entity->label(),
            'user_id' => \Drupal::currentUser()->id(),
            'settings' => $settings,
            'data' => $data,
            'displayed' => FALSE,
            'viewed' => FALSE,
            'handler_key' => static::IFRAME_HANDLER_KEY,
            'implicit' => FALSE,
          ];
          $iframe_entity = \Drupal::service('visualn_iframe.builder')
            ->createIFrameEntity($params);

          $form_state->setValue('iframe_hash', $hash);
        }
        else {
          // @todo: Only reset cache tags to rerender sharing link and iframe
          //   markup itself (see the note below).

          // Do nothing else here (e.g. do not update visualn_iframe entry) since
          // even if properties (settings) were changed, user still may
          // revert the changes or just not save changed markup (e.g. when
          // used inside of node textarea field).
          // On the other hand user *can* save the changes and this fact
          // should be respected when generating iframe markup. It is done
          // right at share link rerendring - EmbedDrawingFilter::process()
          // which means that changes were saved and are used
          // comparing stored_settings with actual widget tag attributes settings.

          // create a staging properties entry
          $user_id = \Drupal::currentUser()->id();
          \Drupal::service('visualn_iframe.builder')
            ->stageIFrameSettings($iframe_hash, $user_id, $settings);
        }
      }
    }

    // update original_settings value
    $form_state->setValue('original_settings', json_encode($settings));
  }

}
