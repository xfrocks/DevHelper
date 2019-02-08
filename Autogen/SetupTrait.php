<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace DevHelper\Autogen;

/**
 * @version 2019011701
 * @see \DevHelper\Autogen\SetupTrait
 */
trait SetupTrait
{
    protected function doCreateTables(array $tables)
    {
        $sm = \XF::db()->getSchemaManager();

        foreach($tables as $tableName => $apply) {
            $sm->createTable($tableName, $apply);
        }
    }

    protected function doAlterTables(array $alters)
    {
        $sm = \XF::db()->getSchemaManager();
        foreach($alters as $tableName => $applies) {
            if (!$sm->tableExists($tableName)) {
                \XF::logException(
                    new \Exception(sprintf('Table (%s) does not exists. So cannot be altered', $tableName)),
                    false,
                    '[doAlterTables] '
                );

                continue;
            }

            foreach($applies as $apply) {
                $sm->alterTable($tableName, $apply);
            }
        }
    }

    protected function doDropTables(array $tables)
    {
        $sm = \XF::db()->getSchemaManager();
        foreach(array_keys($tables) as $tableName) {
            $sm->dropTable($tableName);
        }
    }

    protected function doDropColumns(array $alters)
    {
        $sm = \XF::db()->getSchemaManager();
        foreach($alters as $tableName => $applies) {
            if (!$sm->tableExists($tableName)) {
                continue;
            }

            $sm->alterTable($tableName, function(\XF\Db\Schema\Alter $table) use($applies) {
                $table->dropColumns(array_keys($applies));
            });
        }
    }

    /**
     * @return array
     */
    protected function getTables()
    {
        $tables = [];

        $index = 1;
        while(true) {
            $callable = [$this, 'getTables' . $index];
            if (!is_callable($callable)) {
                break;
            }

            $tables += call_user_func($callable);

            $index++;
        }

        return $tables;
    }

    /**
     * @return array
     */
    protected function getAlters()
    {
        $alters = [];

        $index = 1;
        while(true) {
            $callable = [$this, 'getAlters' . $index];
            if (!is_callable($callable)) {
                break;
            }

            $alters = array_merge_recursive($alters, call_user_func($callable));
            $index++;
        }

        return $alters;
    }
}