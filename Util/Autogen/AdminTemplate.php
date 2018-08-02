<?php

namespace DevHelper\Util\Autogen;

use DevHelper\Util\AutogenContext;
use XF\Entity\Template;
use XF\PrintableException;

class AdminTemplate
{
    /**
     * @param AutogenContext $context
     * @param string $titleSource
     * @param string $titleTarget
     * @return Template
     * @throws PrintableException
     */
    public static function autogen($context, $titleSource, $titleTarget)
    {
        $context->gitignoreAdds[] = sprintf('/_output/templates/admin/%s.html', $titleTarget);

        /** @var Template|null $templateSource */
        $templateSource = $context->finder('XF:Template')
            ->where('type', 'admin')
            ->where('style_id', 0)
            ->where('addon_id', 'DevHelper')
            ->where('title', $titleSource)
            ->fetchOne();
        if ($templateSource === null) {
            throw new \LogicException("Source template {$titleSource} not found");
        }

        /** @var Template|null $templateTarget */
        $templateTarget = $context->finder('XF:Template')
            ->where('type', 'admin')
            ->where('style_id', 0)
            ->where('addon_id', $context->getAddOnId())
            ->where('title', $titleTarget)
            ->fetchOne();

        if ($templateTarget === null) {
            /** @var Template $newTemplate */
            $newTemplate = $context->createEntity('XF:Template');
            $newTemplate->type = 'admin';
            $newTemplate->title = $titleTarget;
            $newTemplate->style_id = 0;
            $newTemplate->template = $templateSource->template;
            $newTemplate->addon_id = $context->getAddOnId();
            $newTemplate->save();

            $context->writeln("<info>Template #{$newTemplate->template_id} {$newTemplate->title} NEW</info>");
            return $newTemplate;
        }

        if ($templateTarget->template === $templateSource->template) {
            $context->writeln(
                "<info>Template #{$templateTarget->template_id} {$templateTarget->title} OK</info>",
                \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE
            );
            return $templateTarget;
        } else {
            $templateTarget->template = $templateSource->template;
            $templateTarget->save();

            $context->writeln("<info>Template #{$templateTarget->template_id} {$templateTarget->title} UPDATED</info>");
            return $templateTarget;
        }
    }
}
