<?php

namespace Drupal\regex_redirect\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\regex_redirect\RegexRedirectRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Plugin implementation of the 'link' widget for the regex redirect module.
 *
 * This class and its functions are based on the RedirectSourceWidget from the
 * config redirect module. Note that this field is meant only for the source
 * field of the redirect entity as it drops validation for non existing paths.
 *
 * @FieldWidget(
 *   id = "regex_redirect_source",
 *   label = @Translation("Regex Redirect source"),
 *   field_types = {
 *     "link"
 *   },
 *   settings = {
 *     "placeholder_url" = "",
 *     "placeholder_title" = ""
 *   }
 * )
 */
class RegexRedirectSourceWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  /**
   * The router service.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * The regex redirect repository.
   *
   * @var \Drupal\regex_redirect\RegexRedirectRepository
   */
  protected $repository;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Symfony\Component\Routing\RouterInterface $router
   *   The router service.
   * @param \Drupal\regex_redirect\RegexRedirectRepository $repository
   *   The regex redirect repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, RouterInterface $router, RegexRedirectRepository $repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->thirdPartySettings = $third_party_settings;
    $this->router = $router;
    $this->repository = $repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('router'),
      $container->get('regex_redirect.repository')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_url_value = $items[$delta]->path;

    $element['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      '#default_value' => $default_url_value,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
      '#field_prefix' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
      '#attributes' => ['data-disable-refocus' => 'true'],
    ];

    // If creating new URL add checks.
    if ($items->getEntity()->isNew()) {
      $element['status_box'] = [
        '#prefix' => '<div id="redirect-link-status">',
        '#suffix' => '</div>',
      ];

      $source_path = $form_state->getValue(['regex_redirect_source', 0, 'path']);
      if ($source_path) {
        $source_path = trim($source_path);

        // Warning about creating a redirect from a valid path.
        // @todo: fix exception driven logic when this has been done by
        // redirect module. Also fix for dependency injections at that moment.
        // Determine if we have a valid path.
        try {
          $this->router->match('/' . $form_state->getValue([
            'regex_redirect_source',
            0,
            'path',
          ]));
          $element['status_box'][]['#markup'] = '<div class="messages messages--warning">' . $this->t('The source path %path is likely a valid path. It is preferred to <a href="@url-alias">create URL aliases</a> for existing paths rather than redirects.',
              ['%path' => $source_path, '@url-alias' => Url::fromRoute('path.admin_add')->toString()]) . '</div>';
        }
        catch (ResourceNotFoundException $e) {
          // Do nothing, expected behaviour.
        }

        // Warning about the path being already redirected.
        $parsed_url = UrlHelper::parse($source_path);
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : NULL;
        if (!empty($path)) {
          $redirects = $this->repository->findBySourcePath($path);
          if (!empty($redirects)) {
            $redirect = array_shift($redirects);
            $element['status_box'][]['#markup'] = '<div class="messages messages--warning">' . $this->t('The base source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?', ['%source' => $source_path, '@edit-page' => $redirect->url('edit-form')]) . '</div>';
          }
        }
      }

      $element['path']['#ajax'] = [
        'callback' => 'regex_redirect_source_link_get_status_messages',
        'wrapper' => 'redirect-link-status',
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

}
