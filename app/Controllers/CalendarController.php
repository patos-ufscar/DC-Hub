<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\Activity;
use PDO;

class CalendarController
{
    private Activity $activityModel;

    public function __construct(private PDO $db)
    {
        $this->activityModel = new Activity($db);
    }

    public function getData(): void
    {
        $year    = (int) ($_GET['year'] ?? date('Y'));
        $month   = (int) ($_GET['month'] ?? date('n'));
        $grupoId = !empty($_GET['grupo_id']) ? (int) $_GET['grupo_id'] : null;

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));

        // Extend range to show days from prev/next month visible in calendar grid
        $firstDayOfWeek = (int) date('w', strtotime($startDate)); // 0=Sun
        $adjustedStart  = date('Y-m-d', strtotime("-{$firstDayOfWeek} days", strtotime($startDate)));

        $lastDay       = (int) date('t', strtotime($startDate));
        $lastDate      = sprintf('%04d-%02d-%02d', $year, $month, $lastDay);
        $lastDayOfWeek = (int) date('w', strtotime($lastDate));
        $daysAfter     = 6 - $lastDayOfWeek;
        $adjustedEnd   = date('Y-m-d', strtotime("+{$daysAfter} days", strtotime($lastDate)));

        $activities = $this->activityModel->listByDateRange($adjustedStart, $adjustedEnd, $grupoId);

        Response::json([
            'success'    => true,
            'year'       => $year,
            'month'      => $month,
            'startDate'  => $adjustedStart,
            'endDate'    => $adjustedEnd,
            'activities' => $activities,
        ]);
    }
}
