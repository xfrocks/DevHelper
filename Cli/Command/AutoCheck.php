<?php

namespace DevHelper\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\AddOn\AddOn;
use XF\Cli\Command\AddOnActionTrait;

class AutoCheck extends Command
{
    use AddOnActionTrait;

    /**
     * @param AddOn $addOn
     * @param OutputInterface $output
     * @return int
     */
    public function checkBuildJson(AddOn $addOn, OutputInterface $output)
    {
        $result = 0;
        $additionalFiles = $addOn->offsetGet('additional_files');
        $filesJsPath = "js/{$addOn->getAddOnId()}";
        $filesStylesPath = "styles/default/{$addOn->getAddOnId()}";

        $includedJs = false;
        $includedStyles = false;
        foreach ((array)$additionalFiles as $additionalFile) {
            if ($additionalFile === $filesJsPath) {
                $includedJs = true;
                continue;
            }

            if ($additionalFile === $filesStylesPath) {
                $includedStyles = true;
                continue;
            }
        }

        if (!$includedJs) {
            $filesJsFullPath = $addOn->getFilesDirectory() . '/' . $filesJsPath;
            if (is_dir($filesJsFullPath)) {
                $output->writeln("<error>JS directory found, {$filesJsPath} should be in `build.json`</error>");
                $result = 1;
            }
        }

        if (!$includedStyles) {
            $filesStylesFullPath = $addOn->getFilesDirectory() . '/' . $filesStylesPath;
            if (is_dir($filesStylesFullPath)) {
                $output->writeln("<error>Styles directory found, {$filesStylesPath} should be in `build.json`</error>");
                $result = 1;
            }
        }

        return $result;
    }

    /**
     * @param AddOn $addOn
     * @param OutputInterface $output
     * @return int
     */
    public function checkPurchasables(AddOn $addOn, OutputInterface $output)
    {
        $result = 0;
        $app = \XF::app();
        /** @var \XF\Finder\Template $templateFinder */
        $templateFinder = $app->finder('XF:Template');

        $purchasables = $app->finder('XF:Purchasable')
            ->where('addon_id', $addOn->getAddOnId())
            ->fetch();

        /** @var \XF\Entity\Purchasable $purchasable */
        foreach ($purchasables as $purchasable) {
            $emailTemplateTitle = 'payment_received_receipt_' . $purchasable->purchasable_type_id;
            $emailTemplateCount = $templateFinder->fromAddOn($addOn->getAddOnId())
                ->where('type', 'email')
                ->where('title', $emailTemplateTitle)
                ->total();

            if ($emailTemplateCount === 0) {
                $output->writeln("<error>Template email:{$emailTemplateTitle} does not exist</error>");
                $result = 1;
            }
        }

        return $result;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('devhelper:autocheck')
            ->setDescription('Check common mistakes for the specified add-on')
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

        $result = 0;
        $result |= $this->checkBuildJson($addOn, $output);
        $result |= $this->checkPurchasables($addOn, $output);

        if ($result === 0) {
            $output->writeln("autocheck OK");
        }

        return $result;
    }
}
