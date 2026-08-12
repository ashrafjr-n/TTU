<?php

/*
|--------------------------------------------------------------------------
| Support widget — all content
|--------------------------------------------------------------------------
|
| Layers 1–3 of the widget are fully static: every string below is written
| by hand and rendered straight into the page, so browsing the topics never
| touches the network. Only the "Chat" option talks to the API.
|
| The three topics each need: label (layer 1 button), short (layer 2 answer),
| detailed (layer 3 "tell me more"), and exactly two followups, each with its
| own pre-written answer shown at layer 3. Keep that shape when editing —
| ChatbotFlow builds the navigation tree from it, and the tests assert it.
|
*/

return [

    'widget' => [
        'title' => 'Clinic support',
        'subtitle' => 'Quick answers about the clinic',
        'open' => 'Open the clinic support assistant',
        'close' => 'Close the support assistant',
    ],

    'welcome' => 'Welcome to the university clinic — how can I help you?',

    'options' => [
        'chat' => 'Chat',
        'back_to_menu' => 'Back to menu',
        'tell_me_more' => 'Tell me more',
    ],

    'chat' => [
        'intro' => "You're now in live chat. Ask me anything about the clinic — booking, logging in, working hours, contacting a doctor, or your medications.",
        'placeholder' => 'Type your question…',
        'send' => 'Send',
        'typing' => 'Typing…',
        'unavailable' => "Live chat isn't available right now. You can still use the quick topics — booking, logging in, or contacting a doctor — or reach the clinic through the Contact page.",
    ],

    'topics' => [

        'booking' => [
            'label' => 'How do I book an appointment?',

            'short' => 'Log in, then open “Book your appointment” from your dashboard. Pick one of the three available days — today, tomorrow, or the day after — choose an hour between 8:00 AM and 4:00 PM, then pick a free 5-minute slot and confirm it.',

            'detailed' => 'The booking page only ever shows the current booking window: today and the next two days. Clinic hours run from 8:00 AM to 4:00 PM, split into 5-minute slots — students book the slots in the earlier part of each hour, and the last slots of every hour are reserved for staff. You can hold only one active booking at a time, so if you already have one you will be asked to cancel it before booking again, and each person is limited to 4 confirmed bookings per semester. Once confirmed, the appointment appears on your dashboard and you get a reminder notification before it starts.',

            'followups' => [
                [
                    'question' => 'Can I cancel or change my appointment?',
                    'answer' => 'Yes. Open your dashboard or the booking page and cancel the current appointment — cancelling is what frees you to book a different time, since only one active booking is allowed at a time. You can cancel only your own booking, and only while it is still confirmed: an appointment whose time has already passed can no longer be cancelled. Cancelled bookings do not count toward your limit of 4 per semester.',
                ],
                [
                    'question' => 'Why is a time slot unavailable?',
                    'answer' => 'A slot is shown as unavailable for one of three reasons: someone already booked it, its time has already passed today, or it belongs to the other group — the last slots of each hour are kept for staff. Those staff slots are released to students on the same day once their start time passes without being booked, and stay open until the clinic closes at 4:00 PM. And if you already have an active booking, you need to cancel it first before any slot can be booked.',
                ],
            ],
        ],

        'login' => [
            'label' => 'How do I log in?',

            'short' => 'From the home page pick your account type, then sign in with your university or staff ID number — or the email on your account — plus your password. After signing in you go straight to the dashboard for your role.',

            'detailed' => 'The login field accepts either your university/staff ID number or the email address registered on your account, together with your password. There is no public sign-up: accounts are created by the clinic administration, so if you do not have one yet, contact the clinic instead of trying to register. For security, login is locked for a short period after 5 failed attempts, and a deactivated account cannot sign in at all until an administrator re-enables it. Doctors and administrators use the same login page and are routed to their own dashboards automatically.',

            'followups' => [
                [
                    'question' => 'I forgot my password, or my login is not working.',
                    'answer' => 'First check that you are entering your ID number exactly as it was issued, or the email on your account, and that Caps Lock is off. If you see a message about too many attempts, wait for the timer to finish before trying again. There is no self-service password reset in the system, so a forgotten password — or an account that has been deactivated — has to be reset by the clinic administration: reach them through the Contact page or at the clinic desk.',
                ],
                [
                    'question' => 'Which account type should I choose?',
                    'answer' => 'Students and staff each have their own entry on the home page, and the difference matters: your role decides which appointment slots you can book (students use the earlier slots in each hour, staff the last ones) and which pages you can open. Doctors and administrators sign in from the same login page and land on their own dashboards. If you picked the wrong entry you can go back and change it before signing in — your actual role comes from your account, not from the button you pressed.',
                ],
            ],
        ],

        'contact_doctor' => [
            'label' => 'How do I contact a doctor?',

            'short' => 'Open “Contact” from the top menu, pick the doctor you want from the list, write your message and send it. The doctor receives it as a notification, and their reply comes back to you in the bell icon in the header.',

            'detailed' => 'The Contact page is available to students and staff once signed in. Your name is filled in from your account automatically, so you only choose a doctor and type your message, up to 2000 characters. Sending it delivers the message to that doctor as a notification; the doctor can reply directly from their own notifications panel, and the reply arrives back to you the same way — open the bell icon in the header to read it. For anything urgent, come to the clinic in person during working hours, 8:00 AM to 4:00 PM, rather than waiting for a reply.',

            'followups' => [
                [
                    'question' => "Where do I see the doctor's reply?",
                    'answer' => "Replies arrive as notifications. Click the bell icon in the header to open your notifications panel and the doctor's reply will be listed there; unread ones are marked with a dot, and the icon shows a counter. Opening a notification marks it as read, and you can also mark everything as read at once from the top of the panel.",
                ],
                [
                    'question' => 'Where do I find my prescriptions and visit reports?',
                    'answer' => 'After a visit the doctor writes a visit report — the condition, examination, diagnosis, treatment plan, and any prescribed medications. Once it is completed you receive a notification, and you can read it any time under “My Medications” from your dashboard, which lists every report together with the medications and quantities dispensed. If a report you are expecting is not there yet, the doctor has not completed it.',
                ],
            ],
        ],

    ],

];
