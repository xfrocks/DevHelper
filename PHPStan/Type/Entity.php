<?php

namespace DevHelper\PHPStan\Type;

use DevHelper\PHPStan\Reflection\EntityColumnReflection;
use DevHelper\PHPStan\Reflection\EntityGetterReflection;
use DevHelper\PHPStan\Reflection\EntityRelationReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use XF\Mvc\Entity\Structure;

class Entity implements PropertiesClassReflectionExtension
{
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        $structure = $this->getStructure($classReflection);
        if ($structure === null) {
            return false;
        }

        if (isset($structure->columns[$propertyName])) {
            return true;
        }
        if (isset($structure->getters[$propertyName])) {
            return true;
        }
        if (isset($structure->relations[$propertyName])) {
            return true;
        }

        return false;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        $structure = $this->getStructure($classReflection);
        if ($structure === null) {
            throw new \PHPStan\ShouldNotHappenException();
        }

        if (isset($structure->getters[$propertyName])) {
            $methodName = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $propertyName)));
            if ($classReflection->hasNativeMethod($methodName)) {
                $method = $classReflection->getNativeMethod($methodName);
                return new EntityGetterReflection(
                    $classReflection,
                    $method->getVariants()[0]->getReturnType()
                );
            }
        }

        if (isset($structure->columns[$propertyName])) {
            $column = $structure->columns[$propertyName];
            return new EntityColumnReflection($classReflection, $column['type']);
        }

        if (isset($structure->relations[$propertyName])) {
            $relation = $structure->relations[$propertyName];
            return new EntityRelationReflection($classReflection, $relation['type'], $relation['entity']);
        }

        $propertyNameWithDash = $propertyName . '_';
        if (isset($structure->columns[$propertyNameWithDash])) {
            $column = $structure->columns[$propertyName];
            return new EntityColumnReflection($classReflection, $column['type']);
        }

        throw new \PHPStan\ShouldNotHappenException();
    }

    protected function getStructure(ClassReflection $classReflection): ?Structure
    {
        static $structures = [];
        $className = $classReflection->getName();
        if (isset($structures[$className])) {
            return $structures[$className];
        }

        $isEntity = $classReflection->isSubclassOf('XF\Mvc\Entity\Entity');
        if (!$isEntity) {
            return null;
        }

        $structures[$className] = new Structure();
        if (!$classReflection->isAbstract()) {
            $callable = [$className, 'getStructure'];
            if (is_callable($callable)) {
                $structures[$className] = call_user_func($callable, $structures[$className]);
            }
        }

        return $structures[$className];
    }
}
