<?php

declare(strict_types=1);

namespace TijsVerkoyen\CssToInlineStyles\Tests\Css\Rule;

use PHPUnit\Framework\TestCase;
use Symfony\Component\CssSelector\Node\Specificity;
use TijsVerkoyen\CssToInlineStyles\Css\Property\Property;
use TijsVerkoyen\CssToInlineStyles\Css\Rule\Rule;

class RuleTest extends TestCase
{
    public function testGetters(): void
    {
        $property = new Property('padding', '5px');
        $specificity = new Specificity(0, 0, 0);

        $rule = new Rule(
            'a',
            [$property],
            $specificity,
            1
        );

        $this->assertEquals('a', $rule->getSelector());
        $this->assertEquals([$property], $rule->getProperties());
        $this->assertEquals($specificity, $rule->getSpecificity());
        $this->assertEquals(1, $rule->getOrder());
    }
}
