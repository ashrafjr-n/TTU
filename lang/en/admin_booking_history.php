<?php

return [

    'title' => 'Full Booking History',
    'heading' => 'Full Booking History',
    'intro' => 'Every booking ever created in the system — independent of the current doctor day-assignment scoping.',

    'filters' => [
        // Week filter: each option is one full Saturday-to-Friday week, the
        // same week convention used by the doctor dashboard and booking page
        'week_label' => 'Week',
        'week_all' => 'All weeks',
        'week_current' => 'This week',
        'week_last' => 'Last week',
        'week_two_ago' => '2 weeks ago',
        'week_n_ago' => ':count weeks ago',
        'search_label' => 'User name or ID',
        'search_placeholder' => 'Search by name or student/staff ID...',
        'apply' => 'Filter',
        'clear' => 'Clear filters',
    ],

    'empty' => 'No matching bookings',

];
