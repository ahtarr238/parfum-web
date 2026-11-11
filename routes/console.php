<?php

use Illuminate\Support\Facades\Schedule;

/*
| Shared hosting: cukup satu cron `* * * * * php artisan schedule:run`.
| Tidak ada proses yang jalan terus; queue diproses per menit lalu berhenti.
*/

Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();

Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
