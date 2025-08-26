<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowTableNullability extends Command
{
    protected $signature = 'schema:required {table} {--schema=public}';
    protected $description = 'List which columns are required/nullable for a table';

    public function handle()
    {
        $table = $this->argument('table');
        $schema = $this->option('schema'); // used by Postgres
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                $rows = DB::select("
                    SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_TYPE, COLUMN_KEY, EXTRA
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                    ORDER BY ORDINAL_POSITION
                ", [$table]);

                if (!count($rows)) {
                    $this->error("Table '{$table}' not found in current database.");
                    return 1;
                }

                $data = [];
                foreach ($rows as $c) {
                    $autoInc = stripos($c->EXTRA ?? '', 'auto_increment') !== false;
                    $required =
                        ($c->IS_NULLABLE === 'NO' && is_null($c->COLUMN_DEFAULT) && !$autoInc)
                            ? 'REQUIRED'
                            : (($c->IS_NULLABLE === 'NO') ? 'NOT NULL (default)' : 'NULLABLE');

                    $data[] = [
                        'column'      => $c->COLUMN_NAME,
                        'requirement' => $required,
                        'type'        => $c->COLUMN_TYPE,
                        'default'     => is_null($c->COLUMN_DEFAULT) ? 'NULL' : (string)$c->COLUMN_DEFAULT,
                        'key'         => $c->COLUMN_KEY,
                        'extra'       => $c->EXTRA,
                    ];
                }

                $this->table(['Column', 'Requirement', 'Type', 'Default', 'Key', 'Extra'], $data);
                return 0;

            case 'pgsql':
                // Postgres: use information_schema and detect serial/identity by column_default
                $rows = DB::select("
                    SELECT
                        c.column_name,
                        c.is_nullable,
                        c.data_type,
                        c.udt_name,
                        c.character_maximum_length,
                        c.numeric_precision,
                        c.column_default,
                        tc.constraint_type AS key_type
                    FROM information_schema.columns c
                    LEFT JOIN information_schema.key_column_usage kcu
                        ON c.table_schema = kcu.table_schema
                       AND c.table_name  = kcu.table_name
                       AND c.column_name = kcu.column_name
                    LEFT JOIN information_schema.table_constraints tc
                        ON kcu.constraint_name = tc.constraint_name
                       AND kcu.table_schema   = tc.table_schema
                       AND kcu.table_name     = tc.table_name
                    WHERE c.table_schema = ?
                      AND c.table_name  = ?
                    ORDER BY c.ordinal_position
                ", [$schema, $table]);

                if (!count($rows)) {
                    $this->error("Table '{$schema}.{$table}' not found.");
                    return 1;
                }

                $data = [];
                foreach ($rows as $c) {
                    $default = $c->column_default; 
                    $autoInc = is_string($default) && (
                        stripos($default, 'nextval(') === 0 ||      
                        stripos($default, 'identity') !== false      
                    );

                    $required =
                        ($c->is_nullable === 'NO' && $default === null && !$autoInc)
                            ? 'REQUIRED'
                            : (($c->is_nullable === 'NO') ? 'NOT NULL (default/identity)' : 'NULLABLE');

                    // Build a friendly type string
                    $type = $c->data_type;
                    if ($c->character_maximum_length) {
                        $type .= '(' . $c->character_maximum_length . ')';
                    } elseif ($c->numeric_precision) {
                        $type .= '(' . $c->numeric_precision . ')';
                    }

                    $data[] = [
                        'column'      => $c->column_name,
                        'requirement' => $required,
                        'type'        => $type,
                        'default'     => $default === null ? 'NULL' : (string)$default,
                        'key'         => $c->key_type ?? '',
                        'extra'       => $autoInc ? 'auto_increment' : '',
                    ];
                }

                $this->table(['Column', 'Requirement', 'Type', 'Default', 'Key', 'Extra'], $data);
                return 0;

            case 'sqlite':
                // SQLite: PRAGMA table_info
                $rows = DB::select("PRAGMA table_info('{$table}')"); 
                if (!count($rows)) {
                    $this->error("Table '{$table}' not found.");
                    return 1;
                }

                $data = [];
                foreach ($rows as $c) {
                    $autoInc = (int)($c->pk ?? 0) === 1 && stripos((string)($c->type ?? ''), 'integer') !== false;
                    $required =
                        ((int)$c->notnull === 1 && $c->dflt_value === null && !$autoInc)
                            ? 'REQUIRED'
                            : (((int)$c->notnull === 1) ? 'NOT NULL (default)' : 'NULLABLE');

                    $data[] = [
                        'column'      => $c->name,
                        'requirement' => $required,
                        'type'        => (string)$c->type,
                        'default'     => $c->dflt_value === null ? 'NULL' : (string)$c->dflt_value,
                        'key'         => ((int)$c->pk === 1) ? 'PRIMARY KEY' : '',
                        'extra'       => $autoInc ? 'auto_increment' : '',
                    ];
                }

                $this->table(['Column', 'Requirement', 'Type', 'Default', 'Key', 'Extra'], $data);
                return 0;

            default:
                $this->error("Driver '{$driver}' not supported in this helper.");
                return 1;
        }
    }
}
