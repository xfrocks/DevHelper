<?php declare(strict_types=1);

namespace DevHelper\PHPStan\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
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

    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getType(): Type
    {
        $className = str_replace(':', '\Entity\\', $this->entity);

        if ($this->type === Entity::TO_ONE) {
            return new UnionType([new NullType(), new ObjectType($className)]);
        } else {
            return new ArrayType(new IntegerType(), new ObjectType($className));
        }
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isWritable(): bool
    {
        return false;
    }
}
