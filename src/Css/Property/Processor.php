<?php

declare (strict_types=1);
namespace Tijs_Verkoyen\Css_To_Inline_Styles\Css\Property;

use Symfony\Component\Css_Selector\Node\Specificity;
class Processor
{
    /**
     * Split a string into separate properties
     *
     * @param string $propertiesString
     *
     * @return string[]
     */
    public function split_into_separate_properties($properties_string): array
    {
        $properties_string = $this->cleanup($properties_string);
        $properties = explode(';', $properties_string);
        $keys_to_remove = [];
        $number_of_properties = count($properties);
        for ($i = 0; $i < $number_of_properties; $i++) {
            $properties[$i] = trim($properties[$i]);
            // if the new property begins with base64 it is part of the current property
            if (isset($properties[$i + 1]) && strpos(trim($properties[$i + 1]), 'base64,') === 0) {
                $properties[$i] .= ';' . trim($properties[$i + 1]);
                $keys_to_remove[] = $i + 1;
            }
        }
        foreach ($keys_to_remove as $key) {
            unset($properties[$key]);
        }
        return array_values($properties);
    }
    /**
     * @param string $string
     */
    private function cleanup($string): string
    {
        $string = str_replace(["\r", "\n"], '', $string);
        $string = str_replace(["\t"], ' ', $string);
        $string = str_replace('"', '\'', $string);
        $string = preg_replace('|/\*.*?\*/|', '', $string) ?? $string;
        $string = preg_replace('/\s\s+/', ' ', $string) ?? $string;
        $string = trim($string);
        return rtrim($string, ';');
    }
    /**
     * Converts a property-string into an object
     *
     * @param string $property
     */
    public function convert_to_object($property, ?Specificity $specificity = null): ?\Tijs_Verkoyen\Css_To_Inline_Styles\Css\Property\Property
    {
        if (strpos($property, ':') === false) {
            return null;
        }
        [$name, $value] = explode(':', $property, 2);
        $name = trim($name);
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        return new Property($name, $value, $specificity);
    }
    /**
     * Converts an array of property-strings into objects
     *
     * @param string[] $properties
     *
     * @return Property[]
     */
    public function convert_array_to_objects(array $properties, ?Specificity $specificity = null): array
    {
        $objects = [];
        foreach ($properties as $property) {
            $object = $this->convert_to_object($property, $specificity);
            if ($object === null) {
                continue;
            }
            $objects[] = $object;
        }
        return $objects;
    }
    /**
     * Build the property-string for multiple properties
     *
     * @param Property[] $properties
     */
    public function build_properties_string(array $properties): string
    {
        $chunks = [];
        foreach ($properties as $property) {
            $chunks[] = $property->to_string();
        }
        return implode(' ', $chunks);
    }
}