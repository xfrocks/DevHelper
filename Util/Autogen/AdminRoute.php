<?php

namespace DevHelper\Util\Autogen;

use DevHelper\Util\AutogenContext;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use XF\Entity\Route;
use XF\PrintableException;

class AdminRoute
{
    /**
     * @param AutogenContext $context
     * @param string $routePrefix
     * @param string $primaryKey
     * @param string $controller
     * @return Route
     * @throws PrintableException
     */
    public static function autogen($context, $routePrefix, $primaryKey, $controller)
    {
        /** @var Route|null $existing */
        $existing = $context->finder('XF:Route')
            ->where('route_type', 'admin')
            ->where('route_prefix', $routePrefix)
            ->where('addon_id', $context->getAddOnId())
            ->fetchOne();
        if ($existing !== null) {
            $context->writeln(
                "<info>Route #{$existing->route_id} {$existing->route_type}/{$existing->route_prefix} OK</info>",
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );
            return $existing;
        }

        /** @var Route $newRoute */
        $newRoute = $context->createEntity('XF:Route');
        $newRoute->route_type = 'admin';
        $newRoute->route_prefix = $routePrefix;
        $newRoute->format = sprintf(':int<%s>/', $primaryKey);
        $newRoute->controller = $controller;
        $newRoute->addon_id = $context->getAddOnId();

        $questionText = sprintf('<question>Enter context for route %s:</question> ', $routePrefix);
        $question = new Question($questionText);
        $routeContext = $context->ask($question);
        if (is_string($routeContext)) {
            $adminNav = \DevHelper\Util\Autogen\AdminNav::autogen($context, $routeContext, $routePrefix);
            $newRoute->context = $adminNav->navigation_id;
        }

        $newRoute->save();
        $context->writeln("<info>Route #{$newRoute->route_id} {$newRoute->route_type}/{$newRoute->route_prefix} NEW</info>");

        return $newRoute;
    }
}
