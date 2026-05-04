<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbFreshOracle extends Command
{
    protected $signature = 'db:fresh-oracle';
    protected $description = 'Drop all Oracle tables/sequences and run migrations (Oracle-safe substitute for migrate:fresh)';

    public function handle(): int
    {
        $this->info('Dropping all sequences...');
        $sequences = DB::select('SELECT sequence_name FROM user_sequences');
        foreach ($sequences as $row) {
            try {
                DB::statement("DROP SEQUENCE \"{$row->SEQUENCE_NAME}\"");
            } catch (\Exception) {}
        }

        $this->info('Dropping all tables...');
        // Tenta até 5 passes para resolver dependências de FK
        for ($pass = 0; $pass < 5; $pass++) {
            $tables = DB::select('SELECT table_name FROM user_tables');
            if (empty($tables)) break;
            foreach ($tables as $row) {
                try {
                    DB::statement("DROP TABLE \"{$row->TABLE_NAME}\" CASCADE CONSTRAINTS");
                } catch (\Exception) {}
            }
        }

        $this->info('Running migrations...');
        $this->call('migrate', ['--force' => true, '--no-interaction' => true]);

        return 0;
    }
}
