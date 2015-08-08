<?php

/**
 * Created by:
 * User: sam
 * Date: 7/27/14
 * Time: 11:06 AM
 */

if (!function_exists('column_names')) {
    /**
     * @param  string $table
     * @param  string $connectionName Database connection name
     *
     * @return array
     */
    function column_names($table, $connectionName = null)
    {
        $schema = \DB::connection($connectionName)->getDoctrineSchemaManager();

        return array_map(function ($var) {
            return str_replace('"', '', $var); // PostgreSQL need this replacement
        }, array_keys($schema->listTableColumns($table)));
    }
}