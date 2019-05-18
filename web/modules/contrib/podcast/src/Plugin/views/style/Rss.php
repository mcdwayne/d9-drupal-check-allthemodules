<?php

namespace Drupal\podcast\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\podcast\PodcastViewsMappingsTrait;
use Drupal\views\Plugin\views\style\Rss as ViewsRss;

/**
 * Default style plugin to render an RSS feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "podcast_rss",
 *   title = @Translation("Podcast RSS Feed"),
 *   help = @Translation("Generates a podcast RSS feed from a view."),
 *   theme = "views_view_rss",
 *   display_types = {"feed"}
 * )
 */
class Rss extends ViewsRss {

  use PodcastViewsMappingsTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    unset($options['description']);
    $keys = [
      'copyright',
      'title_field',
      'description_field',
      'link_field',
      'author_field',
      'itunes:explicit_field',
      'itunes:owner--name_field',
      'itunes:owner--email_field',
      'itunes:author_field',
      'itunes:summary_field',
      'itunes:keywords_field',
      'itunes:image_field',
      'itunes:category_field',
      'itunes:new-feed-url_field',
    ];
    $options = array_reduce($keys, function ($options, $key) {
      $options[$key] = ['default' => ''];
      return $options;
    }, $options);
    $options['generator'] = ['default' => 'Podcast module for Drupal'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $initial_labels = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);
    unset($form['description']);

    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['title_field'],
      '#description' => $this->t('Podcast name to display in the feed. Defaults to the view name.'),
      '#maxlength' => 1024,
      '#required' => TRUE,
    ];
    $form['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Description field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['description_field'],
      '#description' => $this->t('Podcast description to display in the feed.'),
      '#maxlength' => 1024,
      '#required' => FALSE,
    ];
    $form['link_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Link field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['link_field'],
      '#description' => $this->t('Podcast link to the feed.'),
      '#maxlength' => 1024,
      '#required' => TRUE,
    ];
    $form['lastBuildDate_field'] = [
      '#type' => 'select',
      '#title' => $this->t('lastBuildDate field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['lastBuildDate_field'],
      '#description' => $this->t('When the feed was last build.'),
      '#maxlength' => 1024,
      '#required' => TRUE,
    ];
    $form['generator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Generator'),
      '#default_value' => $this->options['generator'],
      '#description' => $this->t('Enter the text you want to display on how this feed was generated.'),
      '#maxlength' => 1024,
    ];
    $form['copyright'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Copyright'),
      '#default_value' => $this->options['copyright'],
      '#description' => $this->t('Copyright notice for the podcast.'),
      '#maxlength' => 1024,
    ];
    $form['author_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Feed Author'),
      '#description' => $this->t('List of owner names names for the feed.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['author_field'],
      '#required' => FALSE,
    ];
    $form['itunes:explicit_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Explicit field'),
      '#description' => $this->t('Signal iTunes weather or not this podcast is explicit. Expects a boolean.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:explicit_field'],
      '#required' => FALSE,
    ];
    $form['itunes:owner--name_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Feed Owner Name'),
      '#description' => $this->t('Owner name for the iTunes feed.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:owner--name_field'],
      '#required' => FALSE,
    ];
    $form['itunes:owner--email_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Feed Owner E-mail'),
      '#description' => $this->t('Owner email for the iTunes feed.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:owner--email_field'],
      '#required' => FALSE,
    ];
    $form['itunes:author_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Feed Author'),
      '#description' => $this->t('List of owner names names for the iTunes feed.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:author_field'],
      '#required' => FALSE,
    ];
    $form['itunes:summary_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Summary'),
      '#description' => $this->t('Summary to be displayed in iTunes.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:summary_field'],
      '#required' => FALSE,
    ];
    $form['itunes:keywords_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Keywords'),
      '#description' => $this->t('Keywords to be displayed in iTunes.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:keywords_field'],
      '#required' => FALSE,
    ];
    $form['itunes:image_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Image'),
      '#description' => $this->t('Image to be displayed in iTunes.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:image_field'],
      '#required' => FALSE,
    ];
    $form['itunes:category_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Category'),
      '#description' => $this->t('Categories to be displayed in iTunes. Processor expects "$category/$subcategory". Multivalues are coma separated.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:category_field'],
      '#required' => FALSE,
    ];
    $form['itunes:new-feed-url_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes New Feed URL field'),
      '#description' => $this->t('The URL to the new iTunes feed. This is used when moving the feed from one URL to another.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:new-feed-url_field'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getChannelElements() {
    $channel_elements = parent::getChannelElements();
    $namespaces = is_array($this->namespaces) ? $this->namespaces : [];
    $this->namespaces = array_merge($namespaces, [
      'xmlns:itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd',
      'xmlns:content' => 'http://purl.org/rss/1.0/modules/content/',
      'xmlns:atom' => 'http://www.w3.org/2005/Atom',
    ]);
    return $channel_elements;
  }

  /**
   * Return an array of additional XHTML elements to add to the podcast channel.
   *
   * @return array
   *   A keyval array.
   */
  protected function getPodcastElements() {
    $podcast_elements = [];
    $podcast_elements[] = [
      'key' => 'generator',
      'value' => $this->options['generator'],
    ];
    $podcast_elements[] = [
      'key' => 'copyright',
      'value' => $this->options['copyright'],
    ];
    $keys = [
      'title',
      'description',
      'lastBuildDate',
      'author',
      'itunes:explicit',
      'itunes:author',
      'itunes:summary',
      'itunes:keywords',
    ];
    $podcast_elements = array_merge(
      array_map([$this, 'buildElementFromOptions'], $keys),
      $podcast_elements
    );
    $owner_name = $this->getField(0, $this->options['itunes:owner--name_field']);
    $owner_email = $this->getField(0, $this->options['itunes:owner--email_field']);
    if (!empty($owner_email) || !empty($owner_name)) {
      $podcast_elements[] = [
        'key' => 'itunes:owner',
        'values' => [
          ['key' => 'itunes:name', 'value' => $owner_name],
          ['key' => 'itunes:email', 'value' => $owner_email],
        ],
      ];
    }
    $link_keys = ['link', 'itunes:image', 'itunes:new-feed-url'];
    $podcast_elements = array_reduce($link_keys, function ($elements, $key) {
      return array_merge($elements, [$this->buildElementForLink($key)]);
    }, $podcast_elements);
    $categories = $this->buildElementFromOptions('itunes:category');
    if (!empty($categories)) {
      $podcast_elements[] = $this->processCategories($categories);
    }
    return $podcast_elements;
  }

  /**
   * Processes categories to output the format expected by iTunes.
   *
   * @param array $element
   *   The keyvalue to process.
   *
   * @return array
   *   The processed keyval.
   */
  protected function processCategories(array $element) {
    $tag_name = 'itunes:category';
    /** @var string[] $values */
    $values = array_map('trim', explode(',', $element['value']));
    $values = array_map('htmlentities', $values);
    // We need to parse out an optional leading category.
    $hierarchical_categories = array_reduce($values, function ($carry, $value) {
      $parts = explode('/', $value);
      if (empty($parts)) {
        return $carry;
      }
      $category = array_shift($parts);
      // Initialize the category section if it does not exist.
      if (!array_key_exists($category, $carry)) {
        $carry[$category] = [];
      }
      if (empty($parts)) {
        return $carry;
      }
      $carry[$category][] = array_shift($parts);
      return $carry;
    }, []);
    if (empty($hierarchical_categories)) {
      return [];
    }
    $element['attributes'] = [];
    // iTunes only supports a single top-level category.
    $category = array_keys($hierarchical_categories)[0];
    $subcategories = $hierarchical_categories[$category];
    $element['attributes']['text'] = $category;
    if (!empty($subcategories)) {
      $element['value'] = Markup::create(
        implode(
          array_map(function ($subcategory) use ($tag_name) {
            return sprintf('<%s text="%s"/>', $tag_name, $subcategory);
          }, $subcategories)
        )
      );
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $this->podcast_elements = array_merge(
      $this->getPodcastElements(),
      isset($this->podcast_elements) ? $this->podcast_elements : []
    );
    return $build;
  }

}
