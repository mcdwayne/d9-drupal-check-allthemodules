<?php

namespace CleverReach\BusinessLogic\Entity;

/**
 * Class SpecialTag
 * @package CleverReach\BusinessLogic\Entity
 */
class SpecialTag extends AbstractTag
{
    /**
     * SpecialTag constructor.
     *
     * @param string $name Valid special tag name. Use constants in this class for valid names.
     */
    protected function __construct($name)
    {
        parent::__construct($name, 'Special');
    }

    /**
     * Returns new special tag "Customer".
     *
     * @return \CleverReach\BusinessLogic\Entity\SpecialTag
     *   Instance of special tag "Customer".
     */
    public static function customer()
    {
        return new SpecialTag('Customer');
    }

    /**
     * Returns new special tag "Subscriber".
     *
     * @return \CleverReach\BusinessLogic\Entity\SpecialTag
     *   Instance of special tag "Subscriber".
     */
    public static function subscriber()
    {
        return new SpecialTag('Subscriber');
    }

    /**
     * Returns new special tag "Buyer".
     *
     * @return \CleverReach\BusinessLogic\Entity\SpecialTag
     *   Instance of special tag "Buyer".
     */
    public static function buyer()
    {
        return new SpecialTag('Buyer');
    }

    /**
     * Returns new special tag "Contact".
     *
     * @return \CleverReach\BusinessLogic\Entity\SpecialTag
     *   Instance of special tag "Contact".
     */
    public static function contact()
    {
        return new SpecialTag('Contact');
    }

    /**
     * Sets special tag from tag name.
     *
     * @param string $tagName
     *
     * @return \CleverReach\BusinessLogic\Entity\SpecialTag
     *
     * @throws \InvalidArgumentException When tag name is not recognized as a special tag name.
     */
    public static function fromString($tagName)
    {
        switch ($tagName) {
            case 'Customer':
                return self::customer();
            case 'Buyer':
                return self::buyer();
            case 'Subscriber':
                return self::subscriber();
            case 'Contact':
                return self::contact();
        }

        throw new \InvalidArgumentException('Unknown special tag!');
    }

    /**
     * Gets collection of all valid special tags.
     *
     * @return \CleverReach\BusinessLogic\Entity\SpecialTagCollection
     *   Collection of all supported special tags.
     */
    public static function all()
    {
        $result = new SpecialTagCollection();
        $result->addTag(self::customer())
            ->addTag(self::subscriber())
            ->addTag(self::buyer())
            ->addTag(self::contact());

        return $result;
    }
}
