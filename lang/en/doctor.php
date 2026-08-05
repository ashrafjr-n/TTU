<?php

return [

    'title' => 'Doctor Dashboard',
    'badge' => 'Doctor Dashboard',
    'today_bookings' => "Today's bookings",

    'attendance' => [
        'heading' => "Today's attendance",
        'none_today' => 'No attendance recorded today',
        'present' => 'Currently present — checked in at :time',
        'ended' => 'Your shift ended today — from :in to :out',
        'auto_checkout' => 'Auto checkout',
        'checkout_button' => 'Check out',
        'check_in_required' => 'You must check in first.',
        'already_checked_out' => "You've already checked out today.",
        'check_out_success' => 'You have checked out successfully.',
    ],

    'bookings_table' => [
        'heading' => 'Bookings schedule',
        'choose_day' => 'Choose day',
        'edit_report' => 'Edit report',
        'attach_report' => 'Attach report',
        'not_available_yet' => 'Not available yet',
        'not_available_yet_hint' => 'The report can be attached once the appointment time arrives',
        'cancel_confirm' => 'Are you sure you want to cancel this booking?',
        'empty' => 'No bookings on this date',
        'not_cancellable' => 'This booking cannot be cancelled.',
        'cancel_success' => 'The booking was cancelled and the patient was notified.',
    ],

    'report_modal' => [
        'close' => 'Close',
        'edit_title' => 'Edit Report',
        'create_title' => 'Attach Report',
        'patient_name' => "Patient's name",
        'patient_identifier' => 'Student/staff ID',
        'appointment_date' => 'Appointment date',
        'appointment_time' => 'Appointment time',
        'condition' => 'Condition',
        'examination' => 'Examination',
        'diagnosis' => 'Diagnosis (optional)',
        'treatment_plan' => 'Treatment plan (optional)',
        'notes' => 'Notes (optional)',
        'medications' => 'Prescribed medications',
        'add_medication' => '+ Add medication',
        'no_medications_added' => 'No medications added',
        'search_placeholder' => 'Search for a medication...',
        'no_results' => 'No results',
        'available_prefix' => 'Available: ',
        'cancel' => 'Cancel',
        'save_edit' => 'Save changes',
        'save_create' => 'Save report',
        'not_confirmed' => 'A report cannot be attached to a booking that is not confirmed.',
        'not_started_yet' => 'A report cannot be attached before the appointment time arrives.',
        'insufficient_stock' => 'The requested amount of ":name" is not available in stock (available: :stock).',
        'save_success' => 'The visit report was saved successfully.',
    ],

];
