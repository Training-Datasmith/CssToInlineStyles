<?php

declare (strict_types=1);
namespace Tijs_Verkoyen\Css_To_Inline_Styles\Css\Property;

use Symfony\Component\Css_Selector\Node\Specificity;
final class Property
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $value;
    private ?\Symfony\Component\Css_Selector\Node\Specificity $original_specificity;
    /**
     * Property constructor.
     * @param string           $name
     * @param string           $value
     */
    public function __construct($name, $value, ?Specificity $specificity = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->original_specificity = $specificity;
    }
    /**
     * Get name
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }
    /**
     * Get value
     *
     * @return string
     */
    public function get_value()
    {
        return $this->value;
    }
    /**
     * Get originalSpecificity
     *
     * @return Specificity|null
     */
    public function get_original_specificity()
    {
        return $this->original_specificity;
    }
    /**
     * Is this property important?
     */
    public function is_important(): bool
    {
        return stripos($this->value, '!important') !== false;
    }
    /**
     * Get the textual representation of the property
     */
    public function to_string(): string
    {
        return sprintf('%1$s: %2$s;', $this->name, $this->value);
    }
}