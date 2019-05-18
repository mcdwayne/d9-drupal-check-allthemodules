<?php

namespace Drupal\icn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IcnDefault.
 */
class IcnDefault extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'icn.default',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'icn_default';
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
                $container->get('config.factory')
        );
    }

    /**
     * Constructs a SiteInformationForm object.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The factory for configuration objects.
     */
    public function __construct(ConfigFactoryInterface $config_factory) {
        parent::__construct($config_factory);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $defaults = $this->config('icn.default')->get('default', []);
        $number_default = $form_state->getValue('number_default', count($defaults));

        $form['number_default'] = [
            '#type' => 'value',
            '#value' => $number_default,
        ];

        $form['default'] = [
            '#type' => 'fieldset',
            '#title' => t("Default Links"),
            '#tree' => TRUE,
        ];
        for ($i = 1; $i <= $number_default; $i++) {
            $item = array_shift($defaults);
            $form['default'][$i] = $this->addFieldGroup($i, $item);
        }

        $form['default'][$i + 1] = $this->addFieldGroup($i + 1, [], FALSE);

        $form['add_item'] = [
            '#type' => 'submit',
            '#value' => t('Add item'),
            '#submit' => ['::addItem'],
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * Make field group
     * 
     * @param int $i
     * @param array $default
     * @param boolean $withRemove
     * @return array
     */
    protected function addFieldGroup($i, $default = [], $withRemove = TRUE) {
        $fieldgroup = [
            '#type' => 'fieldset',
        ];

        $fieldgroup['title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#default_value' => isset($default['title']) ? $default['title'] : '',
        ];

        $fieldgroup['url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Path'),
            '#default_value' => (isset($default['url'])) ? $default['url'] : '',
            '#description' => 'format : /my/local/path   ;  http://my.external/path',
        ];

        if ($withRemove === TRUE) {
            $fieldgroup['remove_' . $i] = [
                '#type' => 'submit',
                '#value' => t("Delete me"),
                '#submit' => ['::removeItem'],
                '#attributes' => [
                    'class' => ['button--danger'],
                    'data-toRemove' => $i,
                ]
            ];
        }

        return $fieldgroup;
    }

    /**
     * Add field groupe callback
     * 
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function addItem(array &$form, FormStateInterface $form_state) {
        $form_state->setValue('number_default', $form_state->getValue('number_default') + 1);
        $form_state->setRebuild();
    }

    /**
     * Remove field group callback
     * 
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function removeItem(array &$form, FormStateInterface $form_state) {
        $to_removed = $form_state->getTriggeringElement()['#attributes']['data-toRemove'];
        $values = $form_state->getValue('default');
        unset($values[$to_removed]);
        $form_state->setValue('default', $values);
        $this->submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        parent::validateForm($form, $form_state);
        $values = $form_state->getValue('default');
        foreach ($values as $key => $value) {
            if ($value['url'] != '') {
                // Test if path are routable
                $url_object = \Drupal::service('path.validator')->getUrlIfValid($value['url']);
                if (!$url_object) {
                    $form_state->setErrorByName('default][' . $key . '][url', t('Please set a valid routed uri'));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);
        $parsed = [];
        $values = $form_state->getValue('default');
        foreach ($values as $value) {
            if ($value['title'] == '' && $value['url'] == '') {
                continue;
            }

            $data_item = ['title' => $value['title'], 'url' => $value['url']];
            $data[] = $data_item;
        }
        $this->config('icn.default')->set('default', $data)->save();
    }

}
