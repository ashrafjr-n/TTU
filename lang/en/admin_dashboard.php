<?php

return [

    'title' => 'Admin Dashboard',

    'stats' => [
        'students' => 'Registered students',
        'staff' => 'Registered staff',
        'doctors' => 'Doctors',
        'today_bookings' => "Today's bookings",
    ],

    'analytics_heading' => 'Statistics & Analytics',

    'week_chart' => [
        'heading' => 'Weekly bookings',
        'total' => 'Total: :total',
        'subheading' => 'Daily breakdown (Sunday–Saturday)',
    ],

    'students_series' => 'Students',
    'staff_series' => 'Staff',
    'role_chart' => [
        'heading' => 'Students vs Staff',
        'subheading' => "This week's bookings by category",
    ],

    'hourly_chart' => [
        'heading' => 'Occupancy rate by hour',
        'description' => 'Share of booked slots out of all available slots per hour (across the entire history)',
        'occupancy_suffix' => '% occupied',
    ],

    'busiest' => [
        'heading' => 'Busiest hours',
        'empty' => 'Not enough data yet',
    ],

    'errors' => [
        'cannot_disable_admin' => 'The admin account cannot be disabled.',
    ],

    'flash' => [
        'user_activated' => 'The account :name has been activated.',
        'user_deactivated' => 'The account :name has been deactivated.',
        'doctor_created' => 'The doctor account was added successfully.',
        'doctor_updated' => 'The account ":name" was updated.',
        'record_added' => 'The ID was added successfully.',
        'record_removed' => 'The record was deleted successfully.',
        'medication_added' => 'The medication was added successfully.',
        'medication_edited' => 'The medication details were updated successfully.',
        'medication_restocked' => 'Added :amount to the stock of ":name".',
        'medication_activated' => '":name" has been activated.',
        'medication_deactivated' => '":name" has been deactivated.',
    ],

];
