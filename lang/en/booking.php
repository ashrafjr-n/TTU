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
        'cancel_booking' => 'Cancel this booking',
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
        'already_have_active_booking' => 'You already have an active booking. You must cancel it before booking a new appointment.',
        'semester_limit_reached' => "You've reached the maximum of 4 bookings for this semester. Please get in touch with us.",
        'just_taken' => 'Sorry, this time was just booked. Please try another time.',
        'not_authorized_to_cancel' => 'You are not authorized to cancel this booking.',
        'not_cancellable' => 'This booking cannot be cancelled.',
    ],

    'flash' => [
        'booked_success' => 'Your appointment was booked successfully!',
        'cancelled_success' => 'Your booking was cancelled successfully.',
    ],

];
