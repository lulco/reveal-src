<?php

declare(strict_types=1);

namespace Reveal\LattePHPStanCompiler\PhpParser\NodeVisitor;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeVisitorAbstract;
use Reveal\LattePHPStanCompiler\Contract\LatteToPhpCompilerNodeVisitorInterface;
use Reveal\LattePHPStanCompiler\Latte\Filters\FilterMatcher;
use Reveal\LattePHPStanCompiler\ValueObject\DynamicCallReference;
use Reveal\LattePHPStanCompiler\ValueObject\FunctionCallReference;
use Reveal\LattePHPStanCompiler\ValueObject\StaticCallReference;
use Symplify\Astral\Naming\SimpleNameResolver;

/**
 * Make \Latte\Runtime\Defaults::getFilters() explicit, from: $this->filters->{magic}(...)
 *
 * to: \Latte\Runtime\Filters::date(...)
 */
final class MagicFilterToExplicitCallNodeVisitor extends NodeVisitorAbstract implements LatteToPhpCompilerNodeVisitorInterface
{
    public function __construct(
        private SimpleNameResolver $simpleNameResolver,
        private FilterMatcher $filterMatcher
    ) {
    }

    /**
     * Looking for: "$this->filters->{magic}"
     */
    public function enterNode(Node $node): Node|null
    {
        if (! $node instanceof FuncCall) {
            return null;
        }

        if (! $node->name instanceof Expr) {
            return null;
        }

        $dynamicName = $node->name;
        if (! $dynamicName instanceof PropertyFetch) {
            return null;
        }

        if (! $this->isPropertyFetchNames($dynamicName->var, 'this', 'filters')) {
            return null;
        }

        $filterName = $this->simpleNameResolver->getName($dynamicName->name);
        if ($filterName === null) {
            return null;
        }

        $callReference = $this->filterMatcher->match($filterName);

        $args = $node->args;

        // Add FilterInfo for special filters
        if (in_array($filterName, ['striphtml', 'striptags', 'strip', 'indent', 'repeat', 'replace', 'trim'], true)) {
            $args = array_merge([
                new Arg(new Variable('ʟ_fi')),
            ], $args);
        }

        if ($callReference instanceof StaticCallReference) {
            return new StaticCall(
                new FullyQualified($callReference->getClass()),
                new Identifier($callReference->getMethod()),
                $args
            );
        }

        if ($callReference instanceof DynamicCallReference) {
            $className = $callReference->getClass();
            $variableName = Strings::firstLower(Strings::replace($className, '#\\\#', '')) . 'Filter';
            return new MethodCall(
                new Variable($variableName),
                new Identifier($callReference->getMethod()),
                $args
            );
        }

        if ($callReference instanceof FunctionCallReference) {
            return new FuncCall(new FullyQualified($callReference->getFunction()), $args);
        }

        return null;
    }

    private function isPropertyFetchNames(Expr $expr, string $variableName, string $propertyName): bool
    {
        if (! $expr instanceof PropertyFetch) {
            return false;
        }

        if (! $this->simpleNameResolver->isName($expr->var, $variableName)) {
            return false;
        }

        return $this->simpleNameResolver->isName($expr->name, $propertyName);
    }
}
