<?php declare(strict_types=1);

namespace DevHelper\PHPStan\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Type;

class EntityGetterReflection implements PropertyReflection
{
    /**
     * @var ClassReflection
     */
    protected $declaringClass;

    /**
     * @var Type
     */
    protected $type;

    /**
     * @var bool
     */
    protected $isWritable;

    public function __construct(ClassReflection $declaringClass, Type $type, bool $isWritable)
    {
        $this->declaringClass = $declaringClass;
        $this->type = $type;
        $this->isWritable = $isWritable;
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
        return $this->type;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isWritable(): bool
    {
        return $this->isWritable;
    }
}
