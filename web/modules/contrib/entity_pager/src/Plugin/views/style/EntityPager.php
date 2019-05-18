<?php

namespace Drupal\entity_pager\Plugin\views\style;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Annotation\ViewsStyle;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render a view for an Entity Pager.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "entity_pager",
 *   title = @Translation("Entity Pager"),
 *   help = @Translation("Displays extra information on a Entity such as Next and Previous links."),
 *   theme = "entity_pager",
 *   display_types = { "normal" }
 * )
 */
class EntityPager extends StylePluginBase {
  protected $usesRowPlugin = FALSE;

  protected $usesRowClass = FALSE;

  protected $usesFields = TRUE;

  protected $usesOptions = TRUE;

  /**
   * Returns an array of default options for the entity pager.
   *
   * @return array
   *   The default options.
   */
  protected function getDefaultOptions() {
    return [
      'relationship' => NULL,
      'link_next' => 'next >',
      'link_prev' => '< prev',
      'link_all_url' => '<front>',
      'link_all_text' => 'Home',
      'display_all' => TRUE,
      'display_count' => TRUE,
      'show_disabled_links' => TRUE,
      'circular_paging' => FALSE,
      'log_performance' => TRUE,
    ];
  }

  /**
   * Returns a value for an option.
   *
   * @param string $name
   *   The option name.
   *
   * @return mixed
   *   The option value, falling back to the default.
   */
  protected function getOption($name) {
    $defaults = $this->getDefaultOptions();

    return (isset($this->options[$name])) ? $this->options[$name] : $defaults[$name];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $defaults = $this->getDefaultOptions();

    return parent::defineOptions() + [
        'relationship' => ['default' => $defaults['relationship']],
        'link_next' => ['default' => $defaults['link_next']],
        'link_prev' => ['default' => $defaults['link_prev']],
        'link_all_url' => ['default' => $defaults['link_all_url']],
        'link_all_text' => ['default' => $defaults['link_all_text']],
        'display_all' => ['default' => $defaults['display_all']],
        'display_count' => ['default' => $defaults['display_count']],
        'show_disabled_links' => ['default' => $defaults['show_disabled_links']],
        'circular_paging' => ['default' => $defaults['circular_paging']],
        'log_performance' => ['default' => $defaults['log_performance']],
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $relationship_options = $this->getRelationshipOptions();
    if (!empty($relationship_options)) {
      $form['relationship'] = [
        '#title' => $this->t('Relationship'),
        '#description' => $this->t('Optionally, select a relationship to link to the related entity.'),
        '#type' => 'select',
        '#options' => $relationship_options,
        '#empty_option' => $this->t('None'),
        '#default_value' => $this->getOption('relationship'),
      ];
    }

    $form['link_next'] = [
      '#title' => $this->t('Next label'),
      '#description' => $this->t('The text to display for the Next link. HTML is allowed.'),
      '#type' => 'textfield',
      '#default_value' => $this->getOption('link_next'),
    ];

    $form['link_prev'] = [
      '#title' => $this->t('Previous label'),
      '#description' => $this->t('The text to display for the Previous link. HTML is allowed.'),
      '#type' => 'textfield',
      '#default_value' => $this->getOption('link_prev'),
    ];

    $form['display_all'] = [
      '#title' => $this->t('Display All link'),
      '#description' => $this->t('Display a link to the parent page of all results.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getOption('display_all'),
    ];

    $form['link_all_url'] = [
      '#title' => $this->t('All link URL'),
      '#description' => $this->t('The URL of the listing page link.<br>
          Examples:
          <ul>
              <li>the URL of a Views listing page of the Entities.</li>
              <li>@front for the homepage</li>
              <li>a <a href="/admin/help/token">token</a> that relates to the Entity. (e.g. [node:edit-url])</li>
              <li>The token can also be an entity reference if the entity has one. (e.g. [node:field_company])</li>
          </ul>', ['@front' => '<front>']),
      '#type' => 'textfield',
      '#default_value' => $this->getOption('link_all_url'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[display_all]"]' => ['checked' => TRUE],
        ]
      ],
    ];

    $form['link_all_text'] = [
      '#title' => $this->t('All link label'),
      '#description' => $this->t("The label text to display for the List All link.
          <ul>
              <li>When an entity reference is used in the <strong>List All URL</strong> box above, just add the same entity reference in this box and the referenced entity title will automatically be displayed.</li>
              <li>HTML is allowed.</li>
          </ul>"
      ),
      '#type' => 'textfield',
      '#default_value' => $this->getOption('link_all_text'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[display_all]"]' => ['checked' => TRUE],
        ]
      ],
    ];

    $form['display_count'] = [
      '#title' => $this->t('Display count'),
      '#description' => $this->t('Display the number of records (e.g. 5 of 8)'),
      '#type' => 'checkbox',
      '#default_value' => $this->getOption('display_count'),
    ];

    $form['circular_paging'] = [
      '#title' => $this->t('Circular paging'),
      '#description' => $this->t('When the last item is active, link to first item and vice versa.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getOption('circular_paging'),
    ];

    $form['show_disabled_links'] = [
      '#title' => $this->t('Show disabled links'),
      '#description' => $this->t('Show disabled next/prev links when on the first or last page.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getOption('show_disabled_links'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[circular_paging]"]' => ['checked' => FALSE],
        ]
      ],
    ];

    $form['log_performance'] = [
      '#title' => $this->t('Log performance suggestions'),
      '#description' => $this->t('Log performance suggestions to Watchdog, see: Reports > Recent log messages.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getOption('log_performance'),
    ];
  }

  protected function getRelationshipOptions() {
    $executable = $this->view;
    $relationships = $executable->display_handler->getOption('relationships');
    $options = [];

    if (!empty($relationships)) {
      foreach ($relationships as $relationship) {
        $options[$relationship['id']] = $relationship['admin_label'];
      }
    }

    return $options;
  }

}
