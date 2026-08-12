<?php

return [

    'create' => [
        'page_title' => 'Add Doctor',
        'heading' => 'Add a New Doctor Account',
        'submit' => 'Add Doctor',
    ],

    'edit' => [
        'page_title' => 'Edit Doctor Account',
        'heading' => 'Edit account: :name',
        'cancel' => 'Cancel',
        'submit' => 'Save changes',
    ],

    'errors' => [
        // Identifiers reserved for the fixed seeded accounts — handing one to a
        // new doctor blocks UserSeeder from assigning its own, aborting the
        // whole seed.
        'reserved_identifier' => 'This number is reserved for a built-in account. Please choose another one.',
    ],

    'name_label' => 'Full name',
    'identifier_label' => 'Staff ID number',
    'identifier_placeholder' => 'e.g. 405',
    'password_label' => 'Password',
    'password_confirmation_label' => 'Confirm password',
    'working_days_label' => 'Weekly working days',
    'working_days_hint' => "Set the days the doctor is expected to be present",

];
