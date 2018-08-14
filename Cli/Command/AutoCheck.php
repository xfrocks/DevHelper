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
        $result |= $this->checkPurchasables($addOn, $output);

        if ($result === 0) {
            $output->writeln("autocheck OK");
        }

        return $result;
    }
}
