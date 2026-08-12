<?php

return [

    'title' => 'Full Booking History',
    'heading' => 'Full Booking History',
    'intro' => 'Every booking ever created in the system — independent of the current doctor day-assignment scoping.',

    'filters' => [
        'from_label' => 'From date',
        'to_label' => 'To date',
        'status_label' => 'Status',
        'status_all' => 'All statuses',
        'search_label' => 'User name or ID',
        'search_placeholder' => 'Search by name or student/staff ID...',
        'apply' => 'Filter',
        'clear' => 'Clear filters',
    ],

    'table' => [
        'date' => 'Date',
        'time' => 'Time',
        'user' => 'User',
        'role' => 'Role',
        'status' => 'Status',
        'status_confirmed' => 'Confirmed',
        'status_cancelled' => 'Cancelled',
    ],

    'empty' => 'No matching bookings',

];
