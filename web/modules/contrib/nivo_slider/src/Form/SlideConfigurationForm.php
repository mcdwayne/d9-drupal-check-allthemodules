<?php

namespace Drupal\nivo_slider\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Url;
use Drupal\likebtn\LikebtnInterface;

class SlideConfigurationForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return [
      'nivo_slider.settings'
    ];
  }

  public function getFormId() {
    return 'slide_configuration';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nivo_slider.settings');

    // Get all available slides.
    $slides = $config->get('slides');

    // Upload.
    $form['upload'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload a new slide image'),
    ];

    // Draggable table.
    $form['order'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Published'),
        $this->t('Delete'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('There are no slides yet.'),
      '#tabledrag' => [
        ['order', 'sibling', 'slide-weight'],
      ],
      '#tree' => TRUE,
    ];

    // Vertical tab.
    $form['images'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Slider images'),
      '#tree' => TRUE,
    ];

    // Process available slides.
    foreach ($slides as $slide => $settings) {
      // Table.
      $form['order'][$slide]['#attributes']['class'][] = 'draggable';
      $form['order'][$slide]['#weight'] = $settings['weight'];

      $form['order'][$slide]['title'] = [
        '#plain_text' => $settings['title'],
      ];
      $form['order'][$slide]['published'] = [
        '#type' => 'checkbox',
        '#default_value' => $settings['published'],
      ];
      $form['order'][$slide]['delete'] = [
        '#type' => 'checkbox',
        '#default_value' => $settings['delete'],
      ];
      $form['order'][$slide]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $settings['title']]),
        '#title_display' => 'invisible',
        '#default_value' => $settings['weight'],
        '#attributes' => ['class' => ['slide-weight']],
      ];

      // Vertical Tabs.
      $form['images'][$slide] = [
        '#type' => 'details',
        '#group' => 'images',
        '#title' => $this->t('Image @number: @title', [
            '@number' => $slide + 1,
            '@title' => isset($settings['title']) ? $settings['title'] : '',
          ]
        ),
        '#weight' => $slide,
      ];

      // Load the slide's image file.
      $file = file_load($settings['fid']);

      // Create a preview image of the slide using an image style if appropriate.
//      if ($config->get('options.image_style') == FALSE && !empty($file)) {
        $variables = array(
//          'style_name' => 'thumbnail',
          'uri' => $file->getFileUri(),
        );
        // The image.factory service will check if our image is valid.
        $image = \Drupal::service('image.factory')->get($file->getFileUri());
        if ($image->isValid()) {
          $variables['width'] = $image->getWidth();
          $variables['height'] = $image->getHeight();
        }
        else {
          $variables['width'] = $variables['height'] = NULL;
        }
        $image = [
          '#theme' => 'image',
          '#width' => $variables['width'],
          '#height' => $variables['height'],
//          '#style_name' => $variables['style_name'],
          '#uri' => $variables['uri'],
        ];
//      }
//      else {
//        $variables = [
//          'uri' => $file->uri,
//          'style_name' => $config->get('options.image_style_slide'),
//        ];
//        $image = theme('image_style', $variables);
//      }

      $form['images'][$slide]['preview'] = $image;
      $form['images'][$slide]['fid'] = [
        '#type' => 'hidden',
        '#value' => isset($settings['fid']) ? $settings['fid'] : '',
      ];
      $form['images'][$slide]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => isset($settings['title']) ? $settings['title'] : '',
        '#description' => $this->t('The title is used as alternative text for the slide image.'),
      ];
      $form['images'][$slide]['description'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Description'),
        '#default_value' => isset($settings['description']['value']) ? $settings['description']['value'] : '',
        '#format' => isset($settings['description']['format']) ? $settings['description']['format'] : NULL,
        '#description' => $this->t('The description will be displayed with the slide image.'),
      ];
      $form['images'][$slide]['url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Link slide to URL'),
        '#default_value' => isset($settings['url']) ? $settings['url'] : '',
        '#description' => $this->t('Specify a path or an absolute URL. An example path is @blog for the blog page. An example absolute URL is @url for the Drupal website. @front is the front page.', [
          '@blog' => 'blog',
          '@url' => 'http://drupal.org',
          '@front' => '<front>',
        ]),
      ];
      $form['images'][$slide]['visibility'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Show slide on specific pages'),
        '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are @blog for the blog page and @blog-wildcard for every personal blog. @front is the front page.", [
          '@blog' => 'blog',
          '@blog-wildcard' => 'blog/*',
          '@front' => '<front>',
        ]),
        '#default_value' => isset($settings['visibility']) ? $settings['visibility'] : '*',
      ];
      $form['images'][$slide]['transition'] = [
        '#type' => 'select',
        '#title' => $this->t('Transition'),
        '#options' => [
          '' => $this->t('- Default -'),
          'sliceDown' => $this->t('Slice Down'),
          'sliceDownLeft' => $this->t('Slice Down Left'),
          'sliceUp' => $this->t('Slice Up'),
          'sliceUpLeft' => $this->t('Slice Up Left'),
          'sliceUpDown' => $this->t('Slice Up Down'),
          'sliceUpDownLeft' => $this->t('Slice Up Down Left'),
          'fold' => $this->t('Fold'),
          'fade' => $this->t('Fade'),
          'random' => $this->t('Random'),
          'slideInRight' => $this->t('Slide In Right'),
          'slideInLeft' => $this->t('Slide in Left'),
          'boxRandom' => $this->t('Box Random'),
          'boxRain' => $this->t('Box Rain'),
          'boxRainReverse' => $this->t('Box Rain Reverse'),
          'boxRainGrow' => $this->t('Box Rain Grow'),
          'boxRainGrowReverse' => $this->t('Box Rain Grow Reverse'),
        ],
        '#description' => $this->t('Select a transition. Selecting an option other than %default will force this slide to use the selected transition every time it appears. It overrides all other effect settings.', ['%default' => '- Default -']),
        '#default_value' => isset($settings['transition']) ? $settings['transition'] : '',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateForm() method.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $slides = [];
    $values = $form_state->getValues();
    $config = $this->config('nivo_slider.settings');

    if (is_array($values['order']) && is_array($values['images'])) {
      // Merge in order settings.
      foreach ($values['order'] as $slide => $settings) {
        if (is_numeric($slide)) {
          if (is_array($settings)) {
            $slides[$slide] = isset($slides[$slide]) ? array_merge($slides[$slide], $settings) : $settings;
          }
        }
      }

      // Merge in images settings.
      foreach ($values['images'] as $slide => $settings) {
        if (is_numeric($slide)) {
          if (is_array($settings)) {
            $slides[$slide] = isset($slides[$slide]) ? array_merge($slides[$slide], $settings) : $settings;
          }
        }
      }
    }

    // Remove any deleted slides.
    foreach ($slides as $slide => $settings) {
      // Delete the slide if required.
      if ($settings['delete']) {
        unset($slides[$slide]);
      }
    }

    // Update / create translation source for user defined slide strings.
    if (function_exists('i18n_string_update')) {
      nivo_slider_locale_refresh($slides);
    }

    // Store slide images in a folder named 'banner'.
    $banner_folder = 'public://banner';

    // Create the banner directory if it does not currently exist.
    file_prepare_directory($banner_folder, FILE_CREATE_DIRECTORY);

    // Create a new slide if an image was uploaded.
    if ($file = file_save_upload('upload', [], $banner_folder, 0)) {
      // Create a new slide.
      $slides[] = [
        'fid' => $file->id(),
        'title' => '',
        'description' => [
          'value' => '',
          'format' => filter_fallback_format(),
        ],
        'url' => '',
        'visibility' => '*',
        'transition' => '',
        'weight' => 1,
        'published' => 1,
        'delete' => 0,
      ];
    }

    // Sort the slides by weight.
    usort($slides, 'drupal_sort_weight');

    // Save the slides.
    $config->set('slides', $slides)
      ->save();

    parent::submitForm($form, $form_state);
  }
}
