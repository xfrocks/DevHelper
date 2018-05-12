<?php

namespace DevHelper\Cli\Command;

use DevHelper\Util\AutogenContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Cli\Command\AddOnActionTrait;
use XF\Util\File;
use XF\Util\Json;

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
     * @param array $autoGen
     * @param AutogenContext $context
     * @throws \ReflectionException
     */
    public function doAdminControllerEntity(array &$autoGen, AutogenContext $context)
    {
        $addOnId = $context->getAddOnId();
        $addOnDir = $context->getAddOnDirectory();
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
        $this->extractVersion($basePathPartial, $baseContents, $autoGen);
        if (!File::writeFile($basePathTarget, $baseContents, false)) {
            throw new \LogicException("Cannot copy {$basePathSource} -> {$basePathTarget}");
        }

        foreach ($controllerClasses as $controllerClass) {
            $reflectionClass = new \ReflectionClass($controllerClass);
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            $controller = $context->newController($controllerClass);
            $context->executeDevHelperAutogen($controller);
        }

        $pattern = '#\s+' . preg_quote(self::MARKER_BEGINS, '#') . '.+' .
            preg_quote(self::MARKER_ENDS, '#') . '#s';
        $baseContents = preg_replace($pattern, '', $baseContents);
        if (file_put_contents($basePathTarget, $baseContents) === false) {
            throw new \LogicException("Cannot update {$basePathTarget}");
        }
    }

    public function doGitIgnore(array &$autoGen, AutogenContext $context)
    {
        static $lines = [
            '/_output/',
            '/_releases/',
            '/DevHelper/*',
            '!/DevHelper/autogen.json',
        ];

        $addOnDir = $context->getAddOnDirectory();
        $gitignorePath = "{$addOnDir}/.gitignore";

        $gitignore = [];
        if (file_exists($gitignorePath)) {
            $gitignore = array_map('trim', file($gitignorePath));
        }
        $newLines = [];

        foreach ($lines as $line) {
            if (in_array($line, $gitignore, true)) {
                continue;
            }

            $gitignore[] = $line;
            $newLines[] = $line;
        }

        if (count($newLines) > 0) {
            if (!File::writeFile($gitignorePath, implode("\n", $gitignore), false)) {
                $context->writeln("<error>Cannot update {$gitignorePath}</error>");
            } else {
                $context->writeln(
                    "<info>{$gitignorePath} OK</info>",
                    \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE
                );
            }
        }

        $autoGen['.gitignore'] = $lines;
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

        $devHelperAddOn = $this->checkInstalledAddOn('DevHelper');
        if (empty($devHelperAddOn)) {
            throw new \LogicException('DevHelper add-on must be installed');
        }

        $autoGenPath = $addOn->getAddOnDirectory() . '/DevHelper/autogen.json';
        $autoGen = [];
        if (file_exists($autoGenPath)) {
            $autoGen = @json_decode(file_get_contents($autoGenPath), true);
            if (!is_array($autoGen)) {
                $autoGen = [];
            }
        }

        $context = new AutogenContext($this, $input, $output, \XF::app(), $addOn);
        $this->doAdminControllerEntity($autoGen, $context);
        $this->doGitIgnore($autoGen, $context);

        unset($autoGen[__CLASS__]);
        ksort($autoGen);
        $autoGen[__CLASS__] = [
            'time' => \XF::$time,
            'version_id' => $devHelperAddOn->getInstalledAddOn()->version_id
        ];
        if (!File::writeFile($autoGenPath, Json::jsonEncodePretty($autoGen), false)) {
            throw new \LogicException("Cannot update {$autoGenPath}");
        }

        return 0;
    }

    /**
     * @param string $path
     * @param string $contents
     * @param array $autoGen
     */
    private function extractVersion($path, $contents, array &$autoGen)
    {
        if (!preg_match('#\n \* @version (\d+)\n#', $contents, $versionMatches)) {
            throw new \LogicException("Cannot extract autogen version from {$path}");
        }
        $autoGen[$path] = intval($versionMatches[1]);
    }
}
