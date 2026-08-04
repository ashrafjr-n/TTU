<?php

namespace App\Notifications;

use App\Models\VisitReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VisitReportCompleted extends Notification
{
    use Queueable;

    public function __construct(protected VisitReport $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $condition = $this->report->condition ?: $this->report->diagnosis;
        $medications = $this->report->medications->pluck('name')->implode('، ');

        return [
            'type' => 'visit_report',
            'title_key' => 'notifications.visit_report.title',
            'body_key' => $medications !== ''
                ? 'notifications.visit_report.body'
                : 'notifications.visit_report.body_no_meds',
            'body_params' => ['condition' => $condition, 'medications' => $medications],
            'url' => route('medications.mine', [], false),
        ];
    }
}
