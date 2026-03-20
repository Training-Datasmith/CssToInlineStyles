<?php

declare (strict_types=1);
namespace Tijs_Verkoyen\Css_To_Inline_Styles\Css;

use Tijs_Verkoyen\Css_To_Inline_Styles\Css\Rule\Processor as RuleProcessor;
use Tijs_Verkoyen\Css_To_Inline_Styles\Css\Rule\Rule;
class Processor
{
    /**
     * Get the rules from a given CSS-string
     *
     * @param string $css
     * @param Rule[] $existingRules
     *
     * @return Rule[]
     */
    public function get_rules($css, array $existing_rules = [])
    {
        $css = $this->do_cleanup($css);
        $rules_processor = new Rule_Processor();
        $rules = $rules_processor->split_into_separate_rules($css);
        return $rules_processor->convert_array_to_objects($rules, $existing_rules);
    }
    /**
     * Get the CSS from the style-tags in the given HTML-string
     *
     * @param string $html
     */
    public function get_css_from_style_tags($html): string
    {
        $css = '';
        $matches = [];
        $html_no_comments = preg_replace('|<!--.*?-->|s', '', $html) ?? $html;
        preg_match_all('|<style(?:\s.*)?>(.*)</style>|isU', $html_no_comments, $matches);
        foreach ($matches[1] as $match) {
            $css .= trim($match) . "\n";
        }
        return $css;
    }
    /**
     * @param string $css
     */
    private function do_cleanup($css): string
    {
        // remove charset
        $css = preg_replace('/@charset "[^"]++";/', '', $css) ?? $css;
        // remove media queries
        $css = preg_replace('/@media [^{]*+{([^{}]++|{[^{}]*+})*+}/', '', $css) ?? $css;
        $css = str_replace(["\r", "\n"], '', $css);
        $css = str_replace(["\t"], ' ', $css);
        $css = str_replace('"', '\'', $css);
        $css = preg_replace('|/\*.*?\*/|', '', $css) ?? $css;
        $css = preg_replace('/\s\s++/', ' ', $css) ?? $css;
        return trim($css);
    }
}