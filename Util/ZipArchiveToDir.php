<?php

namespace DevHelper\Util;

use Symfony\Component\Console\Output\ConsoleOutput;

class ZipArchiveToDir
{
    /**
     * @var string
     */
    protected $dir;

    /**
     * @param string $dir
     */
    public function __construct($dir)
    {
        $this->dir = rtrim($dir, DIRECTORY_SEPARATOR);

        if (is_dir($this->dir)) {
            throw new \InvalidArgumentException('Directory already exists: ' . $this->dir);
        }

        \XF\Util\File::createDirectory($this->dir, false);
    }

    /**
     * @param string $dirName
     * @return bool
     */
    public function addEmptyDir($dirName)
    {
        return \XF\Util\File::createDirectory($this->getPath($dirName), false);
    }

    /**
     * @param string $fileName
     * @param string $localName
     * @return bool
     */
    public function addFile($fileName, $localName)
    {
        return \XF\Util\File::renameFile($fileName, $this->getPath($localName), false);
    }

    /**
     * @return bool
     */
    public function close()
    {
        if (isset($GLOBALS['runner']) &&
            $GLOBALS['runner'] instanceof \XF\Cli\Runner
        ) {
            $output = new ConsoleOutput();
            $output->writeln(sprintf('%s->dir = %s', __CLASS__, var_export($this->dir, true)));
        }

        return true;
    }

    /**
     * @return string
     */
    public function getStatusString()
    {
        return $this->dir;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        $ds = DIRECTORY_SEPARATOR;
        return $this->dir . $ds . ltrim($name, $ds);
    }
}
