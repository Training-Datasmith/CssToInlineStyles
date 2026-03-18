<?php

namespace TijsVerkoyen\CssToInlineStyles\Css\Property;

use Symfony\Component\CssSelector\Node\Specificity;

class Processor
{
    /**
     * Split a string into separate properties
     *
     * @param string $propertiesString
     *
     * @return string[]
     */
    public function splitIntoSeparateProperties($propertiesString): array
    {
        $propertiesString = $this->cleanup($propertiesString);

        $properties = explode(';', $propertiesString);
        $keysToRemove = [];
        $numberOfProperties = count($properties);

        for ($i = 0; $i < $numberOfProperties; $i++) {
            $properties[$i] = trim($properties[$i]);

            // if the new property begins with base64 it is part of the current property
            if (isset($properties[$i + 1]) && strpos(trim($properties[$i + 1]), 'base64,') === 0) {
                $properties[$i] .= ';' . trim($properties[$i + 1]);
                $keysToRemove[] = $i + 1;
            }
        }

        foreach ($keysToRemove as $key) {
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
    public function convertToObject($property, ?Specificity $specificity = null): ?\TijsVerkoyen\CssToInlineStyles\Css\Property\Property
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
    public function convertArrayToObjects(array $properties, ?Specificity $specificity = null): array
    {
        $objects = [];

        foreach ($properties as $property) {
            $object = $this->convertToObject($property, $specificity);
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
    public function buildPropertiesString(array $properties): string
    {
        $chunks = [];

        foreach ($properties as $property) {
            $chunks[] = $property->toString();
        }

        return implode(' ', $chunks);
    }
}
