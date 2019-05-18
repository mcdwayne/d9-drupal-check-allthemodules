<?php

namespace Drupal\simplepodcast\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simplepodcast\SimplePodcastCommonFunctions;
/**
 * Configure simplepodcast settings for this site.
 */
class SimplePodcastAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simplepodcast_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simplepodcast.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $podcast_title = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('title');
    $podcast_description = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('description');
    $podcast_language = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('language');
    $podcast_copyright = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('copyright');
    $podcast_owner_name = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('owner_name');
    $podcast_owner_author = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('owner_author');
    $podcast_owner_email = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('owner_email');
    $podcast_channel_image = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('channel_image');
    $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
    // If you need to display them in a drop down:
    $node_options = [];
    foreach ($node_types as $node_type) {
      $node_options[$node_type->id()] = $node_type->label();
    }
    $itunes_categories = ['Arts' => ['Arts' => '--Arts--',
                                    'Arts|Design' => 'Design',
                                    'Arts|Fashion &amp; Beauty' => 'Fashion & Beauty',
                                    'Arts|Food' => 'Food',
                                    'Arts|Literature'  => 'Literature',
                                    'Arts|Performing Arts' => 'Performing Arts',
                                    'Arts|Visual Arts' => 'Visual Arts'],

                          'Business' => ['Business' => '--Business--',
                                        'Business|Business News' => 'Business News',
                                        'Business|Careers' => 'Careers',
                                        'Business|Investing' => 'Investing',
                                        'Business|Management &amp; Marketing' => 'Management & Marketing',
                                        'Business|Shopping' => 'Shopping'],
                          'Comedy' => ['Comedy' => '--Comedy--'],
                          'Education' => ['Education' => '--Education--',
                                         'Education|Educational Technology' => 'Educational Technology',
                                         'Education|Higher Education' => 'Higher Education',
                                         'Education|K-12' => 'K-12',
                                         'Education|Language Courses' => 'Language Courses',
                                         'Education|Training' => 'Training'],
                          'Games & Hobbies' => ['Games &amp; Hobbies' => '--Games & Hobbies--',
                                               'Games &amp; Hobbies|Automotive' => 'Automotive',
                                                'Games &amp; Hobbies|Aviation' => 'Aviation',
                                                'Games &amp; Hobbies|Hobbies' => 'Hobbies',
                                                'Games &amp; Hobbies|Other Games' => 'Other Games',
                                                'Games &amp; Hobbies|Video Games' => 'Video Games'],
                          'Government & Organizations' => ['Government &amp; Organizations' => '--Government & Organizations--',
                                          'Government &amp; Organizations|Local' => 'Local',
                                          'Government &amp; Organizations|National' => 'National',
                                          'Government &amp; Organizations|Non-Profit' => 'Non-Profit',
                                          'Government &amp; Organizations|Regional'=>'Regional'],
                          'Health' => ['Health' => '--Health--',
                                          'Health|Alternative Health'=>'Alternative Health',
                                          'Health|Fitness &amp; Nutrition'=>'Fitness & Nutrition',
                                          'Health|Self-Help'=>'Self-Help',
                                          'Health|Sexuality'=>'Sexuality'],
                          'Kids & Family' => ['Kids &amp; Family' => '--Kids & Family--'],
                          'Music' => ['Music' => '--Music--'],
                          'News & Politics' => ['News &amp; Politics' => '--News & Politics--'],
                          'Religion & Spirituality' => ['Religion &amp; Spirituality' => '--Religion & Spirituality--',
                                          'Religion &amp; Spirituality|Buddhism' => 'Buddhism',
                                          'Religion &amp; Spirituality|Christianity' => 'Christianity',
                                          'Religion &amp; Spirituality|Hinduism' => 'Hinduism',
                                          'Religion &amp; Spirituality|Islam' => 'Islam',
                                          'Religion &amp; Spirituality|Judaism' => 'Judaism',
                                          'Religion &amp; Spirituality|Other' => 'Other',
                                          'Religion &amp; Spirituality|Spirituality' => 'Spirituality'],
                          'Science & Medicine' => ['Science &amp; Medicine' => '--Science & Medicine--',
                                          'Science &amp; Medicine|Medicine' => 'Medicine',
                                          'Science &amp; Medicine|Natural Sciences' => 'Natural Sciences',
                                          'Science &amp; Medicine|Social Sciences' => 'Social Sciences'],
                          'Society & Culture'=> ['Society &amp; Culture' => '--Society & Culture--',
                                          'Society &amp; Culture|History' => 'History',
                                          'Society &amp; Culture|Personal Journals' => 'Personal Journals',
                                          'Society &amp; Culture|Philosophy' => 'Philosophy',
                                          'Society &amp; Culture|Places &amp; Travel' => 'Places & Travel'],
                          'Sports & Recreation'=> ['Sports &amp; Recreation|Amateur' => 'Amateur',
                                          'Sports &amp; Recreation|College &amp; High School' => 'College & High School',
                                          'Sports &amp; Recreation|Outdoor' => 'Outdoor',
                                          'Sports &amp; Recreation|Professional' => 'Professional'],
                          'Technology'=> ['Technology|Gadgets' => 'Gadgets',
                                          'Technology|Tech News' => 'Tech News',
                                          'Technology|Podcasting' => 'Podcasting',
                                          'Technology|Software How-To' => 'Software How-To'],
                          'TV & Film' => ['TV &amp; Film' => '--TV & Film--']];

    $itunes_categories_plus_blank = array_merge(['' => 'NO CATEGORY'],$itunes_categories);

    $field_map = \Drupal::entityManager()->getFieldMap();
    $node_field_map = $field_map['node'];
    $node_fields = array_keys($node_field_map);

    $node_fields_array = array();
    foreach ($node_fields as $field) {
      $node_fields_array[$field] = $field;
    }

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['simplepodcast_content_type'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('episode_content_type'),
      '#description' => $this->t('This is the machine name of your podcast content type'),
      '#required' => TRUE,
      '#title' => $this->t('Content Type'),
      '#type' => 'select',
      '#options' => $node_options,
    ];
    $form['general']['simplesimplepodcast_path_name'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('rss_path_name'),
      '#description' => $this->t('This is the path (not including the hostname) of the RSS feed'),
      '#maxlength' => 32,
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('RSS Path Name'),
      '#type' => 'textfield',
    ];
    $form['general']['simplepodcast_title'] = [
      '#default_value' => $this->t($podcast_title),
      '#description' => $this->t('This is the official title of the Podcast'),
      '#maxlength' => 32,
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('Podcast Title'),
      '#type' => 'textfield',
    ];
    $form['general']['simplepodcast_description'] = [
      '#default_value' => $this->t($podcast_description),
      '#description' => $this->t('This is the official description of the Podcast'),
      '#required' => TRUE,
      '#rows' => 10,
      '#cols' => 80,
      '#resizable' => 'both',
      '#title' => $this->t('Podcast Description'),
      '#type' => 'textarea',
    ];
    $form['general']['simplepodcast_language'] = [
      '#default_value' => $this->t($podcast_language),
      '#description' => $this->t('This is the official language of the Podcast'),
      '#maxlength' => 8,
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('Podcast Language'),
      '#type' => 'textfield',
    ];
    $form['general']['simplepodcast_copyright'] = [
      '#default_value' => $this->t($podcast_copyright),
      '#description' => $this->t('This is the copyright notice for the Podcast (not including copyright \'&#169;\' symbol)'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('Podcast Copyright'),
      '#type' => 'textfield',
    ];
    $form['general']['simplepodcast_explicit'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('explicit'),
      '#return_value' => TRUE,
      '#title' => $this->t('Is Podcast Explicit?'),
      '#type' => 'checkbox',
    ];
    $form['general']['simplepodcast_owner_name'] = [
      '#default_value' => $this->t($podcast_owner_name),
      '#description' => $this->t('This is the owner name of the podcast'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('Podcast Owner Name'),
      '#type' => 'textfield',
    ];
    $form['general']['simplepodcast_owner_author'] = [
      '#default_value' => $this->t($podcast_owner_author),
      '#description' => $this->t('This is the author of the podcast'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('Podcast Owner Author'),
      '#type' => 'textfield',
    ];
    $form['general']['simplepodcast_owner_email'] = [
      '#default_value' => $podcast_owner_email,
      '#description' => $this->t('This is the owner email address of the podcast'),
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('Podcast Owner Email Address'),
      '#type' => 'email',
    ];
    $form['general']['simplepodcast_channel_image'] = [
      '#default_value' => $this->t($podcast_channel_image),
      '#description' => $this->t('This is the channel image for iTunes and other podcast aggregators'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('Podcast Channel Image'),
      '#type' => 'textfield',
    ];
    $form['general']['simplepodcast_category_1'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('category_1'),
      '#description' => $this->t('This is the First iTunes Podcast Category. <strong>It cannot be blank!</strong>'),
      '#title' => $this->t('Category 1'),
      '#type' => 'select',
      '#options' => $itunes_categories,
    ];
    $form['general']['simplepodcast_category_2'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('category_2'),
      '#description' => $this->t('This is the Second iTunes Podcast Category'),
      '#title' => $this->t('Category 2'),
      '#type' => 'select',
      '#options' => $itunes_categories_plus_blank,
    ];
    $form['general']['simplepodcast_category_3'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('category_3'),
      '#description' => $this->t('This is the Third iTunes Podcast Category'),
      '#title' => $this->t('Category 3'),
      '#type' => 'select',
      '#options' => $itunes_categories_plus_blank,
    ];
    $form['general']['simplepodcast_item_title'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_title'),
      '#description' => $this->t('This is the field from where the Episode Title should come.'),
      '#title' => $this->t('Episode Title'),
      '#type' => 'select',
      '#options' => $node_fields_array,
    ];
    $form['general']['simplepodcast_item_author'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_author'),
      '#description' => $this->t('This is the field from where the Episode Author should come.'),
      '#title' => $this->t('Episode Author'),
      '#type' => 'select',
      '#options' => $node_fields_array,
    ];
    $form['general']['simplepodcast_item_subtitle'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_subtitle'),
      '#description' => $this->t('This is the field from where the Episode Sub-Title should come.'),
      '#title' => $this->t('Episode Sub-Title'),
      '#type' => 'select',
      '#options' => $node_fields_array,
    ];
    $form['general']['simplepodcast_item_summary'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_summary'),
      '#description' => $this->t('This is the field from where the Episode Summary should come.'),
      '#title' => $this->t('Episode Summary'),
      '#type' => 'select',
      '#options' => $node_fields_array,
    ];
    $form['general']['simplepodcast_item_image'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_image'),
      '#description' => $this->t('This is the field from where the Episode Image should come.'),
      '#title' => $this->t('Episode Image'),
      '#type' => 'select',
      '#options' => $node_fields_array,
    ];
    $form['general']['simplepodcast_item_media'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_media'),
      '#description' => $this->t('This is the field from where the Episode Enclosure Media should come.'),
      '#title' => $this->t('Episode Media'),
      '#type' => 'select',
      '#options' => $node_fields_array,
    ];
    $form['general']['simplepodcast_item_media_length'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_media_length'),
      '#description' => $this->t('This is the field from where the length in bytes of the media should come.'),
      '#title' => $this->t('Episode Media Length'),
      '#type' => 'select',
      '#options' => $node_fields_array,
    ];
    $form['general']['simplepodcast_item_media_duration'] = [
      '#default_value' => \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_media_duration'),
      '#description' => $this->t('This is the field from where the duration in seconds of the media should come.'),
      '#title' => $this->t('Episode Media Duration'),
      '#type' => 'select',
      '#options' => $node_fields_array,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);


    if (!preg_match('/^[a-z0-9_]+$/', $form_state->getValue('simplepodcast_content_type'))) {
      $form_state->setErrorByName('simplepodcast_content_type', t('A valid Drupal machine name is lowercase letters \'a-z\', numbers \'0-9\', and underscores \'_\''));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('simplepodcast.settings');
    $config
      ->set('episode_content_type', $form_state->getValue('simplepodcast_content_type'))
      ->set('rss_path_name', $form_state->getValue('simplesimplepodcast_path_name'))
      ->set('title', $form_state->getValue('simplepodcast_title'))
      ->set('description', $form_state->getValue('simplepodcast_description'))
      ->set('copyright', $form_state->getValue('simplepodcast_copyright'))
      ->set('explicit', $form_state->getValue('simplepodcast_explicit'))
      ->set('owner_name', $form_state->getValue('simplepodcast_owner_name'))
      ->set('owner_author', $form_state->getValue('simplepodcast_owner_author'))
      ->set('owner_email', $form_state->getValue('simplepodcast_owner_email'))
      ->set('channel_image', $form_state->getValue('simplepodcast_channel_image'))
      ->set('category_1', $form_state->getValue('simplepodcast_category_1'))
      ->set('category_2', $form_state->getValue('simplepodcast_category_2'))
      ->set('category_3', $form_state->getValue('simplepodcast_category_3'))
      ->set('item_title', $form_state->getValue('simplepodcast_item_title'))
      ->set('item_author', $form_state->getValue('simplepodcast_item_author'))
      ->set('item_subtitle', $form_state->getValue('simplepodcast_item_subtitle'))
      ->set('item_summary', $form_state->getValue('simplepodcast_item_summary'))
      ->set('item_image', $form_state->getValue('simplepodcast_item_image'))
      ->set('item_media', $form_state->getValue('simplepodcast_item_media'))
      ->set('item_media_length', $form_state->getValue('simplepodcast_item_media_length'))
      ->set('item_media_duration', $form_state->getValue('simplepodcast_item_media_duration'))
      ->save();

    parent::submitForm($form, $form_state);
    \Drupal::service("router.builder")->rebuild();
  }
}
