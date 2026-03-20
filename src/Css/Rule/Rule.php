<?php

declare (strict_types=1);
namespace Tijs_Verkoyen\Css_To_Inline_Styles\Css\Rule;

use Symfony\Component\Css_Selector\Node\Specificity;
use Tijs_Verkoyen\Css_To_Inline_Styles\Css\Property\Property;
final class Rule
{
    /**
     * @var string
     */
    private $selector;
    /**
     * @var Property[]
     */
    private array $properties;
    /**
     * @var Specificity
     */
    private $specificity;
    /**
     * @var integer
     */
    private $order;
    /**
     * Rule constructor.
     *
     * @param string      $selector
     * @param Property[]  $properties
     * @param int         $order
     */
    public function __construct($selector, array $properties, Specificity $specificity, $order)
    {
        $this->selector = $selector;
        $this->properties = $properties;
        $this->specificity = $specificity;
        $this->order = $order;
    }
    /**
     * Get selector
     *
     * @return string
     */
    public function get_selector()
    {
        return $this->selector;
    }
    /**
     * Get properties
     *
     * @return Property[]
     */
    public function get_properties()
    {
        return $this->properties;
    }
    /**
     * Get specificity
     *
     * @return Specificity
     */
    public function get_specificity()
    {
        return $this->specificity;
    }
    /**
     * Get order
     *
     * @return int
     */
    public function get_order()
    {
        return $this->order;
    }
}