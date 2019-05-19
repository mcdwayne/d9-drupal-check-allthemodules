<?php

namespace Drupal\views_webticker\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render a list of years and months
 * in reverse chronological order linked to content.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "webticker",
 *   title = @Translation("Webticker"),
 *   help = @Translation("Provides a Views webticker display style."),
 *   theme = "views_view_webticker",
 *   display_types = { "normal" }
 * )
 */
class Webticker extends StylePluginBase
{

    /**
     * Does the style plugin allows to use style plugins.
     *
     * @var bool
     */
    protected $usesRowPlugin = TRUE;
    /**
     * Does the style plugin support custom css class for the rows.
     *
     * @var bool
     */
    protected $usesRowClass = TRUE;

    /**
     * Set default options
     */
    protected function defineOptions()
    {
        $options = parent::defineOptions();
        $options['speed'] = array('default' => 50);
        $options['moving'] = array('default' => TRUE);
        $options['startEmpty'] = array('default' => TRUE);
        $options['duplicate'] = array('default' => FALSE);
        $options['hoverpause'] = array('default' => TRUE);
        $options['rssurl'] = array('default' => FALSE);
        $options['rssfrequency'] = array('default' => 0);
        $options['updatetype'] = array('default' => 'reset');
        $options['transition'] = array('default' => 'linear');
        $options['height'] = array('default' => '30px');
        $options['maskleft'] = array('default' => '');
        $options['maskright'] = array('default' => '');
        $options['maskwidth'] = array('default' => 0);

        return $options;
    }


    /**
     * Render the given style.
     */
    public function buildOptionsForm(&$form, FormStateInterface $form_state)
    {
        parent::buildOptionsForm($form, $form_state);

        $form['speed'] = array(
            '#type' => 'textfield',
            '#title' => t('speed'),
            '#default_value' => $this->options['speed'],
        );

        $form['moving'] = array(
            '#type' => 'checkbox',
            '#title' => t('moving'),
            '#default_value' => $this->options['moving'],
        );
        $form['startEmpty'] = array(
            '#type' => 'checkbox',
            '#title' => t('startEmpty'),
            '#default_value' => $this->options['startEmpty'],
        );
        $form['duplicate'] = array(
            '#type' => 'checkbox',
            '#title' => t('duplicate'),
            '#default_value' => $this->options['duplicate'],
        );
        $form['hoverpause'] = array(
            '#type' => 'checkbox',
            '#title' => t('hoverpause'),
            '#default_value' => $this->options['hoverpause'],
        );
        $form['rssurl'] = array(
            '#type' => 'checkbox',
            '#title' => t('rssurl'),
            '#default_value' => $this->options['rssurl'],
        );
        $form['rssfrequency'] = array(
            '#type' => 'textfield',
            '#title' => t('rssfrequency'),
            '#default_value' => $this->options['rssfrequency'],
        );
        $form['updatetype'] = array(
            '#type' => 'textfield',
            '#title' => t('updatetype'),
            '#default_value' => $this->options['updatetype'],
        );
        $form['transition'] = array(
            '#type' => 'textfield',
            '#title' => t('transition'),
            '#default_value' => $this->options['transition'],
        );
        $form['height'] = array(
            '#type' => 'textfield',
            '#title' => t('height'),
            '#default_value' => $this->options['height'],
        );
        $form['maskleft'] = array(
            '#type' => 'textfield',
            '#title' => t('maskleft'),
            '#default_value' => $this->options['maskleft'],
        );
        $form['maskright'] = array(
            '#type' => 'textfield',
            '#title' => t('maskright'),
            '#default_value' => $this->options['maskright'],
        );
        $form['maskwidth'] = array(
            '#type' => 'textfield',
            '#title' => t('maskwidth'),
            '#default_value' => $this->options['maskwidth'],
        );

    }


}