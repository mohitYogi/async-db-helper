<?php

namespace MohitYogi\AsyncDBHelper;

use Illuminate\Support\ServiceProvider;
use MohitYogi\AsyncDBHelper\Commands\MoveDataFromRedisToDBCommand;

class AsyncDBHelperServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->commands([
            MoveDataFromRedisToDBCommand::class
        ]);
    }
}