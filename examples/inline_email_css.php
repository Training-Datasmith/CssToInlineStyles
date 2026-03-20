<?php

declare(strict_types=1);

/**
 * Example: Convert an HTML email template so all CSS is inlined.
 *
 * Before sending HTML email, inline CSS ensures compatibility with
 * mail clients (Gmail, Outlook) that strip <style> blocks.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tijs_Verkoyen\Css_To_Inline_Styles\Css_To_Inline_Styles;

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <style>
    body  { font-family: Arial, sans-serif; background: #f4f4f4; }
    h1    { color: #333333; }
    .btn  { background: #0073e6; color: #ffffff; padding: 10px 20px; }
  </style>
</head>
<body>
  <h1>Welcome!</h1>
  <a href="https://example.com" class="btn">Click here</a>
</body>
</html>
HTML;

// Additional CSS can be injected on top of the embedded <style> block.
$extraCss = 'h1 { font-size: 24px; }';

$converter = new Css_To_Inline_Styles();
$result    = $converter->convert($html, $extraCss);

echo $result;
