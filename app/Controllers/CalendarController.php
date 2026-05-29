<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ActivityVagasDisplay;
use App\Core\Response;
use App\Core\Session;
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

        $rangeStart = trim((string) ($_GET['start'] ?? ''));
        $rangeEnd   = trim((string) ($_GET['end'] ?? ''));

        if ($this->isValidDate($rangeStart) && $this->isValidDate($rangeEnd) && $rangeStart <= $rangeEnd) {
            $adjustedStart = $rangeStart;
            $adjustedEnd   = $rangeEnd;
        } else {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            // Extend range to show days from prev/next month visible in calendar grid
            $firstDayOfWeek = (int) date('w', strtotime($startDate)); // 0=Sun
            $adjustedStart  = date('Y-m-d', strtotime("-{$firstDayOfWeek} days", strtotime($startDate)));

            $lastDay       = (int) date('t', strtotime($startDate));
            $lastDate      = sprintf('%04d-%02d-%02d', $year, $month, $lastDay);
            $lastDayOfWeek = (int) date('w', strtotime($lastDate));
            $daysAfter     = 6 - $lastDayOfWeek;
            $adjustedEnd   = date('Y-m-d', strtotime("+{$daysAfter} days", strtotime($lastDate)));
        }

        $role = Session::get('user_role');
        if ($grupoId === null && $role === 'proj') {
            $userGrupo = (int) Session::get('user_grupo_id');
            if ($userGrupo > 0) {
                $grupoId = $userGrupo;
            }
        }

        $activities = $this->activityModel->listByDateRange($adjustedStart, $adjustedEnd, $grupoId);
        ActivityVagasDisplay::enrichList($activities);

        Response::json([
            'success'    => true,
            'year'       => $year,
            'month'      => $month,
            'startDate'  => $adjustedStart,
            'endDate'    => $adjustedEnd,
            'activities' => $activities,
        ]);
    }

    private function isValidDate(string $date): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }
}
