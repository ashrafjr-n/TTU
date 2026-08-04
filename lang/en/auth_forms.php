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
        'login_field' => 'Email or student/staff ID',
        'login_placeholder' => 'example@ttu.edu.jo or 20210123',
        'password' => 'Password',
        'remember' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'submit' => 'Log In',
        'no_account' => "Don't have an account?",
        'register_now' => 'Sign up now',
        'errors' => [
            'login_required' => 'Please enter your email or student/staff ID.',
            'password_required' => 'Please enter your password.',
            'invalid_credentials' => 'These credentials do not match our records.',
            'account_disabled' => 'This account has been disabled. Please contact the clinic administration.',
            'too_many_attempts' => 'Too many attempts. Please try again in :seconds seconds.',
        ],
    ],

    'register' => [
        'page_title' => 'Create Account',
        'heading' => 'Create a :role Account',
        'full_name' => 'Full name',
        'student_id' => 'Student ID',
        'student_id_placeholder' => 'Example: 20210123',
        'student_id_help' => 'Exactly 8 digits. We will check it against the university records.',
        'staff_id' => 'Staff ID',
        'staff_id_placeholder' => 'Example: 2320',
        'staff_id_help' => 'Exactly 4 digits. We will check it against the university records.',
        'email' => 'Email',
        'doctor_email_help' => 'A fixed email address used only for the doctor account.',
        'password' => 'Password',
        'confirm_password' => 'Confirm password',
        'submit' => 'Create Account',
        'have_account' => 'Already have an account?',
        'login_now' => 'Log in',
        'identifier_label' => [
            'student' => 'student ID',
            'staff' => 'staff ID',
        ],
        'errors' => [
            'invalid_role' => 'Please choose a valid account type to register from the home page.',
            'role_error' => 'Something went wrong identifying the account type. Please try again.',
            'identifier_required' => 'Please enter your :label.',
            'student_id_digits' => 'The student ID must be exactly 8 digits.',
            'staff_id_digits' => 'The staff ID must be exactly 4 digits.',
            'identifier_taken' => 'This :label is already in use by another account.',
            'email_taken' => 'This email is already in use by another account.',
            'password_confirmed' => 'The passwords do not match.',
            'identifier_not_found' => 'The ID you entered was not found or is not valid in the university records.',
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
