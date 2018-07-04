<?php

namespace DevHelper\PHPStan\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use XF\Mvc\Entity\Entity;

class EntityColumnReflection implements PropertyReflection
{
    /**
     * @var ClassReflection
     */
    protected $declaringClass;

    /**
     * @var string
     */
    protected $type;

    public function __construct(ClassReflection $declaringClass, string $type)
    {
        $this->declaringClass = $declaringClass;
        $this->type = $type;
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
        switch ($this->type) {
            case Entity::INT:
            case Entity::UINT:
                return new IntegerType();
            case Entity::FLOAT:
                return new FloatType();
            case Entity::BOOL:
                return new BooleanType();
            case Entity::STR:
            case Entity::BINARY:
                return new StringType();
            case Entity::SERIALIZED:
            case Entity::JSON:
                return new UnionType([new ArrayType(new MixedType(), new MixedType()), new BooleanType()]);
            case Entity::SERIALIZED_ARRAY:
            case Entity::JSON_ARRAY:
            case Entity::LIST_LINES:
            case Entity::LIST_COMMA:
                return new ArrayType(new MixedType(), new MixedType());
        }

        throw new \PHPStan\ShouldNotHappenException();
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isWritable(): bool
    {
        return true;
    }
}
