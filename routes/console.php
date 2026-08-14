<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:run')->daily()->at('02:00');
Schedule::command('backup:verify')->weekly()->sundays()->at('03:00');
