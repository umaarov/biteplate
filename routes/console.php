<?php

use Illuminate\Support\Facades\Schedule;

// Scenario B: fire reservation reminders roughly two hours before each booking.
Schedule::command('biteplate:reservations:remind')->everyFiveMinutes()->withoutOverlapping();
