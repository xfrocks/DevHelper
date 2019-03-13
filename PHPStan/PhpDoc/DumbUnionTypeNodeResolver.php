<?php declare(strict_types=1);

namespace DevHelper\PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverAwareExtension;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class DumbUnionTypeNodeResolver implements TypeNodeResolverExtension, TypeNodeResolverAwareExtension
{
    /** @var \PHPStan\PhpDoc\TypeNodeResolver */
    private $typeNodeResolver;

    public function setTypeNodeResolver(\PHPStan\PhpDoc\TypeNodeResolver $typeNodeResolver): void
    {
        $this->typeNodeResolver = $typeNodeResolver;
    }

    public function getCacheKey(): string
    {
        return __CLASS__ . '-2019031301';
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if ($typeNode instanceof UnionTypeNode) {
            // override PHPStan's default smart union type resolver with our simplified version
            // the default one merge iterable types together with lots of logic
            // which do not match XenForo type hint usages -> we have to disable it completely
            $types = $this->typeNodeResolver->resolveMultiple($typeNode->types, $nameScope);
            return TypeCombinator::union(...$types);
        }

        return null;
    }
}
