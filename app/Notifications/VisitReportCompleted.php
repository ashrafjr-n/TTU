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
        $medications = $medications !== '' ? $medications : 'لا توجد أدوية موصوفة';

        return [
            'type' => 'visit_report',
            'title' => 'تقرير زيارتك جاهز',
            'body' => "تقرير زيارتك جاهز — الحالة: {$condition}. الأدوية: {$medications}.",
            'url' => route('medications.mine', [], false),
        ];
    }
}
