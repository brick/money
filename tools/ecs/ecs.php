<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\OrderedTypesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->import(__DIR__ . '/vendor/brick/coding-standard/ecs.php');

    $libRootPath = realpath(__DIR__ . '/../..');

    $ecsConfig->paths(
        [
            $libRootPath . '/src',
            $libRootPath . '/tests',
            $libRootPath . '/import-currencies.php',
            __FILE__,
        ],
    );

    $ecsConfig->skip([
        // Money uses loose comparison intentionally when comparing contexts
        StrictComparisonFixer::class => $libRootPath . '/src/Money.php',

        // AbstractTestCase uses assertEquals() intentionally when comparing contexts
        PhpUnitStrictFixer::class => $libRootPath . '/tests/AbstractTestCase.php',

        // We want to keep BigNumber|int|string order
        OrderedTypesFixer::class => null,
        PhpdocTypesOrderFixer::class => null,
    ]);
};
