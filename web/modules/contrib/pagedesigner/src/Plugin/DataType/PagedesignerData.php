<?php
namespace Drupal\pagedesigner\Plugin\DataType;

use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for pagedesigner content data.
 *
 * @DataType(
 *   id = "pagedesigner_item_data",
 *   label = @Translation("Pagedesigner content data"),
 *   description = @Translation("Generated content of the pagedesigner."),
 * )
 */
class PagedesignerData extends TypedData
{

    /**
     * Cached processed text.
     *
     * @var string|null
     */
    protected $value = null;

    /**
     * Cached processed text.
     *
     * @var string|null
     */
    protected $processed = null;

    /**
     * {@inheritdoc}
     */
    public function getValue($langcode = null)
    {
        if ($this->processed !== null) {
            return $this->processed;
        }

        $this->processed = 'some test text for pagedesigner data';

        // Avoid running check_markup() or
        // \Drupal\Component\Utility\SafeMarkup::checkPlain() on empty strings.
        // if (!isset($text) || $text === '') {
        //     $this->processed = '';
        // } else {
        //     $this->processed = check_markup($text, $item->format, $item->getLangcode());
        // }
        return $this->processed;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value, $notify = true)
    {
        $this->processed = $value;

        // Notify the parent of any changes.
        if ($notify && isset($this->parent)) {
            $this->parent->onChange($this->name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getString()
    {
        return implode(' ', $this->getValue());
    }

}
