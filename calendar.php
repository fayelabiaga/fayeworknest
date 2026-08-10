<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Calendar";

$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$firstDay = mktime(0,0,0,$month,1,$year);
$daysInMonth = (int)date('t', $firstDay);
$startWeekday = (int)date('w', $firstDay); // 0=Sun
$monthLabel = date('F Y', $firstDay);
$prevMonth = $month - 1; $prevYear = $year; if ($prevMonth<1){$prevMonth=12;$prevYear--;}
$nextMonth = $month + 1; $nextYear = $year; if ($nextMonth>12){$nextMonth=1;$nextYear++;}

$rangeStart = date('Y-m-01', $firstDay);
$rangeEnd = date('Y-m-t', $firstDay);

// Collect events per day: date => [ [title, type] ]
$events = [];
function addEvent(&$events, $date, $title, $type) {
    if (!$date) return;
    $events[$date][] = ['title' => $title, 'type' => $type];
}

$stmt = $pdo->prepare("SELECT activity_name, activity_date FROM school_activities WHERE user_id=? AND activity_date BETWEEN ? AND ?");
$stmt->execute([$current_user_id, $rangeStart, $rangeEnd]);
foreach ($stmt->fetchAll() as $r) addEvent($events, $r['activity_date'], $r['activity_name'], 'School');

$stmt = $pdo->prepare("SELECT event_name, event_date FROM school_events WHERE user_id=? AND event_date BETWEEN ? AND ?");
$stmt->execute([$current_user_id, $rangeStart, $rangeEnd]);
foreach ($stmt->fetchAll() as $r) addEvent($events, $r['event_date'], $r['event_name'], 'School');

$stmt = $pdo->prepare("SELECT assignment_name, deadline FROM school_assignments WHERE user_id=? AND deadline BETWEEN ? AND ?");
$stmt->execute([$current_user_id, $rangeStart, $rangeEnd]);
foreach ($stmt->fetchAll() as $r) addEvent($events, $r['deadline'], $r['assignment_name'], 'Deadline');

$stmt = $pdo->prepare("SELECT activity_name, deadline FROM research_activities WHERE user_id=? AND deadline BETWEEN ? AND ?");
$stmt->execute([$current_user_id, $rangeStart, $rangeEnd]);
foreach ($stmt->fetchAll() as $r) addEvent($events, $r['deadline'], $r['activity_name'], 'Research');

$stmt = $pdo->prepare("SELECT deadline_name, deadline_date FROM research_deadlines WHERE user_id=? AND deadline_date BETWEEN ? AND ?");
$stmt->execute([$current_user_id, $rangeStart, $rangeEnd]);
foreach ($stmt->fetchAll() as $r) addEvent($events, $r['deadline_date'], $r['deadline_name'], 'Deadline');

$stmt = $pdo->prepare("SELECT project_name, deadline FROM personal_projects WHERE user_id=? AND deadline BETWEEN ? AND ?");
$stmt->execute([$current_user_id, $rangeStart, $rangeEnd]);
foreach ($stmt->fetchAll() as $r) addEvent($events, $r['deadline'], $r['project_name'], 'Projects');

$stmt = $pdo->prepare("SELECT title, item_type, item_date, id FROM calendar_items WHERE user_id=? AND item_date BETWEEN ? AND ?");
$stmt->execute([$current_user_id, $rangeStart, $rangeEnd]);
foreach ($stmt->fetchAll() as $r) addEvent($events, $r['item_date'], $r['title'], $r['item_type']);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$today = date('Y-m-d');
?>
<div class="page-header">
    <h1>📅 Calendar</h1>
    <div class="sub">All your deadlines, events, and activities in one view.</div>
</div>

<div class="card">
<div class="calendar-header">
    <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-secondary btn-small">&larr; Prev</a>
    <h2><?= $monthLabel ?></h2>
    <div style="display:flex;gap:8px;">
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-secondary btn-small">Next &rarr;</a>
        <button class="btn btn-small" onclick="openModal('addModal')">+ Add</button>
    </div>
</div>

<div class="calendar-grid">
    <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
        <div class="calendar-dow"><?= $d ?></div>
    <?php endforeach; ?>

    <?php for ($i=0; $i<$startWeekday; $i++): ?>
        <div class="calendar-cell empty"></div>
    <?php endfor; ?>

    <?php for ($day=1; $day<=$daysInMonth; $day++):
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $isToday = $dateStr === $today;
    ?>
        <div class="calendar-cell <?= $isToday?'today':'' ?>">
            <div class="date-num"><?= $day ?></div>
            <?php if (!empty($events[$dateStr])): foreach ($events[$dateStr] as $ev): ?>
                <span class="cal-event color-<?= $ev['type'] ?>" title="<?= htmlspecialchars($ev['title']) ?>"><?= htmlspecialchars($ev['title']) ?></span>
            <?php endforeach; endif; ?>
        </div>
    <?php endfor; ?>
</div>

<div class="legend">
    <span><span class="cal-dot color-School"></span>School</span>
    <span><span class="cal-dot color-Research"></span>Research</span>
    <span><span class="cal-dot color-Learning"></span>Learning</span>
    <span><span class="cal-dot color-Projects"></span>Projects</span>
    <span><span class="cal-dot color-Deadline"></span>Deadlines</span>
</div>
</div>

<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
        <h3>Add Calendar Item</h3>
        <form method="POST" action="actions/calendar_actions.php">
            <input type="hidden" name="action" value="add_item">
            <input type="hidden" name="month" value="<?= $month ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
            <div class="form-group"><label>Type</label>
                <select name="item_type"><option>School</option><option>Research</option><option>Learning</option><option>Projects</option><option>Deadline</option><option>Other</option></select>
            </div>
            <div class="form-group"><label>Date</label><input type="date" name="item_date" required></div>
            <button type="submit" class="btn btn-block">Add to Calendar</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
