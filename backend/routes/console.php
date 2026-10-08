<?php

use Illuminate\Support\Facades\Schedule;

// Pengingat & eskalasi harian (07:00). Pada Windows/Laragon dijalankan oleh Task Scheduler tiap menit.
Schedule::command('reminders:daily')->dailyAt('07:00');