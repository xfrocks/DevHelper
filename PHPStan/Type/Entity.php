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

        if (substr($propertyName, -1) === '_') {
            $propertyNameWithoutDash = substr($propertyName, 0, -1);
            if (isset($structure->columns[$propertyNameWithoutDash])) {
                return true;
            }
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
            if (isset($structure->getters[$propertyName]['getter'])
                && is_string($structure->getters[$propertyName]['getter'])
            ) {
                $methodName = $structure->getters[$propertyName]['getter'];
            } else {
                $methodName = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $propertyName)));
            }

            if ($classReflection->hasNativeMethod($methodName)) {
                $method = $classReflection->getNativeMethod($methodName);
                return new EntityGetterReflection(
                    $classReflection,
                    $method->getVariants()[0]->getReturnType(),
                    isset($structure->columns[$propertyName])
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

        if (substr($propertyName, -1) === '_') {
            $propertyNameWithoutDash = substr($propertyName, 0, -1);
            if (isset($structure->columns[$propertyNameWithoutDash])) {
                $column = $structure->columns[$propertyNameWithoutDash];
                return new EntityColumnReflection($classReflection, $column['type']);
            }
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

        $structure = null;
        $classNameGetStructure = [$className, 'getStructure'];
        if (is_callable($classNameGetStructure)) {
            try {
                $_structure = new Structure();
                call_user_func($classNameGetStructure, $_structure);
                $structures[$className] = $structure = $_structure;
            } catch (\Exception $e) {
                // ignore
            }
        }

        return $structure;
    }
}
