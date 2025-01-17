<?php

declare(strict_types=1);

namespace Reveal\RevealLatte\Tests\Rules\NoUnusedNetteCreateComponentMethodRule;

use Iterator;
use PHPStan\Rules\Rule;
use Reveal\RevealLatte\Rules\NoUnusedNetteCreateComponentMethodRule;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<NoUnusedNetteCreateComponentMethodRule>
 */
final class NoUnusedNetteCreateComponentMethodRuleTest extends AbstractServiceAwareRuleTestCase
{
    /**
     * @dataProvider provideData()
     * @param mixed[] $expectedErrorsWithLines
     */
    public function testRule(string $filePath, array $expectedErrorsWithLines): void
    {
        $this->analyse([$filePath], $expectedErrorsWithLines);
    }

    public function provideData(): Iterator
    {
        yield [__DIR__ . '/Fixture/SkipUsedAbstractPresenter.php', []];

        yield [__DIR__ . '/Fixture/SkipNonPresneter.php', []];
        yield [__DIR__ . '/Fixture/SkipUsedCreateComponentMethod.php', []];
        yield [__DIR__ . '/Fixture/SkipUsedInThisGetComponent.php', []];
        yield [__DIR__ . '/Fixture/SkipUsedInArrayDimFetch.php', []];

        $errorMessage = sprintf(NoUnusedNetteCreateComponentMethodRule::ERROR_MESSAGE, 'createComponentWhatever');
        yield [__DIR__ . '/Fixture/UnusedCreateComponentMethod.php', [[$errorMessage, 11]]];
    }

    protected function getRule(): Rule
    {
        return $this->getRuleFromConfig(
            NoUnusedNetteCreateComponentMethodRule::class,
            __DIR__ . '/config/configured_rule.neon'
        );
    }
}
