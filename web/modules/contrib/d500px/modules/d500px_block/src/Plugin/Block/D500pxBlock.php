<?php

namespace Drupal\d500px_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\d500px\D500pxPhotos;

/**
 * Provides d500px Block.
 *
 * @Block(
 *   id = "d500px_block",
 *   admin_label = @Translation("500px block"),
 * )
 */
class D500pxBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * D500px Photos.
   *
   * @var \Drupal\d500px\D500pxPhotos
   */
  protected $d500pxphotos;

  /**
   * D500pxBlock constructor.
   *
   * @param array $configuration
   *   Config.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin Def.
   * @param \Drupal\d500px\D500pxPhotos $d500pxphotos
   *   D500px Photos.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              D500pxPhotos $d500pxphotos) {
    // Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->d500pxphotos = $d500pxphotos;
  }

  /**
   * Create for DI.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   * @param array $configuration
   *   Config.
   * @param string $plugin_id
   *   Plugin.
   * @param mixed $plugin_definition
   *   Plugin def.
   *
   * @return static
   *   Static
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('d500px.D500pxPhotos')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // TODO Bring back some cache controls.
    $build = ['#cache' => ['max-age' => 0]];

    $params = [
      'feature'       => $config['feature'],
      'rpp'           => $config['rpp'],
      'image_size'    => $config['image_size'],
      'sort'          => $config['sort'],
    ];

    // Add category if its not all.
    if ($config['only'] != '- All -') {
      $params += ['only' => $config['only']];
    }

    // Add username.
    if (!empty($config['username'])) {
      $params += ['username' => $config['username']];
    }

    // Get some pics.
    // TODO Error handling, what if $content is NULL?
    $content = $this->d500pxphotos->getPhotos($params, $config['nsfw']);

    // Check if there are any photos firstly.
    if (empty($content)) {
      $build['#markup'] = $this->t('No Pics!');
      return $build;
    }

    $build['content'] = $content;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['d500px_block_block_common'] = [
      '#type'               => 'fieldset',
      '#title'              => $this->t('500px Block Settings'),
      '#collapsible'        => FALSE,
      '#collapsed'          => FALSE,
    ];

    $form['d500px_block_block_common']['rpp'] = [
      '#type'               => 'select',
      '#title'              => $this->t('Number of photos to display?'),
      '#options'            => array_combine(range(5, 100, 5), range(5, 100, 5)),
      '#default_value'      => isset($config['rpp']) ? $config['rpp'] : 5,
      '#description'        => $this->t('The number of results to return. Can not be over 100, default is 5.'),
    ];

    $form['d500px_block_block_common']['feature'] = [
      '#type'               => 'select',
      '#title'              => $this->t('Photo stream to be retrieved?'),
      '#options'            => $this->d500pxphotos->d500pxhelpers->availableFeatures(),
      '#default_value'      => isset($config['feature']) ? $config['feature'] : 'fresh_today',
      '#description'        => $this->t('Photo stream to be retrieved. Default fresh_today.'),
    ];

    $form['d500px_block_block_common']['username'] = [
      '#type'               => 'textfield',
      '#title'              => $this->t('Username'),
      '#default_value'      => isset($config['username']) ? $config['username'] : '',
      '#description'        => $this->t('Selected stream requires a user_id or username parameter.'),
      '#element_validate'   => [[$this, 'usernameElementValidator']],
      '#states' => [
        'visible' => [
            [':input[name="settings[d500px_block_block_common][feature]"]' => ['value' => 'user']],
            [':input[name="settings[d500px_block_block_common][feature]"]' => ['value' => 'user_friends']],
        ],
      ],
    ];

    $image_options_available = $this->d500pxphotos->d500pxhelpers->photoGetSizes();
    foreach ($image_options_available as $image_option_key => $value) {
      $image_options[$image_option_key] = $value['width'] . 'x' . $value['height'];
    }

    $form['d500px_block_block_common']['image_size'] = [
      '#type'               => 'select',
      '#title'              => $this->t('Thumbnail size:'),
      '#options'            => $image_options,
      '#default_value'      => isset($config['image_size']) ? $config['image_size'] : 2,
      '#description'        => $this->t('The photo size to be displayed.'),
    ];

    $available_categories = $this->d500pxphotos->d500pxhelpers->availableCategories();
    foreach ($available_categories as $value) {
      $categories[$value] = $value;
    }

    $form['d500px_block_block_common']['only'] = [
      '#type'               => 'select',
      '#title'              => $this->t('Photo Category'),
      '#options'            => $categories,
      '#default_value'      => isset($config['only']) ? $config['only'] : '- All -',
      '#description'        => $this->t('If you want results from a specific category'),
    ];

    $form['d500px_block_block_common']['sort'] = [
      '#type'               => 'select',
      '#title'              => $this->t('Sort photos in the specified order'),
      '#options'            => $this->d500pxphotos->d500pxhelpers->availableSortOptions(),
      '#default_value'      => isset($config['sort']) ? $config['sort'] : 'created_at',
      '#description'        => $this->t('Sort photos in the specified order'),
    ];

    $form['d500px_block_block_common']['nsfw'] = [
      '#type'               => 'checkbox',
      '#title'              => $this->t('Display NSFW photos?'),
      '#default_value'      => isset($config['nsfw']) ? $config['nsfw'] : FALSE,
      '#description'        => $this->t('Some photos on 500px are "Not Safe For Work" (or children), use with care. By default all NSFW images will be blacked out.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function usernameElementValidator(&$element, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (($values['settings']['d500px_block_block_common']['feature'] == 'user'
        or $values['settings']['d500px_block_block_common']['feature'] == 'user_friends')
        and (empty($element['#value']))) {
      $form_state->setError($element, t("Additional parameter 'username' is required"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['rpp'] = $form_state->getValue(['d500px_block_block_common', 'rpp']);
    $this->configuration['feature'] = $form_state->getValue(['d500px_block_block_common', 'feature']);
    $this->configuration['nsfw'] = $form_state->getValue(['d500px_block_block_common', 'nsfw']);
    $this->configuration['image_size'] = $form_state->getValue(['d500px_block_block_common', 'image_size']);
    $this->configuration['only'] = $form_state->getValue(['d500px_block_block_common', 'only']);
    $this->configuration['sort'] = $form_state->getValue(['d500px_block_block_common', 'sort']);
    $this->configuration['username'] = $form_state->getValue(['d500px_block_block_common', 'username']);
  }

}
