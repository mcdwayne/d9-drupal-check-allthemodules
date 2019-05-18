<?php

namespace Drupal\content_parser\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_parser\ContentParserHelperService;
use Drupal\content_parser\FieldLoaderService;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\content_parser\Results;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;

/**
 * Class ContentParserForm.
 */
class ContentParserForm extends EntityForm {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Entity\EntityTypeBundleInfoInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The content_parser field loader.
   *
   * @var \Drupal\content_parser\FieldLoaderService
   */
  protected $field_loader;

  /**
   * Batch Builder.
   *
   * @var \Drupal\Core\Batch\BatchBuilder
   */
  protected $batchBuilder;

  /**
   * Constructs a ContentParserForm object.
   *
   * @param \Drupal\content_parser\ContentParserHelperService $helper
   *   The content_parser helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, FieldLoaderService $field_loader) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->field_loader = $field_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('content_parser.field_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#attached']['library'][] = 'content_parser/textarea';

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => 'Название задания',
      '#description' => 'Будет отображаться в списке с заданиями. Пример заполнения: <code>Парсинг проектов на drupal.org</code>',
      '#required' => TRUE,
      '#default_value' => $this->entity->label(),
      '#maxlength' => 255,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\content_parser\Entity\ContentParser::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['start_url'] = [
      '#type' => 'textarea',
      '#title' => 'Стартовый URL',
      '#description' => '
        Адрес, с которого парсер начнёт работу. Пример заполнения: <code>https://drupal.org/project/project_theme</code><br />
        В адресе можно использовать маску для числовых значений в формате <code>[mask:min,max]</code>, например: <code>https://drupal.org/project/project_theme?page=[mask:0,49]</code><br />
        В поле можно указать несколько адресов, каждый на новой строке.
      ',
      '#required' => TRUE,
      '#default_value' => $this->entity->getStartUrl(),
    ];

    $form['depth'] = [
      '#type' => 'number',
      '#title' => 'Глубина',
      '#description' => '
        Глубина, на которую парсер будет ходить по ссылкам. Например 1 означает, что будет распарсен стартовый адрес и
        страницы на которые он ссылается, т.е. парсер пройдёт вглубь на 1 уровень относительно стартовой страницы.
        Укажите 0, если хотите распарсить только стартовый URL.
      ',
      '#required' => TRUE,
      '#default_value' => $this->entity->getDepth(),
      '#size' => 5,
    ];

    $form['white_list'] = [
      '#type' => 'textarea',
      '#title' => 'Белый список адресов',
      '#description' => '
        Маски адресов, по которым будет разрешено ходить парсеру. Каждая маска на новой строке. Оставьте пустым, если
        парсеру разрешено ходить по всем найденным ссылкам. Регулярные выражения заключаются в символ <code>#</code>.
        Пример заполнения: <code>http://drupal.org/project/*</code> или <code>#/project/(uc|dc|commerce)_#</code>
      ',
      '#default_value' => $this->entity->getWhiteList(),
      '#rows' => 3,
    ];

    $form['black_list'] = [
      '#type' => 'textarea',
      '#title' => 'Чёрный список адресов',
      '#description' => '
        Маски адресов, по которым будет запрещено ходить парсеру. Каждая маска на новой строке. Регулярные выражения
        заключаются в символ <code>#</code>. Пример заполнения: <code>http://drupal.org/project/uc_*</code> или
        <code>#/project/(uc|dc|commerce)_#</code>
      ',
      '#default_value' => $this->entity->getBlackList() ? $this->entity->getBlackList() : implode("\n", [
        '*.jpg',
        '*.png',
        '*.gif',
        '*.zip',
        '*.rar',
        '*.pdf',
        '*.doc',
        '*.xls',
        '*.txt',
      ]),
      '#rows' => 3,
    ];

    $form['test_url'] = [
      '#type' => 'textfield',
      '#title' => 'URL тестовой страницы',
      '#description' => '
        Адрес любой страницы, попадающей под условие парсинга. Страница будет использоваться для проверки
        работоспособности кода. Пример заполнения: <code>http://drupal.org/project/zen</code>
      ',
      '#required' => TRUE,
      '#default_value' => $this->entity->getTestUrl(),
      '#maxlength' => 255,
    ];

    $form['check_code'] = [
      '#type' => 'textarea',
      '#title' => 'Код проверки для дальнейшего парсинга страницы',
      '#description' => '
        PHP код без тегов &lt;?php и ?&gt;, который должен вернуть TRUE если страницу нужно парсить в сущность.
        Доступные переменные: $page - html код страницы, $doc - объект phpQuery, $page_url - адрес страницы без учёта редиректов.
        Пример заполнения: <code>return ($doc->find(\'.class-name\')->length() > 0);</code>
      ',
      '#default_value' => $this->entity->getCheckCode()
    ];

    $form['check_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Проверить'),
      '#submit' => ['::test'],
      '#ajax' => [
        'callback' => [$this, 'check_modal']
      ],
    ];

    $form['entity_wrapper'] = [
      '#prefix' => '<div id="entity-wrapper">',
      '#suffix' => '</div>'
    ];
    
    $entity_type = $this->entity->getSelectEntityType();
    $bundle = $this->entity->getSelectBundle();
    $bundles = $this->getOptionsBundles($entity_type);
    $input_bundle = $form_state->getValue('bundle');

    if ($input_bundle && !isset($bundles[$input_bundle])) {
      if (count($bundles) > 0) {
        $keys = array_keys($bundles);
        $bundle = $keys[0];
      } else {
        $bundle = null;
      }
    }

    $form['entity_wrapper']['entity_type'] = [
      '#type' => 'select',
      '#title' => 'Тип сущности (entity type)',
      '#description' => 'Выберите тип сущности, который будет создавать парсер.',
      '#options' => $this->getOptionsDefinitions(),
      '#default_value' => $entity_type,
      '#ajax' => [
        'callback' => [$this, 'update_wrapper'],
        'wrapper' => 'entity-wrapper',
      ]
    ];

    $form['entity_wrapper']['bundle'] = [
      '#type' => 'select',
      '#title' => 'Подтип сущности (bundle)',
      '#description' => 'Выберите подтип сущности, который будет создавать парсер.',
      '#options' => $bundles,
      '#default_value' => $bundle,
      '#ajax' => [
        'callback' => [$this, 'update_wrapper'],
        'wrapper' => 'entity-wrapper',
      ]
    ];

    $this->buildFieldsInterface($form, $form_state, $entity_type, $bundle);
    $this->buildSettingsInterface($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function update_wrapper(array $form, FormStateInterface $form_state) {
    return $form['entity_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFieldsInterface(array &$form, FormStateInterface $form_state, $entity_type, $bundle) {
    $codes = $this->entity->getCodes();

    $form['entity_wrapper']['details'] = [
      '#type' => 'details',
      '#title' => t('Поля'),
      '#open' => TRUE,
      '#description' => '
      <p>Поля заполняются без обёртки кода в &lt;?php и ?&gt;. Во всех полях доступны следующие переменные:</p>
      <p>
        $doc - объект phpQuery для страницы<br />
        $entity - объект сущности<br />
        $base_url - адрес страницы<br />
      </p>
      <p>Оставьте textarea пустым или верните NULL, если поле заполнять не нужно.</p>
      <p>
        Полезные функции:<br />
        <code>$this->getEntityByRemoteId($remote_id)</code>
          - возвращает id созданной сущности по Remote ID.<br />
        <code>_content_parser_retrieve_images($doc, $query)</code>
          - Загружает все изображения в запросе и возращает массив сущностей файло<br />
      </p>
      </br>

    ',
    ];

    $form['entity_wrapper']['details']['fields_tabs'] = [
      '#type' => 'vertical_tabs',
    ];



    $fields = $this->field_loader->load($entity_type, $bundle);

    $form['codes'] = [
      '#type' => 'container',
      '#tree' => true
    ];

    foreach ($fields as $name => $field) {
      $tab = [
        '#type' => 'details',
        '#title' => $field['title'],
        '#group' => 'fields_tabs',
        '#open' => TRUE
      ];

      $tab['description'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $field['text']
      ];

      $tab['code'] = [
        '#type' => 'textarea',
        '#rows' => 10,
        '#title' => $field['code_title'],
        '#description' => $field['description'],
        '#default_value' => isset($codes[$name]['code']) ? $codes[$name]['code'] : $field['default_value']
      ];

      $tab['check_field'] = [
        '#type' => 'submit',
        '#value' => $this->t('Проверить'),
        '#name' => 'check_field_' . $name,
        '#submit' => ['::test'],
        '#ajax' => [
          'callback' => [$this, 'check_field']
        ],
      ];

      $tab['reference_create'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Создавать сущности'),
        '#default_value' => isset($codes[$name]['reference_create']) ? $codes[$name]['reference_create'] : 0,
        '#access' => boolval($field['reference'])
      ];

      $tab['example_create'] = [
        '#type' => 'container',
        'code' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $field['example']
        ],
        '#states' => [
          'visible' => [
            ':input[name="codes[' . $name . '][reference_create]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $tab['example_exist'] = [
        '#type' => 'container',
        '#access' => boolval($field['reference']),
        'code' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $field['example_exist']
        ],
        '#states' => [
          'visible' => [
            ':input[name="codes[' . $name . '][reference_create]"]' => ['checked' => false],
          ],
        ],
      ];

      $tab['reference'] = [
        '#type' => 'hidden',
        '#default_value' => $field['reference']
      ];

      $tab['isMulti'] = [
        '#type' => 'hidden',
        '#default_value' => $field['isMulti'] ? '1' : ''
      ];

      $form['codes'][$name] = $tab;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSettingsInterface(array &$form, FormStateInterface $form_state) {
    $form['settings'] = [
      '#type' => 'details',
      '#title' => 'Дополнительные настройки',
      '#open' => false,
      '#tree' => true
    ];
    
    $form['settings']['parse_limit'] = [
      '#type' => 'textfield',
      '#title' => 'Ограничить число создаваемых/обновляемых сущностей',
      '#description' => '
        Максимальное число сущностей, которое сможет создать/обновить парсер. Применяется в осномном при тестировании
        задания. Оставьте пустым, если ограничения не нужны.
      ',
      '#default_value' => $this->entity->getSetting('parse_limit'),
      '#size' => 5,
    ];
    
    $form['settings']['sleep'] = [
      '#type' => 'textfield',
      '#title' => 'Задержка между http запросами',
      '#description' => '
        Количество секунд между http запросами к сайту источнику. Помогает обойти защиту от парсинга. По умолчанию
        задержка отсутствует.
      ',
      '#default_value' => $this->entity->getSetting('sleep'),
      '#size' => 5,
      '#field_suffix' => 'сек.',
    ];
    
    $form['settings']['only_this_domen'] = [
      '#type' => 'checkbox',
      '#title' => 'Парсить только с этого же домена',
      '#description' => 'Отметьте, если хотите чтобы парсер работал только на домене, указанном в поле "Стартовый URL".',
      '#default_value' => $this->entity->getSetting('only_this_domen')
    ];
    
    $form['settings']['save_url'] = [
      '#type' => 'checkbox',
      '#title' => 'Сохранять адреса',
      '#description' => 'Отметьте, если хотите, чтобы у сущностей были такие же адреса, как на сайте источнике. Не включайте эту опцию если на сайте источнике не используется ЧПУ (т.е. в адресах есть символ "?").',
      '#default_value' => $this->entity->getSetting('save_url')
    ];
    
    $form['settings']['no_update'] = [
      '#type' => 'checkbox',
      '#title' => 'Не обновлять сущности',
      '#description' => 'Отметьте, если не хотите обновлять сущности, созданные в предыдущие запуски этого задания.',
      '#default_value' => $this->entity->getSetting('no_update'),
    ];
    
    $form['settings']['list_mode'] = [
      '#type' => 'checkbox',
      '#title' => 'Режим списка',
      '#description' => 'Отметьте, если сущности на сайте источнике не имеют своих страниц, а располагаются в виде списков. Например это могут быть комментарии.',
      '#default_value' => $this->entity->getSetting('list_mode')
    ];
    
    $form['settings']['list_code'] = [
      '#type' => 'textarea',
      '#description' => '
        Код, который должен вернуть простой одномерный массив с элементами списка.
        Доступные переменные: $page - html код страницы, $doc - объект phpQuery.
        Пример заполнения:<br />
        <code>
          $elements = [];<br />
          foreach ($doc->find(\'#comments .comment\') as $element) {<br />
          &nbsp;&nbsp;$elements[] = pq($element);<br />
          }<br />
          return $elements;
        </code>
      ',
      '#default_value' => $this->entity->getSetting('list_code'),
      '#rows' => 3,
      '#resizable' => FALSE,
      '#states' => [
        'visible' => [
          'input[name="settings[list_mode]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['settings']['init_code'] = [
      '#type' => 'textarea',
      '#title' => 'Код предварительной инициализации',
      '#description' => '
        Код, который будет выполнен перед загрузкой стартового адреса. Например, здесь можно пройти авторизацию и сохранить
        номер сессии в масив $headers = [] или переменную $cookieJar для дальнейшего использования в HTTP заголовках.
        Пример кода:<br /><br />
        <code>
          $client = \Drupal::httpClient(["cookies" => true])<br />
          &nbsp;->post("http://example.com/user/login", [<br />
          &nbsp;&nbsp;&nbsp;"form_params" => [<br />
          &nbsp;&nbsp;&nbsp;&nbsp;"name" => "admin",<br />
          &nbsp;&nbsp;&nbsp;&nbsp;"pass" => "admin",<br />
          &nbsp;&nbsp;&nbsp;&nbsp;"form_id" => "user_login_form",<br />
          &nbsp;&nbsp;&nbsp;&nbsp;"form_build_id" => "",<br />
          &nbsp;&nbsp;&nbsp;&nbsp;"op" => "Войти"<br />
          &nbsp;&nbsp;&nbsp;]<br />
          &nbsp;]<br />
          );<br />
          <br />
          $cookieJar = $client->getConfig("cookies");
        </code>
      ',
      '#default_value' => $this->entity->getSetting('init_code'),
      '#rows' => 3,
      '#resizable' => FALSE,
    ];

    $form['settings']['prepare_code'] = [
      '#type' => 'textarea',
      '#title' => 'Пост-обработка сущности',
      '#description' => '
        Код, который будет выполнен перед вызовом $entity-save(). Доступные переменные:
          <br />$doc - объект phpQuery.<br />
        Пример заполнения: <code>$entity->set(\'title\', \'new title\');</code>
      ',
      '#default_value' => $this->entity->getSetting('prepare_code'),
      '#rows' => 3,
      '#resizable' => FALSE,
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function test(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function check_field(array $form, FormStateInterface $form_state) {
    $codes = $form_state->getValue('codes');
    $triggering = $form_state->getTriggeringElement();
    $field_name = str_replace('check_field_', '', $triggering['#name']);

    $result = 'Ошибка чтения поля';

    if (isset($codes[$field_name])) {
      $result = $this->entity->runTestUrl(
        $form_state->getValue('test_url'),
        $codes[$field_name]['code']
      );
    }

    return $this->returnModal($result, 'Проверка поля (' . $field_name . ')');
  }

  /**
   * {@inheritdoc}
   */
  public function check_modal(array $form, FormStateInterface $form_state) {
    $result = $this->entity->runTestUrl(
      $form_state->getValue('test_url'),
      $form_state->getValue('check_code')
    );

    return $this->returnModal($result ? 'Успешно' : 'Не успешно', 'Проверка парсера');
  }

  /**
   * {@inheritdoc}
   */
  public function returnModal($html, $title) {
    if (is_array($html)) {
      $html = print_r($html, true);
    }

    $content = [
      '#type' => 'inline_template',
      '#template' => "{{ html|raw }}",
      '#context' => [
        'html' => $html,
      ],
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax'
        ]
      ]
    ];
    
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($title, $content, ['width' => '800']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity = $this->entity;
    $status = $this->entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label ContentParser.', [
          '%label' => $this->entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label ContentParser.', [
          '%label' => $this->entity->label(),
        ]));
    }

    $form_state->setRedirect('entity.content_parser.collection');
  }

  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['save_run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Сохранить и запустить'),
      '#submit' => ['::submitForm', '::save', '::batchUrls'],
    ];

    $actions['run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Запустить'),
      '#submit' => ['::batchUrls'],
    ];

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function batchUrls(array $form, FormStateInterface $form_state) {
    $this->createBatch($this->entity->getStartUrls(), 0, [], $this->entity->evalInitCode());
  }

  /**
   * {@inheritdoc}
   */
  public function createBatch($urls, $depth, $results = [], $options = []) {
    $batchBuilder = new BatchBuilder();

    $batchBuilder
      ->setTitle($this->t('Начался парсинг @title', [
        '@title' => $this->entity->label()
      ]))
      ->setInitMessage($this->t('Инициализация уровня @depth', ['@depth' => $depth]))
      ->setProgressMessage($this->t('Завершено @current из @total.'))
      ->setErrorMessage($this->t('Произошла ошибка.'));

    if ($depth == $this->entity->getDepth()) {
      $batchBuilder->setFinishCallback([$this, 'finished']);
    }

    $index = 0;

    foreach ($urls as $key => $url) {
      $batchBuilder->addOperation([$this, 'parseUrl'], [$url, $depth, $results, $options, ++$index == count($urls)]);
    }

    batch_set($batchBuilder->toArray());
  }

  /**
   * {@inheritdoc}
   */
  public function parseUrl($url, $depth, $results, $options, $last, array &$context) {
    foreach (['urls', 'results', 'processed_urls'] as $key => $value) {
      if (!isset($context['results'][$key]) && $results[$key]) {
        $context['results'][$key] = $results[$key];
      } else {
        $context['results'][$key] = [];
      }
    }

    $context['message'] = $this->t('Парсинг страницы (@url) Уровень: @depth', [
      '@url' => $url,
      '@depth' => $depth
    ]);

    $entity = $this->entity;

    if ($this->limit($context)) {
      return;
    }

    $parse = true;

    if (!$entity->isAllowedUrl($url)) {
      $parse = false;
    }

    $html = $entity->loadUrl($url, $options['headers'], $options['cookieJar']);

    if (!$html) {
      $parse = false;
    }

    $doc = $entity->getPhpQuery($html, $url);

    if (!$doc) {
      $parse = false;
    }

    $context['results']['processed_urls'][$url] = $url;

    if ($parse && $entity->isCheck($doc, $url)) {
      foreach ($entity->getElements($doc) as $el) {
        $context['results']['results'][] = $entity->processElement($el, $url);

        if ($this->limit($context)) {
          return;
        }
      }
    }

    if ($depth < $entity->getDepth() && $doc) {
      foreach ($entity->findUrls($doc, $url) as $url_find) {
        if (!isset($context['results']['processed_urls'][$url_find])) {
          $context['results']['urls'][$url_find] = $url_find;
        }
      }
    }
    
    if ($last && $context['results']['urls']) {
      $this->createBatch($context['results']['urls'], ++$depth, $context['results'], $options);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function limit($context) {
    $parse_limit = $this->entity->getSetting('parse_limit');

    if ($parse_limit == "") {
      return false;
    }

    if (!isset($context['results']['results'])) {
      return false;
    }

    $results = $context['results']['results'];

    if (count($results) >= (int) $parse_limit) {
      return true;
    }

    return false;
  }

  /**
   * Finished callback for batch.
   */
  public function finished($success, $results, $operations) {
    if (!isset($results['results'])) {
      return;
    }

    $this->entity->setResults($results['results']);

    $msg = 'Парсинг завершен:</br>';
    $texts = $this->entity->generateResults();
    $texts[] = 'Обработано страниц: ' . count($results['processed_urls']);

    foreach ($texts as $text) {
      $msg .= $text . '</br>';
    }

    $this->messenger()
      ->addStatus($this->t($msg));
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsDefinitions() {
    $options = [];

    foreach ($this->entityTypeManager
                  ->getDefinitions() as $name => $definition) {
      if ($definition instanceof ContentEntityType) {
        $options[$name] = $definition->getLabel();
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsBundles($entity_type) {
    $options = [];

    foreach ($this->entityTypeBundleInfo
                  ->getBundleInfo($entity_type) as $name => $info) {
      $options[$name] = $info['label'];
    }

    return $options;
  }
}
