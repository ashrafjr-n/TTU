<?php

return [

    'title' => 'Book an Appointment',

    'heading' => [
        'student' => 'Student Booking',
        'staff' => 'Staff Booking',
    ],

    'book_appointment' => 'Book your appointment',
    'fee_label' => 'Medication fee',
    'fee_value' => 'JD 0.20 per medication',

    'legend' => [
        'available' => 'Available',
        'taken' => 'Booked',
        'past' => 'Time has passed',
        'released' => 'Extra slot released from the staff quota',
    ],

    'choose_day' => 'Choose the day',
    'choose_hour' => 'Choose the hour',
    'book_this_slot' => 'Book this time',

    'day' => [
        'today' => 'Today',
        'tomorrow' => 'Tomorrow',
        'day_after' => 'Day after tomorrow',
    ],

    'confirm_modal' => [
        'heading' => 'Confirm Booking',
        'question_before' => 'Do you want to confirm your appointment on',
        'question_middle' => 'at',
        'question_end' => '?',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm Booking',
    ],

    'active_modal' => [
        'close' => 'Close',
        'heading' => 'You have an active booking',
        'intro' => 'You need to cancel your current appointment before booking a new one:',
        'hour_prefix' => 'at',
        // Doctor names are stored with their "د." prefix already — not repeated here
        'with_doctor' => 'with :doctor',
        'doctor_unassigned' => 'Doctor unassigned',
        'cancel_booking' => 'Cancel this booking',
    ],

    'closed_modal' => [
        'close' => 'Close',
        'heading' => 'The clinic is closed today',
        'intro' => 'Friday and Saturday are the clinic weekend, and no days are available to book until the next working week begins. Booking reopens on Sunday.',
    ],

    'semester_limit_modal' => [
        'close' => 'Close',
        'heading' => 'Semester booking limit reached',
        'intro' => "You've reached the maximum of 4 bookings for this semester. Please get in touch with us.",
    ],

    'errors' => [
        'students_only' => 'This time is for students only.',
        'staff_only' => 'This time is for staff only.',
        'released_slot_closed' => 'The booking window for released extra slots has ended for today.',
        'slot_expired' => 'This appointment time has already passed.',
        'clinic_closed' => 'The clinic is closed on this day. Bookings are only possible on working days (Sunday – Thursday).',
        'already_have_active_booking' => 'You already have an active booking. You must cancel it before booking a new appointment.',
        'semester_limit_reached' => "You've reached the maximum of 4 bookings for this semester. Please get in touch with us.",
        'just_taken' => 'Sorry, this time was just booked. Please try another time.',
        'not_authorized_to_cancel' => 'You are not authorized to cancel this booking.',
        'not_cancellable' => 'This booking cannot be cancelled.',
    ],

    'flash' => [
        'booked_success' => 'Your appointment was booked successfully!',
        // Confirmation toast after booking — carries the date and time because
        // the user lands on their dashboard, not back on the booking page
        'booked_success_toast' => 'Your appointment is confirmed for :date — at :time.',
        'cancelled_success' => 'Your booking was cancelled successfully.',
    ],

    'toast' => [
        'title' => 'Booking confirmed',
        'close' => 'Dismiss notification',
    ],

];
