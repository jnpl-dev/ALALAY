<?php

namespace App\Console\Commands\Traits;

trait InteractsWithMysql
{
    /**
     * Run a callback with MYSQL_PWD exported (and always unset afterwards)
     * so the MySQL client picks the password up from the environment instead
     * of a `-p...` flag that would be visible in `ps` output.
     */
    protected function withMysqlPassword(?string $password, callable $callback): mixed
    {
        $hadPassword = $password !== null && $password !== '';
        if ($hadPassword) {
            putenv('MYSQL_PWD=' . $password);
        }

        try {
            return $callback();
        } finally {
            if ($hadPassword) {
                putenv('MYSQL_PWD');
            }
        }
    }
}
