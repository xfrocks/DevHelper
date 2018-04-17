<?php

namespace DevHelper\Cli\Command;

use DevHelper\Autogen\Admin\Controller\Entity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\AddOn\AddOn;
use XF\Cli\Command\AddOnActionTrait;
use XF\Util\File;

class AutoGen extends Command
{
    use AddOnActionTrait;

    const MARKER_BEGINS = '// DevHelper/Autogen begins';
    const MARKER_ENDS = '// DevHelper/Autogen ends';

    protected $devHelperDirPath;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->devHelperDirPath = dirname(dirname(__DIR__));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param AddOn $addOn
     * @throws \ReflectionException
     * @throws \XF\PrintableException
     */
    public function doAdminControllerEntity($input, $output, $addOn)
    {
        $app = \XF::app();
        $addOnId = $addOn->getAddOnId();
        $addOnDir = $addOn->getAddOnDirectory();
        $classNamePrefix = str_replace('/', '\\', $addOnId);
        $baseNamespace = "{$classNamePrefix}\\DevHelper\\Admin\\Controller";
        $baseClass = "{$baseNamespace}\\Entity";
        $basePathPartial = 'Admin/Controller/Entity.php';
        $basePathSource = "{$this->devHelperDirPath}/Autogen/{$basePathPartial}";
        $basePathTarget = "{$addOnDir}/DevHelper/{$basePathPartial}";
        $controllerDirPath = "{$addOnDir}/Admin/Controller";
        $controllerNamespace = "{$classNamePrefix}\\Admin\\Controller";

        $baseClassRefFound = false;
        $controllerClasses = [];
        foreach (new \DirectoryIterator($controllerDirPath) as $entry) {
            // TODO: process sub-directories

            $entryExtension = $entry->getExtension();
            if ($entryExtension !== 'php') {
                continue;
            }

            $entryFileName = $entry->getFilename();
            $entryClassName = substr($entryFileName, 0, -strlen($entryExtension) - 1);
            $controllerClasses[] = "{$controllerNamespace}\\{$entryClassName}";

            $entryContents = file_get_contents($entry->getPath());
            if (strpos($entryContents, $baseClass) !== false) {
                $baseClassRefFound = true;
            }
        }

        if ($baseClassRefFound) {
            return;
        }

        $baseContents = file_get_contents($basePathSource);
        $baseContents = preg_replace('/namespace .+;/', "namespace {$baseNamespace};", $baseContents);
        if (!File::createDirectory(dirname($basePathTarget), false) ||
            file_put_contents($basePathTarget, $baseContents) === false) {
            throw new \LogicException("Cannot copy {$basePathSource} -> {$basePathTarget}");
        }

        foreach ($controllerClasses as $controllerClass) {
            $reflectionClass = new \ReflectionClass($controllerClass);
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            /** @var Entity $controller */
            $controller = new $controllerClass($app, $app->request());
            if (!is_callable([$controller, 'devHelperAutogen'])) {
                continue;
            }

            $controller->devHelperAutogen($this, $input, $output, $addOn);
        }

        $pattern = '#\s+' . preg_quote(self::MARKER_BEGINS, '#') . '.+' .
            preg_quote(self::MARKER_ENDS, '#') . '#s';
        $baseContents = preg_replace($pattern, '', $baseContents);
        if (file_put_contents($basePathTarget, $baseContents) === false) {
            throw new \LogicException("Cannot update {$basePathTarget}");
        }
    }

    protected function configure()
    {
        $this
            ->setName('devhelper:autogen')
            ->setDescription('Generate code/template/phrase/etc. automatically for the specified add-on')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Add-On ID'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');
        $addOn = $this->checkEditableAddOn($id, $error);
        if (!$addOn) {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        $this->doAdminControllerEntity($input, $output, $addOn);
        return 0;
    }
}
