<?php

namespace DevHelper\Util\Autogen;

use DevHelper\Util\AutogenContext;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use XF\Entity\AdminNavigation;
use XF\PrintableException;

class AdminNav
{
    /**
     * @param AutogenContext $context
     * @param string $navId
     * @param string $link
     * @return AdminNavigation
     * @throws PrintableException
     */
    public static function autogen($context, $navId, $link = '')
    {
        /** @var AdminNavigation $existing */
        $existing = $context->finder('XF:AdminNavigation')
            ->where('navigation_id', $navId)
            ->fetchOne();
        if (!empty($existing)) {
            if ($existing->addon_id !== $context->getAddOnId() &&
                $existing->addon_id !== 'XF') {
                $existing = null;
            }
        }
        if (!empty($existing)) {
            $context->writeln(
                "<info>Admin navigation {$existing->navigation_id} OK</info>",
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );
            return $existing;
        }

        /** @var AdminNavigation $newAdminNav */
        $newAdminNav = $context->createEntity('XF:AdminNavigation');
        $newAdminNav->navigation_id = $navId;
        $newAdminNav->link = $link;
        $newAdminNav->addon_id = $context->getAddOnId();

        Phrase::autogen($context, $newAdminNav->getPhraseName());

        $parentNavId = 'addOns';
        $questionText = sprintf('<question>Enter parent for admin nav %s [%s]:</question> ', $navId, $parentNavId);
        $question = new Question($questionText, $parentNavId);
        $parentNavId = $context->ask($question);
        $parentNav = self::autogen($context, $parentNavId);
        $newAdminNav->parent_navigation_id = $parentNav->navigation_id;

        if ($parentNav->addon_id !== $context->getAddOnId()) {
            $newAdminNav->display_order = 9999;
        } else {
            $displayOrder = 0;
            foreach ($parentNav->Children as $siblingNav) {
                $displayOrder = max($displayOrder, $siblingNav->display_order);
            }
            $newAdminNav->display_order = $displayOrder + 10;
        }

        $newAdminNav->save();
        $context->writeln("<info>Admin navigation {$newAdminNav->navigation_id} NEW</info>");

        return $newAdminNav;
    }
}
