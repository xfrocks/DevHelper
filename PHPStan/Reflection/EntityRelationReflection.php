<?php declare(strict_types=1);

namespace DevHelper\PHPStan\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use XF\Mvc\Entity\Entity;

class EntityRelationReflection implements PropertyReflection
{
    /**
     * @var ClassReflection
     */
    protected $declaringClass;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var string
     */
    protected $entity;

    public function __construct(ClassReflection $declaringClass, int $type, string $entity)
    {
        $this->declaringClass = $declaringClass;
        $this->type = $type;
        $this->entity = $entity;
    }

    public function canChangeTypeAfterAssignment(): bool
    {
        return true;
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function getDocComment(): ?string
    {
        return null;
    }

    public function getReadableType(): Type
    {
        $className = str_replace(':', '\Entity\\', $this->entity);

        if ($this->type === Entity::TO_ONE) {
            return TypeCombinator::addNull(new ObjectType($className));
        } else {
            return new ArrayType(new MixedType(), new ObjectType($className));
        }
    }

    public function getWritableType(): Type
    {
        return $this->getReadableType();
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isWritable(): bool
    {
        return false;
    }
}
