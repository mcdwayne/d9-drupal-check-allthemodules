<?php

namespace Drupal\magic_theme;

class HunterControlHandler
{
    /**
     * @var string
     */
    protected $hook;

    /**
     * @var array
     */
    protected $vars = array();

    /**
     * Set the variables.
     *
     * @return array
     */
    public function setVariables(array $vars = array())
    {
        $this->vars = $vars;
        return $this;
    }

    /**
     * Set the variable.
     *
     * @return array
     */
    public function setVariable($name, $value)
    {
        $this->vars[$name] = $value;
        return $this;
    }

    /**
     * Set the hook.
     *
     * @return string
     */
    public function setTemplate($value)
    {
        $this->hook = $value;
        return $this;
    }

    /**
     *  Render a page.
     *
     * @return string
     */
    public function render()
    {
        if (\Drupal::service('path.matcher')->isFrontPage() && empty($this->hook)) {
            $this->setTemplate('hunter__front');
            $path_args = [''];
        }
        else {
          $path_args = explode('/', ltrim(\Drupal::service('path.current')->getPath(), '/'));
        }

        if (empty($this->hook)) {
            $suggestions = theme_get_suggestions($path_args, 'hunter');
            $suggestions = array_reverse($suggestions);
            $this->hook = array_shift($suggestions);
        }

        return array(
          '#theme' => $this->hook,
        ) + $this->vars;
    }
}
