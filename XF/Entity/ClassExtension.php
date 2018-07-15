<?php

namespace DevHelper\XF\Entity;

class ClassExtension extends XFCP_ClassExtension
{
    protected function _preSave()
    {
        parent::_preSave();

        if ($this->to_class === '') {
            $newClass = $this->generateToClassFileAutomatically();
            if (!empty($newClass)) {
                $this->to_class = $newClass;
            }
        }
    }

    protected function verifyToClass(&$class)
    {
        if ($class === '') {
            return true;
        }

        return parent::verifyToClass($class);
    }

    protected function generateToClassFileAutomatically()
    {
        $newClass = str_replace('/', '\\', $this->addon_id) . '\\' . $this->from_class;

        $addOn = $this->app()->addOnManager()->getById($this->addon_id);
        if (empty($addOn)) {
            return '';
        }

        $addOnDirPath = $addOn->getAddOnDirectory();
        $newClassPath = $addOnDirPath . '/' . str_replace('\\', '/', $this->from_class) . '.php';
        if (file_exists($newClassPath)) {
            return $newClass;
        }

        $newClassParts = explode('\\', $newClass);
        $newClassName = array_pop($newClassParts);
        $newNamespace = implode('\\', $newClassParts);
        $newClassContents = <<<EOF
<?php

namespace {$newNamespace};

class {$newClassName} extends XFCP_{$newClassName}
{
}

EOF;

        \XF\Util\File::createDirectory(dirname($newClassPath), false);
        file_put_contents($newClassPath, $newClassContents);

        return $newClass;
    }
}

// phpcs:disable
if (false) {
    class XFCP_ClassExtension extends \XF\Entity\ClassExtension
    {
    }
}
