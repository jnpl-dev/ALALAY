<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:run')->daily()->at('02:00')->withoutOverlapping();
Schedule::command('backup:verify')->weekly()->sundays()->at('03:00')->withoutOverlapping();
Schedule::command('backup:prune')->weekly()->sundays()->at('04:00')->withoutOverlapping();
