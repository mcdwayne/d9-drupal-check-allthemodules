<?php
namespace Drupal\pagedesigner\Service\Render;

/**
 * Styles container.
 *
 * This class provides methods to add and removes css style definitions.
 */
class Styles
{

    /**
     * Contains the added styles.
     *
     * @var string[]
     */
    protected $_styles = [];

    /**
     * Returns styles for the given key.
     *
     * @return string
     */
    public function getStyles($key)
    {
        if (!empty($this->_styles[$key])) {
            return $this->_styles[$key];
        }
        return '';
    }

    /**
     * Add a style definition.
     *
     * @param string $key The size for which to add the style definition (mn, sm, xs).
     * @param string $style The style definition to add.
     * @param int $id The id of the element.
     * @return void
     */
    public function addStyle($key, $style, $id)
    {
        if (empty($this->_styles[$key])) {
            $this->_styles[$key] = '';
        }
        $this->_styles[$key] .= "#pd-cp-" . $id . " {" . $style . "}";
    }

}
