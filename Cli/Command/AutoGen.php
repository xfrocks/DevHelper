<?php

namespace DevHelper\Cli\Command;

use DevHelper\Util\Autogen\GitIgnore;
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
    const VERSION_ID = 2018081902;

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

        if (!is_dir($controllerDirPath)) {
            return;
        }

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
            if (!is_string($entryContents)) {
                continue;
            }

            if (strpos($entryContents, $baseClass) !== false) {
                $baseClassRefFound = true;
            }
        }

        if ($baseClassRefFound) {
            return;
        }

        $baseContents = file_get_contents($basePathSource);
        if (!is_string($baseContents)) {
            return;
        }

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
        $lineAdds = $context->gitignoreAdds;
        $lineAdds = array_unique($lineAdds);
        GitIgnore::sort($lineAdds);

        $lineDeletes = $context->gitignoreDeletes;
        $lineDeletes = array_unique($lineDeletes);
        GitIgnore::sort($lineDeletes);

        $addOnDir = $context->getAddOnDirectory();
        $gitignorePath = "{$addOnDir}/.gitignore";

        $gitignore = [];
        $changed = false;
        if (file_exists($gitignorePath)) {
            $currentLines = file($gitignorePath);
            if (!is_array($currentLines)) {
                $currentLines = [];
            }
            $currentLines = array_map('trim', $currentLines);
            foreach ($currentLines as $currentLine) {
                if (in_array($currentLine, $lineDeletes, true)) {
                    $changed = true;
                    continue;
                }

                $gitignore[] = $currentLine;
            }
        }

        foreach ($lineAdds as $line) {
            if (in_array($line, $gitignore, true)) {
                continue;
            }

            $gitignore[] = $line;
            $changed = true;
        }

        if ($changed) {
            GitIgnore::sort($gitignore);

            if (!File::writeFile($gitignorePath, implode("\n", $gitignore), false)) {
                $context->writeln("<error>Cannot update {$gitignorePath}</error>");
            } else {
                $context->writeln(
                    "<info>{$gitignorePath} OK</info>",
                    \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE
                );
            }
        }

        if (isset($autoGen['.gitignore'])) {
            unset($autoGen['.gitignore']);
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

        $devHelperAddOn = $this->checkInstalledAddOn('DevHelper');
        if (empty($devHelperAddOn)) {
            throw new \LogicException('DevHelper add-on must be installed');
        }
        $devHelperInstalledVersionId = $devHelperAddOn->getInstalledAddOn()->version_id;
        $devHelperJsonVersionId = $devHelperAddOn->getJsonVersion()['version_id'];
        if ($devHelperInstalledVersionId !== $devHelperJsonVersionId) {
            throw new \LogicException("DevHelper add-on must be upgraded ({$devHelperInstalledVersionId} vs. {$devHelperJsonVersionId})");
        }

        $autoGenPath = $addOn->getFilesDirectory() . '/dev/autogen.json';
        $autoGenLegacyPath = $addOn->getAddOnDirectory() . '/DevHelper/autogen.json';
        if (file_exists($autoGenLegacyPath)) {
            if (file_exists($autoGenPath)) {
                throw new \LogicException("{$autoGenLegacyPath} must be moved to {$autoGenPath} manually");
            } else {
                \XF\Util\File::writeFile($autoGenPath, file_get_contents($autoGenLegacyPath), false);
                unlink($autoGenLegacyPath);
                $output->writeln("<warning>{$autoGenLegacyPath} has been moved to {$autoGenPath} automatically</warning>");
            }
        }

        $autoGen = [];
        if (file_exists($autoGenPath)) {
            $autoGenContents = file_get_contents($autoGenPath);
            if (is_string($autoGenContents)) {
                $autoGen = @json_decode($autoGenContents, true);
                if (!is_array($autoGen)) {
                    $autoGen = [];
                }
            }
        }
        $lastAutoGenRun = [];
        if (isset($autoGen[__CLASS__])) {
            $lastAutoGenRun = $autoGen[__CLASS__];
        }
        if (count($lastAutoGenRun) > 0 && $lastAutoGenRun['version_id'] > self::VERSION_ID) {
            throw new \LogicException("{$autoGenPath} was generated by a newer version");
        }
        $autoGenBefore = md5(serialize($autoGen));

        $context = new AutogenContext($this, $input, $output, \XF::app(), $addOn);
        $this->doAdminControllerEntity($autoGen, $context);
        $this->doGitIgnore($autoGen, $context);

        $autoGen[__CLASS__]['version_id'] = self::VERSION_ID;
        if (isset($autoGen[__CLASS__]['time'])) {
            // `time` was used in previous version, remove it now
            // TODO: remove this around next year? (August 2019)
            unset($autoGen[__CLASS__]['time']);
        }

        ksort($autoGen);
        $autoGenAfter = md5(serialize($autoGen));
        if ($autoGenAfter !== $autoGenBefore) {
            if (!File::writeFile($autoGenPath, Json::jsonEncodePretty($autoGen), false)) {
                throw new \LogicException("Cannot update {$autoGenPath}");
            }

            $output->writeln("autogen@" . $autoGen[__CLASS__]['version_id']);
        } else {
            $output->writeln("autogen OK");
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
