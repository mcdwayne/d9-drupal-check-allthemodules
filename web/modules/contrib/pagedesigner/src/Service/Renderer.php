<?php

namespace Drupal\pagedesigner\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Render\Markup;
use Drupal\pagedesigner\PagedesignerService;
use Drupal\pagedesigner\Service\Render\Styles;
use Drupal\user\Entity\User;

class Renderer extends PagedesignerService
{
    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected static $styles = null;

    public static function addStyle($key, $style, $id)
    {
        if (self::$styles == null) {
            self::$styles = new Styles();
        }
        self::$styles->addStyle($key, $style, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function render(ContentEntityBase $entity = null)
    {
        if ($entity == null) {
            return $this;
        }
        $entity = $this->_getContainer($entity);
        $this->_output = $this->getHandler($entity)->render($entity);
        if (empty($this->_output['#attached'])) {
            $this->_output['#attached'] = [];
        }
        $this->_output['#attached']['library'][] = 'pagedesigner/icon';
        $this->_output['#cache']['max-age'] = 0;
        $this->addStyles();
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function renderForPublic(ContentEntityBase $entity = null)
    {
        if ($entity == null) {
            return $this;
        }
        $this->_output['#cache'] =
            ['keys' => ['entity_view', 'node', $entity->id()],
            'contexts' => ['languages'],
            'tags' => $entity->getCacheTags(),
            'max-age' => Cache::PERMANENT,
        ];
        $entity = $this->_getContainer($entity);

        $this->_output = $this->getHandler($entity)->renderForPublic($entity);
        $this->addStyles();
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function renderForEdit(ContentEntityBase $entity = null)
    {
        if ($entity == null) {
            return $this;
        }

        $locker = \Drupal::service('pagedesigner.locker');
        $locker->setEntity($entity);
        $lock = $locker->acquire();
        $lockIdentifier = $locker->getIdentifier();
        if (!$lock && !$locker->hasLock()) {
            $this->_editMode = false;
            $username = 'unknown';
            $otherUser = User::load($locker->getMetaData()->owner);
            if ($otherUser != null) {
                $username = $otherUser->getUsername();
            }
            drupal_set_message(t('This page is currently being edited by user %user. You may access it again after the user is done editing.', array('%user' => $username)), 'warning');
            return $this->render($entity);
        } elseif (isset($_GET['otherTab']) && $_GET['otherTab'] == 1) {
            $username = 'unknown';
            $otherUser = User::load($locker->getMetaData()->owner);
            if ($otherUser != null) {
                $username = $otherUser->getUsername();
            }
            drupal_set_message(t('This page is currently being edited by you in another tab. Continue editing in that tab or close the other tab and reload here.'), 'warning');
            return $this->render($entity);
        }

        $entity = $this->_getContainer($entity);
        $this->_output = $this->getHandler($entity)->renderForEdit($entity);
        $this->_output['#cache']['max-age'] = 0;
        $this->addStyles();
        $this->addEditorAttachments();
        $this->addPagedesigner();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMarkup()
    {
        return Markup::create(\Drupal::service('renderer')->render($this->_output));
    }

    /**
     * {@inheritdoc}
     */
    public function getStyles()
    {
        $rendered = '';
        if (self::$styles != null) {
            foreach (['large' => '', 'medium' => '@media (max-width: 992px)', 'small' => '@media (max-width: 768px)'] as $size => $query) {
                if (empty($query)) {
                    $rendered .= self::$styles->getStyles($size) . "\n";
                } else {
                    $rendered .= $query . '{' . self::$styles->getStyles($size) . '}' . "\n";
                }
            }
        }
        return $rendered;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function addStyles()
    {
        if (self::$styles != null) {
            $rendered = '';
            foreach (['large' => '', 'medium' => '@media (max-width: 992px)', 'small' => '@media (max-width: 768px)'] as $size => $query) {
                if (empty($query)) {
                    $rendered .= self::$styles->getStyles($size) . "\n";
                } else {
                    $rendered .= $query . '{' . self::$styles->getStyles($size) . '}' . "\n";
                }
            }
            $this->_output['#attached']['html_head']['pd_styles'] = [
                [
                    '#tag' => 'style',
                    '#value' => $rendered,
                    '#attributes' => [
                        'id' => 'pd_styles',
                    ],
                ],
                'pagedesigner_dynamic_css',
            ];
        }
    }

    protected function addEditorAttachments()
    {
        if (empty($this->_output['#attached'])) {
            $this->_output['#attached'] = [];
        }
        $formats = ['pagedesigner'];
        $this->_output['#attached'] = array_merge(
            $this->_output['#attached'],
            \Drupal::service('plugin.manager.editor')->getAttachments($formats)
        );
    }

    protected function addPagedesigner()
    {
        if (empty($this->_output['#attached'])) {
            $this->_output['#attached'] = [];
        }
        $this->_output['#attached']['library'][] = 'pagedesigner/pagedesigner';
        $handlers = \Drupal::service('plugin.manager.pagedesigner_handler')->getHandlers();
        foreach ($handlers as $handler) {
            $handler->collectAttachments($this->_output['#attached']);
        }
    }

}
