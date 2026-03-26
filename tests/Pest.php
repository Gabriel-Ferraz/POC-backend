<?php

use Illuminate\Foundation\Testing\{RefreshDatabase, WithCachedConfig};

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->use(WithCachedConfig::class)
    ->in('Feature', 'Unit')
    ->afterEach(function () {
        gc_collect_cycles();
    });

arch()
    ->expect('App')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);
