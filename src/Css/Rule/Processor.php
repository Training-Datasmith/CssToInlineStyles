<?php

declare (strict_types=1);
namespace Tijs_Verkoyen\Css_To_Inline_Styles\Css\Rule;

use Symfony\Component\Css_Selector\Node\Specificity;
use Tijs_Verkoyen\Css_To_Inline_Styles\Css\Property\Processor as PropertyProcessor;
class Processor
{
    /**
     * Splits a string into separate rules
     *
     * @param string $rulesString
     *
     * @return string[]
     */
    public function split_into_separate_rules($rules_string): array
    {
        $rules_string = $this->cleanup($rules_string);
        return explode('}', $rules_string);
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
        return rtrim($string, '}');
    }
    /**
     * Converts a rule-string into an object
     *
     * @param string $rule
     * @param int    $originalOrder
     *
     * @return Rule[]
     */
    public function convert_to_objects($rule, $original_order): array
    {
        $rule = $this->cleanup($rule);
        $chunks = explode('{', $rule);
        if (!isset($chunks[1])) {
            return [];
        }
        $properties_processor = new Property_Processor();
        $rules = [];
        $selectors = explode(',', trim($chunks[0]));
        $properties = $properties_processor->split_into_separate_properties($chunks[1]);
        foreach ($selectors as $selector) {
            $selector = trim($selector);
            $specificity = $this->calculate_specificity_based_on_a_selector($selector);
            $rules[] = new Rule($selector, $properties_processor->convert_array_to_objects($properties, $specificity), $specificity, $original_order);
        }
        return $rules;
    }
    /**
     * Calculates the specificity based on a CSS Selector string,
     * Based on the patterns from premailer/css_parser by Alex Dunae
     *
     * @see https://github.com/premailer/css_parser/blob/master/lib/css_parser/regexps.rb
     *
     * @param string $selector
     *
     * @return Specificity
     */
    public function calculate_specificity_based_on_a_selector($selector)
    {
        $id_selector_count = preg_match_all("/  \\#/ix", $selector, $matches);
        $class_attributes_pseudo_classes_selectors_pattern = "  (\\.[\\w]+)                     # classes\n                        |\n                        \\[(\\w+)                       # attributes\n                        |\n                        (\\:(                          # pseudo classes\n                          link|visited|active\n                          |hover|focus\n                          |lang\n                          |target\n                          |enabled|disabled|checked|indeterminate\n                          |root\n                          |nth-child|nth-last-child|nth-of-type|nth-last-of-type\n                          |first-child|last-child|first-of-type|last-of-type\n                          |only-child|only-of-type\n                          |empty|contains\n                        ))";
        $class_attributes_pseudo_classes_selector_count = preg_match_all("/{$class_attributes_pseudo_classes_selectors_pattern}/ix", $selector, $matches);
        $type_pseudo_elements_selector_pattern = "  ((^|[\\s\\+\\>\\~]+)[\\w]+       # elements\n                        |\n                        \\:{1,2}(                    # pseudo-elements\n                          after|before\n                          |first-letter|first-line\n                          |selection\n                        )\n                      )";
        $type_pseudo_elements_selector_count = preg_match_all("/{$type_pseudo_elements_selector_pattern}/ix", $selector, $matches);
        if ($id_selector_count === false || $class_attributes_pseudo_classes_selector_count === false || $type_pseudo_elements_selector_count === false) {
            throw new \RuntimeException('Failed to calculate specificity based on selector.');
        }
        return new Specificity($id_selector_count, $class_attributes_pseudo_classes_selector_count, $type_pseudo_elements_selector_count);
    }
    /**
     * @param string[] $rules
     * @param Rule[]   $objects
     *
     * @return Rule[]
     */
    public function convert_array_to_objects(array $rules, array $objects = [])
    {
        $order = 1;
        foreach ($rules as $rule) {
            $objects = array_merge($objects, $this->convert_to_objects($rule, $order));
            $order++;
        }
        return $objects;
    }
    /**
     * Sorts an array on the specificity element in an ascending way
     * Lower specificity will be sorted to the beginning of the array
     *
     * @param Rule $e1 The first element.
     * @param Rule $e2 The second element.
     *
     * @return int
     */
    public static function sort_on_specificity(Rule $e1, Rule $e2)
    {
        $e1Specificity = $e1->get_specificity();
        $value = $e1Specificity->compare_to($e2->get_specificity());
        // if the specificity is the same, use the order in which the element appeared
        if ($value === 0) {
            return $e1->get_order() - $e2->get_order();
        }
        return $value;
    }
}