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
        'login_field' => 'University or staff ID number',
        'login_placeholder' => 'e.g. 20210123',
        'password' => 'Password',
        'remember' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'submit' => 'Log In',
        'errors' => [
            'login_required' => 'Please enter your ID number.',
            'password_required' => 'Please enter your password.',
            'invalid_credentials' => 'These credentials do not match our records.',
            'account_disabled' => 'This account has been disabled. Please contact the clinic administration.',
            'too_many_attempts' => 'Too many attempts. Please try again in :seconds seconds.',
        ],
    ],

    'forgot_password' => [
        'heading' => 'Forgot your password?',
        'intro' => "No problem. Enter your email and we'll send you a link to reset your password.",
        'email' => 'Email',
        'submit' => 'Send reset link',
    ],

    'reset_password' => [
        'heading' => 'Reset your password',
        'email' => 'Email',
        'password' => 'New password',
        'confirm_password' => 'Confirm password',
        'submit' => 'Reset password',
    ],

    'confirm_password' => [
        'heading' => 'Confirm your password',
        'intro' => 'This is a secure area of the app. Please confirm your password before continuing.',
        'password' => 'Password',
        'submit' => 'Confirm',
    ],

    'verify_email' => [
        'heading' => 'Verify your email',
        'intro' => "Thanks for signing up! Please verify your email by clicking the link we sent you before you get started. If you didn't get the email, we're happy to send another one.",
        'link_sent' => 'A new verification link has been sent to the email address you used when signing up.',
        'resend' => 'Resend verification email',
    ],

];
