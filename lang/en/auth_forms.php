<?php

return [

    'back' => 'Back',

    'account_type' => 'Account type',
    'change_type' => 'Change type',

    'login' => [
        'heading' => 'Log In',
        'heading_role' => 'Log in as :role',
        'role_student' => 'Student',
        'role_staff' => 'Staff',
        'role_doctor' => 'Doctor',
        'role_admin' => 'Admin',

        // Generic wording — shown when landing on /login with no account type
        'login_field' => 'University or staff ID number',

        // Per-role wording, driven by the home page card the user came from
        'login_field_student' => 'University ID number',
        'login_field_staff' => 'Staff ID number',
        'login_field_doctor' => "Doctor's staff ID number",
        'login_field_admin' => 'Email address',

        'password' => 'Password',
        'remember' => 'Remember me',
        'submit' => 'Log In',
        'errors' => [
            'login_required' => 'Please enter your ID number.',
            'password_required' => 'Please enter your password.',
            'invalid_credentials' => 'These credentials do not match our records.',
            'account_disabled' => 'This account has been disabled. Please contact the clinic administration.',
            'too_many_attempts' => 'Too many attempts. Please try again in :seconds seconds.',
        ],
    ],

    'confirm_password' => [
        'heading' => 'Confirm your password',
        'intro' => 'This is a secure area of the app. Please confirm your password before continuing.',
        'password' => 'Password',
        'submit' => 'Confirm',
    ],

    'verify_email' => [
        'heading' => 'Verify your email',
        'intro' => "Please verify your email by clicking the link we sent you before you get started. If you didn't get the email, we're happy to send another one.",
        'link_sent' => 'A new verification link has been sent to your email address on file.',
        'resend' => 'Resend verification email',
    ],

];
