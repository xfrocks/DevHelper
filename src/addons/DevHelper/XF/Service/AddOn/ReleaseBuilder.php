<?php

namespace DevHelper\XF\Service\AddOn;

use DevHelper\StreamWrapper;
use XF\Util\File;

class ReleaseBuilder extends XFCP_ReleaseBuilder
{
    protected function prepareDirectories()
    {
        parent::prepareDirectories();

        $this->buildRoot = '/var/www/html/xenforo/internal_data/builds/' . $this->addOn->getAddOnId();
    }

    protected function prepareFilesToCopy()
    {
        parent::prepareFilesToCopy();

        $srcUptoNamespace = $this->addOnRoot;
        $srcUptoNamespaceLocated = StreamWrapper::locate($srcUptoNamespace);
        $repoPath = preg_replace('#/src/addons.+$#', '', $srcUptoNamespaceLocated);
        $ds = DIRECTORY_SEPARATOR;

        $dirPaths = [];
        foreach (new \DirectoryIterator($repoPath) as $dirCandidate) {
            if (!$dirCandidate->isDir()
                || $dirCandidate->isDot()) {
                continue;
            }

            $skip = false;
            $basename = $dirCandidate->getBasename();
            switch ($basename) {
                case '.git':
                case 'addons':
                case 'dev-scripts':
                case 'library':
                case 'src':
                case 'xenforo':
                    $skip = true;
                    break;
            }
            if ($skip) {
                continue;
            }

            $dirPaths[] = $dirCandidate->getPathname();
        }

        foreach ($dirPaths as $dirPath) {
            $dirStandardized = $this->standardizePath($repoPath, $dirPath);

            $filesIterator = $this->getFileIterator($dirPath);
            foreach ($filesIterator AS $file) {
                if ($file->isDir()) {
                    continue;
                }

                $fileStandardized = $this->standardizePath($dirPath, $file->getPathname());
                $destination = $this->uploadRoot . $ds . $dirStandardized . $ds . $fileStandardized;
                File::copyFile($file->getPathname(), $destination, false);
            }
        }
    }

    public function finalizeRelease()
    {
        $addOnId = $this->addOn->prepareAddOnIdForFilename();
        $versionString = $this->addOn->prepareVersionForFilename();
        $releasePath = sprintf(
            '/var/www/html/xenforo/internal_data/releases/%1$s/%1$s-%2$s.zip',
            $addOnId,
            $versionString
        );

        File::createDirectory(dirname($releasePath), false);
        File::renameFile($this->tempFile, $releasePath, false);

        // intentionally do not call parent
    }
}

if (false) {
    class XFCP_ReleaseBuilder extends \XF\Service\AddOn\ReleaseBuilder
    {
    }
}