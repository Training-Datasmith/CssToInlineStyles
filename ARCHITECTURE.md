# CssToInlineStyles Architecture

## Purpose

Converts external or `<style>`-block CSS into inline `style` attributes on HTML
elements.  Primarily used for HTML emails, where many clients do not support
`<style>` tags or external stylesheets.

## Directory Structure

```
src/
  Css_To_Inline_Styles.php        — main public class
  Css/
    Processor.php                 — extracts CSS text from <style> tags and builds Rule objects
    Rule/
      Rule.php                    — represents a single CSS rule (selector + properties)
      Processor.php               — parses CSS text into Rule objects, sorts by specificity
    Property/
      Property.php                — represents a single CSS declaration (name: value)
      Processor.php               — splits a style attribute string into Property objects
example/
  index.php                       — standalone usage example
tests/                            — PHPUnit test suite
```

## Key Design Decisions

- **DOM-based**: uses PHP's `DOMDocument` + `DOMXPath` so that selector matching
  delegates to Symfony's `CssSelectorConverter` (CSS → XPath).
- **Specificity-aware**: rules are sorted by specificity before application;
  `!important` declarations are honoured.
- **Inline-style preservation**: existing inline styles always win over
  stylesheet-sourced properties.
- **Encoding safety**: multibyte characters are numeric-entity-encoded before
  `DOMDocument::loadHTML()` to avoid mangling UTF-8 content.

## Extension Points

- Call `convert(string $html, string $css)` for one-shot conversion.
- Call `inline_css_on_element()` directly for fine-grained per-element control.
- Override `create_dom_document_from_html()` / `get_html_from_document()` in a
  subclass to customise parsing or serialisation behaviour.

## Dependency Flow

```
Consumer → Css_To_Inline_Styles
               ├── Symfony/CssSelector  (CSS → XPath conversion)
               ├── Css\Processor        (rule extraction)
               ├── Css\Rule\Processor   (rule parsing & specificity sort)
               └── Css\Property\Processor (property parsing)
```
