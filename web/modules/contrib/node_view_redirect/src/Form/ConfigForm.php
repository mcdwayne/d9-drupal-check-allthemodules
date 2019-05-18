<?php

namespace Drupal\node_view_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\Router;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * CurrentRouteMatch var.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $router;

  /**
   * Construct method.
   *
   * @inheritDoc
   */
  public function __construct(Router $router) {
    $this->router = $router;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.no_access_checks')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_view_redirect_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_view_redirect.config');

    $form['help'] = [
      '#markup' => $this->t('<h3>Help</h3><p><em>This configuration make a redirection for the checked content type from a node view to a valid internal PATH if 
the user has no administration permission. If "No 
permission exception" is checked, this redirection is 
effective for all the users.</em><br 
/><strong>Permissions allowed to view de node:</strong> create nodeType content, delete any nodeType content, delete own nodeType content, delete nodeType revisions, edit any nodeType content, revert any nodeType revisions, view nodeType revisions, revert any nodeType revisions</p>'),
    ];
    /*
     * @FormElement("table")
     */
    $form['table'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => [
        $this->t('Content type'),
        $this->t('Redirect path'),
        $this->t('No permission exception'),
      ],
    ];
    $i = 0;
    foreach (node_view_redirect_get_content_types() as $type_id => $content_type) {
      $i++;
      $form['table'][$i]['nvr_content_type'][$type_id] = [
        '#type' => 'checkbox',
        '#title' => $content_type,
        '#default_value' => $config->get('nvr_content_type.' . $type_id),
      ];

      $form['table'][$i]['nvr_redirect'][$type_id] = [
        '#type' => 'textfield',
        '#title' => $this->t('Redirect path'),
        '#title_display' => 'invisible',
        '#default_value' => $config->get('nvr_redirect.'
          . $type_id),
        '#states' => [
          'visible' => [
            ':input[name="table[' . $i . '][nvr_content_type][' . $type_id . ']"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];

      $form['table'][$i]['nvr_no_exception'][$type_id] =
        [
          '#type' => 'checkbox',
          '#default_value' => $config->get('nvr_no_exception.' . $type_id),
          '#states' => [
            'visible' => [
              ':input[name="table[' . $i . '][nvr_content_type][' . $type_id . ']"]' => [
                'checked' => TRUE,
              ],
            ],
          ],
        ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $num = 0;
    foreach ($form_state->getValues()['table'] as $conf) {
      $num++;
      $check = $conf['nvr_content_type'][key($conf['nvr_content_type'])];
      $url = $conf['nvr_redirect'][key($conf['nvr_redirect'])];
      if ($check && $url == '') {
        $form_state->setErrorByName('table][' . $num . '][nvr_redirect][' . key($conf['nvr_redirect']), $this->t('If conten type is checked insert a valid URL'));
      }
      else {
        if (!$this->urlIsValid($url)) {
          $form_state->setErrorByName('table][' . $num . '][nvr_redirect][' . key($conf['nvr_redirect']), $this->t('Incorrect URL:') . $url);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  private function urlIsValid($url) {
    try {
      $result = $this->router->match($url);
      if ($result) {
        return TRUE;
      }
    }
    catch (\Exception $e) {
      return FALSE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $configuration = $this->config('node_view_redirect.config');
    foreach ($form_state->getValues()['table'] as $conf) {
      $configuration
        ->set('nvr_content_type.' . key($conf['nvr_content_type']),
          $conf['nvr_content_type'][key($conf['nvr_content_type'])])
        ->set('nvr_redirect.' . key($conf['nvr_redirect']),
          $conf['nvr_redirect'][key($conf['nvr_redirect'])])
        ->set('nvr_no_exception.' . key($conf['nvr_no_exception']),
          $conf['nvr_no_exception'][key($conf['nvr_no_exception'])]);
    }

    $configuration->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'node_view_redirect.config',
    ];
  }

}
