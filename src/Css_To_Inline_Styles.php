<?php

declare (strict_types=1);
namespace Tijs_Verkoyen\Css_To_Inline_Styles;

use Symfony\Component\Css_Selector\Css_Selector_Converter;
use Symfony\Component\Css_Selector\Exception\Exception_Interface;
use Tijs_Verkoyen\Css_To_Inline_Styles\Css\Processor;
use Tijs_Verkoyen\Css_To_Inline_Styles\Css\Property\Processor as PropertyProcessor;
use Tijs_Verkoyen\Css_To_Inline_Styles\Css\Property\Property;
use Tijs_Verkoyen\Css_To_Inline_Styles\Css\Rule\Processor as RuleProcessor;
class Css_To_Inline_Styles
{
    /**
     * @var Css_Selector_Converter Symfony CSS-to-XPath converter used for selector matching
     */
    private $css_converter;

    /**
     * Initialises the converter with a Symfony CssSelectorConverter instance.
     */
    public function __construct()
    {
        $this->css_converter = new Css_Selector_Converter();
    }

    /**
     * Inlines all CSS rules into the HTML document's element style attributes.
     *
     * Extracts CSS from any `<style>` tags already present in the HTML, merges
     * them with the optionally provided $css string, then applies each rule to
     * matching elements as inline `style` attributes.  Specificity and
     * `!important` are respected.
     *
     * @param string      $html The HTML document or fragment to process.
     * @param string|null $css  Additional CSS rules to apply on top of any embedded `<style>` blocks.
     *
     * @return string The processed HTML with all CSS inlined.
     *
     * @complexity O(r * e) where r = number of CSS rules and e = number of matched DOM elements
     */
    public function convert($html, $css = null)
    {
        $document = $this->create_dom_document_from_html($html);
        $processor = new Processor();
        // get all styles from the style-tags
        $rules = $processor->get_rules($processor->get_css_from_style_tags($html));
        if ($css !== null) {
            $rules = $processor->get_rules($css, $rules);
        }
        $document = $this->inline($document, $rules);
        return $this->get_html_from_document($document);
    }
    /**
     * Applies the given CSS properties to a single DOM element as an inline style attribute.
     *
     * Pre-existing inline styles take precedence — they are merged after the provided
     * $properties so that author inline styles are never overwritten.
     *
     * @param \Dom_Element $element    The DOM element to modify.
     * @param Property[]   $properties The list of CSS properties to apply.
     *
     * @return \Dom_Element The same element with its `style` attribute updated.
     */
    public function inline_css_on_element(\Dom_Element $element, array $properties): \Dom_Element
    {
        if (empty($properties)) {
            return $element;
        }
        $css_properties = [];
        $inline_properties = [];
        foreach ($this->get_inline_styles($element) as $property) {
            $inline_properties[$property->get_name()] = $property;
        }
        foreach ($properties as $property) {
            if (!isset($inline_properties[$property->get_name()])) {
                $css_properties[$property->get_name()] = $property;
            }
        }
        $rules = [];
        foreach (array_merge($css_properties, $inline_properties) as $property) {
            $rules[] = $property->to_string();
        }
        $element->set_attribute('style', implode(' ', $rules));
        return $element;
    }
    /**
     * Returns the existing inline CSS properties already set on a DOM element.
     *
     * Parses the element's current `style` attribute and returns each declaration
     * as a typed {@see Property} object, preserving property names and values.
     *
     * @param \Dom_Element $element The DOM element whose inline styles are to be read.
     *
     * @return Property[] Ordered list of Property objects parsed from the `style` attribute.
     */
    public function get_inline_styles(\Dom_Element $element)
    {
        $processor = new Property_Processor();
        return $processor->convert_array_to_objects($processor->split_into_separate_properties($element->get_attribute('style')));
    }
    /**
     * Parses an HTML string into a DOMDocument, handling non-ASCII characters safely.
     *
     * Uses `mb_encode_numericentity` to convert multibyte codepoints above 0x7F to
     * numeric HTML entities before parsing, then re-enables the original libxml
     * internal-error mode.
     *
     * @param string $html Raw HTML string to parse.
     *
     * @return \Dom_Document The parsed document with `formatOutput` enabled.
     */
    protected function create_dom_document_from_html($html): \Dom_Document
    {
        $document = new \Dom_Document('1.0', 'UTF-8');
        $internal_errors = libxml_use_internal_errors(true);
        $document->load_html(mb_encode_numericentity($html, [0x80, 0x10ffff, 0, 0x1fffff], 'UTF-8'));
        libxml_use_internal_errors($internal_errors);
        $document->format_output = true;
        return $document;
    }
    /**
     * Serialises a DOMDocument back to an HTML string, preserving the DOCTYPE.
     *
     * Handles the HTML5 `<!DOCTYPE html>` declaration by lower-casing it to match
     * the HTML5 specification.
     *
     * @param \Dom_Document $document The document to serialise.
     *
     * @return string Full HTML string including doctype and root element.
     *
     * @throws \RuntimeException If the document element is missing or serialisation fails.
     */
    protected function get_html_from_document(\Dom_Document $document): string
    {
        // retrieve the document element
        // we do it this way to preserve the utf-8 encoding
        $html_element = $document->document_element;
        if ($html_element === null) {
            throw new \RuntimeException('Failed to get HTML from empty document.');
        }
        $html = $document->save_html($html_element);
        if ($html === false) {
            throw new \RuntimeException('Failed to get HTML from document.');
        }
        $html = trim($html);
        // retrieve the doctype
        $document->remove_child($html_element);
        $doctype = $document->save_html();
        if ($doctype === false) {
            $doctype = '';
        }
        $doctype = trim($doctype);
        // if it is the html5 doctype convert it to lowercase
        if ($doctype === '<!DOCTYPE html>') {
            $doctype = strtolower($doctype);
        }
        return $doctype . "\n" . $html;
    }
    /**
     * @param Css\Rule\Rule[] $rules
     *
     */
    protected function inline(\Dom_Document $document, array $rules): \Dom_Document
    {
        if (empty($rules)) {
            return $document;
        }
        /** @var \SplObjectStorage<\DOMElement, array<string, Property>> $propertyStorage */
        $property_storage = new \Spl_Object_Storage();
        $x_path = new \Domx_Path($document);
        usort($rules, [Rule_Processor::class, 'sortOnSpecificity']);
        foreach ($rules as $rule) {
            try {
                $expression = $this->css_converter->to_x_path($rule->get_selector());
            } catch (Exception_Interface $e) {
                continue;
            }
            $elements = $x_path->query($expression);
            if ($elements === false) {
                continue;
            }
            foreach ($elements as $element) {
                \assert($element instanceof \Dom_Element);
                $property_storage[$element] = $this->calculate_properties_to_be_applied($rule->get_properties(), $property_storage->offsetExists($element) ? $property_storage[$element] : []);
            }
        }
        foreach ($property_storage as $element) {
            $this->inline_css_on_element($element, $property_storage[$element]);
        }
        return $document;
    }
    /**
     * Merge the CSS rules to determine the applied properties.
     *
     * @param Property[] $properties
     * @param array<string, Property> $cssProperties existing applied properties indexed by name
     *
     * @return array<string, Property> updated properties, indexed by name
     */
    private function calculate_properties_to_be_applied(array $properties, array $css_properties): array
    {
        if (empty($properties)) {
            return $css_properties;
        }
        foreach ($properties as $property) {
            if (isset($css_properties[$property->get_name()])) {
                $existing_property = $css_properties[$property->get_name()];
                //skip check to overrule if existing property is important and current is not
                if ($existing_property->is_important() && !$property->is_important()) {
                    continue;
                }
                //overrule if current property is important and existing is not, else check specificity
                $overrule = !$existing_property->is_important() && $property->is_important();
                if (!$overrule) {
                    \assert($existing_property->get_original_specificity() !== null, 'Properties created for parsed CSS always have their associated specificity.');
                    \assert($property->get_original_specificity() !== null, 'Properties created for parsed CSS always have their associated specificity.');
                    $overrule = $existing_property->get_original_specificity()->compare_to($property->get_original_specificity()) <= 0;
                }
                if ($overrule) {
                    unset($css_properties[$property->get_name()]);
                    $css_properties[$property->get_name()] = $property;
                }
            } else {
                $css_properties[$property->get_name()] = $property;
            }
        }
        return $css_properties;
    }
}