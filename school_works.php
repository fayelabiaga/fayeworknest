<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "School Works";
$tab = $_GET['tab'] ?? 'activities';
if (!in_array($tab, ['activities','assignments','events','ganaps','schedule'])) $tab = 'activities';

if ($tab === 'activities') {
    $stmt = $pdo->prepare("SELECT * FROM school_activities WHERE user_id=? ORDER BY activity_date ASC, id DESC");
    $stmt->execute([$current_user_id]); $rows = $stmt->fetchAll();
} elseif ($tab === 'assignments') {
    $stmt = $pdo->prepare("SELECT * FROM school_assignments WHERE user_id=? ORDER BY deadline ASC, id DESC");
    $stmt->execute([$current_user_id]); $rows = $stmt->fetchAll();
} elseif ($tab === 'events') {
    $stmt = $pdo->prepare("SELECT * FROM school_events WHERE user_id=? ORDER BY event_date ASC, id DESC");
    $stmt->execute([$current_user_id]); $rows = $stmt->fetchAll();
} elseif ($tab === 'ganaps') {
    $stmt = $pdo->prepare("SELECT * FROM school_ganaps WHERE user_id=? ORDER BY ganap_date ASC, id DESC");
    $stmt->execute([$current_user_id]); $rows = $stmt->fetchAll();
} else {
    // schedule tab
    $stmt = $pdo->prepare("SELECT * FROM semesters WHERE user_id=? ORDER BY created_at ASC");
    $stmt->execute([$current_user_id]); $semesters = $stmt->fetchAll();

    $activeSemId = (int)($_GET['semester_id'] ?? 0);
    if (!$activeSemId) {
        foreach ($semesters as $sm) { if ($sm['is_current']) { $activeSemId = $sm['id']; break; } }
        if (!$activeSemId && $semesters) $activeSemId = $semesters[0]['id'];
    }

    $classes = [];
    $totalUnits = 0;
    $classesByDay = [];
    if ($activeSemId) {
        $stmt = $pdo->prepare("SELECT * FROM class_schedule WHERE semester_id=? ORDER BY id ASC");
        $stmt->execute([$activeSemId]);
        $classes = $stmt->fetchAll();
        foreach ($classes as $c) { $totalUnits += (float)($c['units'] ?? 0); }

        // Parse a free-text "days" value (e.g. "MWF", "TTH", "SAT") into full day names
        function parseScheduleDays($str) {
            $str = strtoupper(preg_replace('/[^A-Z]/', '', (string)$str));
            if ($str === '') return [];
            $tokenMap = [
                'MONDAY'=>'Monday','TUESDAY'=>'Tuesday','WEDNESDAY'=>'Wednesday','THURSDAY'=>'Thursday','FRIDAY'=>'Friday','SATURDAY'=>'Saturday','SUNDAY'=>'Sunday',
                'MON'=>'Monday','TUE'=>'Tuesday','WED'=>'Wednesday','THU'=>'Thursday','FRI'=>'Friday','SAT'=>'Saturday','SUN'=>'Sunday',
                'TH'=>'Thursday','SU'=>'Sunday',
                'M'=>'Monday','T'=>'Tuesday','W'=>'Wednesday','F'=>'Friday','S'=>'Saturday',
            ];
            $keys = array_keys($tokenMap);
            usort($keys, fn($a,$b) => strlen($b) - strlen($a));
            preg_match_all('/' . implode('|', $keys) . '/', $str, $m);
            $result = [];
            foreach ($m[0] as $tok) {
                $day = $tokenMap[$tok];
                if (!in_array($day, $result)) $result[] = $day;
            }
            return $result;
        }

        $dayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        foreach ($dayOrder as $d) { $classesByDay[$d] = []; }
        foreach ($classes as $c) {
            foreach (parseScheduleDays($c['days']) as $d) {
                $classesByDay[$d][] = $c;
            }
        }
        // Keep only days that actually have classes, in weekday order
        $classesByDay = array_filter($classesByDay, fn($list) => !empty($list));
    }
}

$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT assignment_name AS name, deadline AS d FROM school_assignments WHERE user_id=? AND deadline >= ? AND status!='Done' ORDER BY deadline ASC LIMIT 4");
$stmt->execute([$current_user_id, $today]);
$sideDeadlines = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>🎓 School Works</h1>
            <div class="sub">Stay on top of your activities, assignments, and events.</div>
        </div>
        <?php if ($tab === 'schedule' && empty($activeSemId)): ?>
            <button class="btn" onclick="openModal('addSemesterModal')">+ New Semester</button>
        <?php else: ?>
            <button class="btn" onclick="openModal('addModal')">+ Add <?= $tab==='activities'?'Activity':($tab==='assignments'?'Assignment':($tab==='events'?'Event':($tab==='ganaps'?'Ganap':'Class'))) ?></button>
        <?php endif; ?>
    </div>
</div>

<div class="tabs">
    <a href="?tab=activities" class="tab-link <?= $tab==='activities'?'active':'' ?>">Activities To Do</a>
    <a href="?tab=assignments" class="tab-link <?= $tab==='assignments'?'active':'' ?>">Assignments / Projects</a>
    <a href="?tab=events" class="tab-link <?= $tab==='events'?'active':'' ?>">Events</a>
    <a href="?tab=ganaps" class="tab-link <?= $tab==='ganaps'?'active':'' ?>">Ganaps</a>
    <a href="?tab=schedule" class="tab-link <?= $tab==='schedule'?'active':'' ?>">My Schedule</a>
</div>

<div class="content-wrap">
<div class="content-main">

<?php if ($tab === 'activities'): ?>
    <div class="table-card">
        <?php if (!$rows): ?><div class="empty-state">No activities yet. Add your first one!</div><?php else: ?>
        <table class="data-table">
            <tr><th></th><th>Activity</th><th>Subject</th><th>Date / Time</th><th>Priority</th><th></th></tr>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td>
                    <form method="POST" action="actions/school_actions.php" style="display:inline;">
                        <input type="hidden" name="action" value="toggle_activity">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="tab" value="activities">
                        <input type="hidden" name="status" value="<?= $r['status']==='Done'?'Pending':'Done' ?>">
                        <button type="submit" class="task-check <?= $r['status']==='Done'?'done':'' ?>" style="border:2px solid var(--border);"><?= $r['status']==='Done'?'✓':'' ?></button>
                    </form>
                </td>
                <td class="<?= $r['status']==='Done'?'task-title done':'' ?>"><?= htmlspecialchars($r['activity_name']) ?><?php if($r['description']):?><div class="list-item-meta"><?= htmlspecialchars(mb_strimwidth($r['description'],0,50,'…')) ?></div><?php endif; ?></td>
                <td><?= htmlspecialchars($r['subject']) ?></td>
                <td><?= $r['activity_date']?date('M j, Y', strtotime($r['activity_date'])):'—' ?><?= $r['activity_time']?' · '.date('g:i A', strtotime($r['activity_time'])):'' ?></td>
                <td><span class="badge badge-<?= strtolower($r['priority']) ?>"><?= $r['priority'] ?></span></td>
                <td><a href="javascript:void(0)" onclick="openModal('edit<?= $r['id'] ?>')" style="color:var(--purple);font-size:0.85rem;font-weight:600;margin-right:10px;">Edit</a><a href="actions/school_actions.php?action=delete_activity&id=<?= $r['id'] ?>&tab=activities" class="confirm-delete" style="color:var(--red);font-size:0.9rem;">✕</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

    <?php foreach ($rows as $r): ?>
    <div class="modal-overlay" id="edit<?= $r['id'] ?>">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('edit<?= $r['id'] ?>')">&times;</span>
            <h3>Edit Activity</h3>
            <form method="POST" action="actions/school_actions.php">
                <input type="hidden" name="action" value="update_activity">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="tab" value="activities">
                <div class="form-group"><label>Activity Name</label><input type="text" name="activity_name" value="<?= htmlspecialchars($r['activity_name']) ?>" required></div>
                <div class="form-group"><label>Subject</label><input type="text" name="subject" value="<?= htmlspecialchars($r['subject']) ?>"></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="3"><?= htmlspecialchars($r['description']) ?></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label>Date</label><input type="date" name="activity_date" value="<?= $r['activity_date'] ?>"></div>
                    <div class="form-group"><label>Time</label><input type="time" name="activity_time" value="<?= $r['activity_time'] ?>"></div>
                </div>
                <div class="form-group"><label>Priority</label>
                    <select name="priority">
                        <?php foreach (['Low','Medium','High'] as $opt): ?><option <?= $r['priority']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="status" value="<?= htmlspecialchars($r['status']) ?>">
                <button type="submit" class="btn btn-block">Save Changes</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            <h3>Add Activity</h3>
            <form method="POST" action="actions/school_actions.php">
                <input type="hidden" name="action" value="add_activity">
                <input type="hidden" name="tab" value="activities">
                <div class="form-group"><label>Activity Name</label><input type="text" name="activity_name" required></div>
                <div class="form-group"><label>Subject</label><input type="text" name="subject"></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label>Date</label><input type="date" name="activity_date"></div>
                    <div class="form-group"><label>Time</label><input type="time" name="activity_time"></div>
                </div>
                <div class="form-group"><label>Priority</label>
                    <select name="priority"><option>Low</option><option selected>Medium</option><option>High</option></select>
                </div>
                <input type="hidden" name="status" value="Pending">
                <button type="submit" class="btn btn-block">Save Activity</button>
            </form>
        </div>
    </div>

<?php elseif ($tab === 'assignments'): ?>
    <div class="item-list">
    <?php if (!$rows): ?><div class="empty-state">No assignments/projects yet.</div><?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <div class="item-card">
            <h4><?= htmlspecialchars($r['assignment_name']) ?></h4>
            <?php if ($r['subject']): ?><div class="meta">Subject: <?= htmlspecialchars($r['subject']) ?></div><?php endif; ?>
            <?php if ($r['teacher']): ?><div class="meta">Teacher: <?= htmlspecialchars($r['teacher']) ?></div><?php endif; ?>
            <?php if ($r['deadline']): ?><div class="meta">📅 Deadline: <?= date('M j, Y', strtotime($r['deadline'])) ?></div><?php endif; ?>
            <?php if ($r['description']): ?><div class="desc"><?= nl2br(htmlspecialchars($r['description'])) ?></div><?php endif; ?>
            <?php if ($r['notes']): ?><div class="desc"><em>Notes: <?= nl2br(htmlspecialchars($r['notes'])) ?></em></div><?php endif; ?>
            <span class="badge badge-<?= strtolower($r['priority']) ?>"><?= $r['priority'] ?></span>
            <span class="badge badge-<?= strtolower(str_replace(' ','',$r['status'])) ?>"><?= $r['status'] ?></span>
            <div class="item-actions">
                <form method="POST" action="actions/school_actions.php">
                    <input type="hidden" name="action" value="update_assignment_status">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="tab" value="assignments">
                    <select name="status" class="auto-submit-checkbox" style="font-size:0.75rem;padding:4px 8px;">
                        <option value="Pending" <?= $r['status']==='Pending'?'selected':'' ?>>Pending</option>
                        <option value="In Progress" <?= $r['status']==='In Progress'?'selected':'' ?>>In Progress</option>
                        <option value="Done" <?= $r['status']==='Done'?'selected':'' ?>>Done</option>
                    </select>
                </form>
                <a href="actions/school_actions.php?action=delete_assignment&id=<?= $r['id'] ?>&tab=assignments" class="btn btn-small btn-danger confirm-delete">Delete</a>
                <button type="button" class="btn btn-small btn-secondary" onclick="openModal('edit<?= $r['id'] ?>')">Edit</button>
            </div>
        </div>
        <div class="modal-overlay" id="edit<?= $r['id'] ?>">
            <div class="modal-box">
                <span class="close-modal" onclick="closeModal('edit<?= $r['id'] ?>')">&times;</span>
                <h3>Edit Assignment / Project</h3>
                <form method="POST" action="actions/school_actions.php">
                    <input type="hidden" name="action" value="update_assignment">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="tab" value="assignments">
                    <div class="form-group"><label>Assignment Name</label><input type="text" name="assignment_name" value="<?= htmlspecialchars($r['assignment_name']) ?>" required></div>
                    <div class="form-row">
                        <div class="form-group"><label>Subject</label><input type="text" name="subject" value="<?= htmlspecialchars($r['subject']) ?>"></div>
                        <div class="form-group"><label>Teacher</label><input type="text" name="teacher" value="<?= htmlspecialchars($r['teacher']) ?>"></div>
                    </div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="3"><?= htmlspecialchars($r['description']) ?></textarea></div>
                    <div class="form-group"><label>Deadline</label><input type="date" name="deadline" value="<?= $r['deadline'] ?>"></div>
                    <div class="form-row">
                        <div class="form-group"><label>Priority</label>
                            <select name="priority"><?php foreach (['Low','Medium','High'] as $opt): ?><option <?= $r['priority']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                        </div>
                        <div class="form-group"><label>Status</label>
                            <select name="status"><?php foreach (['Pending','In Progress','Done'] as $opt): ?><option <?= $r['status']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                        </div>
                    </div>
                    <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"><?= htmlspecialchars($r['notes']) ?></textarea></div>
                    <button type="submit" class="btn btn-block">Save Changes</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            <h3>Add Assignment / Project</h3>
            <form method="POST" action="actions/school_actions.php">
                <input type="hidden" name="action" value="add_assignment">
                <input type="hidden" name="tab" value="assignments">
                <div class="form-group"><label>Assignment Name</label><input type="text" name="assignment_name" required></div>
                <div class="form-row">
                    <div class="form-group"><label>Subject</label><input type="text" name="subject"></div>
                    <div class="form-group"><label>Teacher</label><input type="text" name="teacher"></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
                <div class="form-group"><label>Deadline</label><input type="date" name="deadline"></div>
                <div class="form-row">
                    <div class="form-group"><label>Priority</label>
                        <select name="priority"><option>Low</option><option selected>Medium</option><option>High</option></select>
                    </div>
                    <div class="form-group"><label>Status</label>
                        <select name="status"><option selected>Pending</option><option>In Progress</option><option>Done</option></select>
                    </div>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
                <button type="submit" class="btn btn-block">Save Assignment</button>
            </form>
        </div>
    </div>

<?php elseif ($tab === 'events'): ?>
    <div class="item-list">
    <?php if (!$rows): ?><div class="empty-state">No events yet.</div><?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <div class="item-card">
            <h4><?= htmlspecialchars($r['event_name']) ?></h4>
            <?php if ($r['venue']): ?><div class="meta">📍 <?= htmlspecialchars($r['venue']) ?></div><?php endif; ?>
            <?php if ($r['event_date']): ?><div class="meta">📅 <?= date('M j, Y', strtotime($r['event_date'])) ?><?= $r['event_time'] ? ' • ' . date('g:i A', strtotime($r['event_time'])) : '' ?></div><?php endif; ?>
            <?php if ($r['organizer']): ?><div class="meta">Organizer: <?= htmlspecialchars($r['organizer']) ?></div><?php endif; ?>
            <?php if ($r['description']): ?><div class="desc"><?= nl2br(htmlspecialchars($r['description'])) ?></div><?php endif; ?>
            <span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span>
            <div class="item-actions">
                <a href="actions/school_actions.php?action=delete_event&id=<?= $r['id'] ?>&tab=events" class="btn btn-small btn-danger confirm-delete">Delete</a>
                <button type="button" class="btn btn-small btn-secondary" onclick="openModal('edit<?= $r['id'] ?>')">Edit</button>
            </div>
        </div>
        <div class="modal-overlay" id="edit<?= $r['id'] ?>">
            <div class="modal-box">
                <span class="close-modal" onclick="closeModal('edit<?= $r['id'] ?>')">&times;</span>
                <h3>Edit Event</h3>
                <form method="POST" action="actions/school_actions.php">
                    <input type="hidden" name="action" value="update_event">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="tab" value="events">
                    <div class="form-group"><label>Event Name</label><input type="text" name="event_name" value="<?= htmlspecialchars($r['event_name']) ?>" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="2"><?= htmlspecialchars($r['description']) ?></textarea></div>
                    <div class="form-group"><label>Venue</label><input type="text" name="venue" value="<?= htmlspecialchars($r['venue']) ?>"></div>
                    <div class="form-row">
                        <div class="form-group"><label>Date</label><input type="date" name="event_date" value="<?= $r['event_date'] ?>"></div>
                        <div class="form-group"><label>Time</label><input type="time" name="event_time" value="<?= $r['event_time'] ?>"></div>
                    </div>
                    <div class="form-group"><label>Organizer</label><input type="text" name="organizer" value="<?= htmlspecialchars($r['organizer']) ?>"></div>
                    <div class="form-group"><label>Status</label>
                        <select name="status"><?php foreach (['Upcoming','Done','Cancelled'] as $opt): ?><option <?= $r['status']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                    </div>
                    <button type="submit" class="btn btn-block">Save Changes</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            <h3>Add Event</h3>
            <form method="POST" action="actions/school_actions.php">
                <input type="hidden" name="action" value="add_event">
                <input type="hidden" name="tab" value="events">
                <div class="form-group"><label>Event Name</label><input type="text" name="event_name" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
                <div class="form-group"><label>Venue</label><input type="text" name="venue"></div>
                <div class="form-row">
                    <div class="form-group"><label>Date</label><input type="date" name="event_date"></div>
                    <div class="form-group"><label>Time</label><input type="time" name="event_time"></div>
                </div>
                <div class="form-group"><label>Organizer</label><input type="text" name="organizer"></div>
                <div class="form-group"><label>Status</label>
                    <select name="status"><option selected>Upcoming</option><option>Done</option><option>Cancelled</option></select>
                </div>
                <button type="submit" class="btn btn-block">Save Event</button>
            </form>
        </div>
    </div>

<?php elseif ($tab === 'ganaps'): ?>
    <div class="item-list">
    <?php if (!$rows): ?><div class="empty-state">No ganaps yet. Add a school appointment or errand!</div><?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <div class="item-card">
            <h4><?= $r['status']==='Done'?'✔':'☐' ?> <?= htmlspecialchars($r['ganap_name']) ?></h4>
            <?php if ($r['office']): ?><div class="meta">🏢 <?= htmlspecialchars($r['office']) ?></div><?php endif; ?>
            <?php if ($r['ganap_date']): ?><div class="meta">📅 <?= date('M j, Y', strtotime($r['ganap_date'])) ?><?= $r['ganap_time'] ? ' • ' . date('g:i A', strtotime($r['ganap_time'])) : '' ?></div><?php endif; ?>
            <?php if ($r['purpose']): ?><div class="desc"><?= nl2br(htmlspecialchars($r['purpose'])) ?></div><?php endif; ?>
            <span class="badge badge-<?= strtolower($r['priority']) ?>"><?= $r['priority'] ?></span>
            <span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span>
            <div class="item-actions">
                <form method="POST" action="actions/school_actions.php">
                    <input type="hidden" name="action" value="toggle_ganap">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="tab" value="ganaps">
                    <select name="status" class="auto-submit-checkbox" style="font-size:0.75rem;padding:4px 8px;">
                        <option value="Pending" <?= $r['status']==='Pending'?'selected':'' ?>>Pending</option>
                        <option value="Done" <?= $r['status']==='Done'?'selected':'' ?>>Done</option>
                        <option value="Cancelled" <?= $r['status']==='Cancelled'?'selected':'' ?>>Cancelled</option>
                    </select>
                </form>
                <button type="button" class="btn btn-small btn-secondary" onclick="openModal('edit<?= $r['id'] ?>')">Edit</button>
                <a href="actions/school_actions.php?action=delete_ganap&id=<?= $r['id'] ?>&tab=ganaps" class="btn btn-small btn-danger confirm-delete">Delete</a>
            </div>
        </div>
        <div class="modal-overlay" id="edit<?= $r['id'] ?>">
            <div class="modal-box">
                <span class="close-modal" onclick="closeModal('edit<?= $r['id'] ?>')">&times;</span>
                <h3>Edit Ganap</h3>
                <form method="POST" action="actions/school_actions.php">
                    <input type="hidden" name="action" value="update_ganap">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="tab" value="ganaps">
                    <div class="form-group"><label>Appointment / Errand Name</label><input type="text" name="ganap_name" value="<?= htmlspecialchars($r['ganap_name']) ?>" required placeholder="e.g. Process tuition payment"></div>
                    <div class="form-group"><label>Purpose / Notes</label><textarea name="purpose" rows="2"><?= htmlspecialchars($r['purpose']) ?></textarea></div>
                    <div class="form-group"><label>Office / Department</label><input type="text" name="office" value="<?= htmlspecialchars($r['office']) ?>" placeholder="e.g. Registrar, Cashier, Guidance Office"></div>
                    <div class="form-row">
                        <div class="form-group"><label>Date</label><input type="date" name="ganap_date" value="<?= $r['ganap_date'] ?>"></div>
                        <div class="form-group"><label>Time</label><input type="time" name="ganap_time" value="<?= $r['ganap_time'] ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Priority</label>
                            <select name="priority"><?php foreach (['Low','Medium','High'] as $opt): ?><option <?= $r['priority']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                        </div>
                        <div class="form-group"><label>Status</label>
                            <select name="status"><?php foreach (['Pending','Done','Cancelled'] as $opt): ?><option <?= $r['status']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-block">Save Changes</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            <h3>Add Ganap</h3>
            <form method="POST" action="actions/school_actions.php">
                <input type="hidden" name="action" value="add_ganap">
                <input type="hidden" name="tab" value="ganaps">
                <div class="form-group"><label>Appointment / Errand Name</label><input type="text" name="ganap_name" required placeholder="e.g. Process tuition payment"></div>
                <div class="form-group"><label>Purpose / Notes</label><textarea name="purpose" rows="2"></textarea></div>
                <div class="form-group"><label>Office / Department</label><input type="text" name="office" placeholder="e.g. Registrar, Cashier, Guidance Office"></div>
                <div class="form-row">
                    <div class="form-group"><label>Date</label><input type="date" name="ganap_date"></div>
                    <div class="form-group"><label>Time</label><input type="time" name="ganap_time"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Priority</label>
                        <select name="priority"><option>Low</option><option selected>Medium</option><option>High</option></select>
                    </div>
                    <div class="form-group"><label>Status</label>
                        <select name="status"><option selected>Pending</option><option>Done</option><option>Cancelled</option></select>
                    </div>
                </div>
                <button type="submit" class="btn btn-block">Save Ganap</button>
            </form>
        </div>
    </div>

<?php else: /* schedule */ ?>

    <?php if (!$semesters): ?>
        <div class="empty-state">No semesters yet. Add one to start building your class schedule!</div>
    <?php else: ?>
        <div class="toolbar" style="margin-bottom:14px;">
            <form method="GET" style="display:flex;align-items:center;gap:8px;">
                <input type="hidden" name="tab" value="schedule">
                <label style="font-size:0.85rem;font-weight:600;">Semester:</label>
                <select name="semester_id" class="auto-submit-checkbox" style="padding:8px 12px;border-radius:9px;border:1px solid var(--border);background:var(--card-bg);color:var(--text);">
                    <?php foreach ($semesters as $sm): ?>
                        <option value="<?= $sm['id'] ?>" <?= $sm['id']==$activeSemId?'selected':'' ?>><?= htmlspecialchars($sm['semester_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div style="display:flex;gap:8px;">
                <button type="button" class="btn btn-small btn-secondary" onclick="openModal('addSemesterModal')">+ New Semester</button>
                <?php if ($activeSemId): ?>
                    <a href="actions/schedule_actions.php?action=delete_semester&semester_id=<?= $activeSemId ?>" class="btn btn-small btn-danger confirm-delete">Delete Semester</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($activeSemId): ?>

    <?php if (!$classes): ?>
        <div class="empty-state">No classes added yet for this semester.</div>
    <?php else: ?>
        <div class="card" style="margin-bottom:16px;display:inline-block;padding:12px 20px;">
            <strong>Total Units: <?= rtrim(rtrim(number_format($totalUnits,1),'0'),'.') ?></strong>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <?php foreach ($classesByDay as $dayName => $dayClasses): ?>
            <div class="table-card">
                <table class="data-table">
                    <tr><th colspan="5" style="text-align:center;font-size:0.8rem;"><?= strtoupper($dayName) ?></th></tr>
                    <?php foreach ($dayClasses as $c): ?>
                    <tr>
                        <td style="white-space:nowrap;color:var(--muted);"><?= htmlspecialchars($c['code']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($c['subject']) ?></strong>
                            <?php if ($c['class_type'] === 'LAB'): ?><span class="badge" style="background:var(--blue-light);color:var(--blue);margin-left:4px;">LAB</span><?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;">Rm <?= htmlspecialchars($c['room']) ?></td>
                        <td style="white-space:nowrap;"><?= htmlspecialchars($c['schedule_time']) ?></td>
                        <td style="white-space:nowrap;">
                            <a href="javascript:void(0)" onclick="openModal('editClass<?= $c['id'] ?>')" style="color:var(--purple);font-size:0.8rem;font-weight:600;margin-right:6px;">Edit</a>
                            <a href="actions/schedule_actions.php?action=delete_class&id=<?= $c['id'] ?>&semester_id=<?= $activeSemId ?>" class="confirm-delete" style="color:var(--red);font-size:0.85rem;">✕</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php foreach ($classes as $c): ?>
    <div class="modal-overlay" id="editClass<?= $c['id'] ?>">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('editClass<?= $c['id'] ?>')">&times;</span>
            <h3>Edit Class</h3>
            <form method="POST" action="actions/schedule_actions.php">
                <input type="hidden" name="action" value="update_class">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <input type="hidden" name="semester_id" value="<?= $activeSemId ?>">
                <div class="form-row">
                    <div class="form-group"><label>Code</label><input type="text" name="code" value="<?= htmlspecialchars($c['code']) ?>"></div>
                    <div class="form-group"><label>Remarks</label><input type="text" name="remarks" value="<?= htmlspecialchars($c['remarks']) ?>" placeholder="e.g. ONLINE/OFFLINE"></div>
                </div>
                <div class="form-group"><label>Subject</label><input type="text" name="subject" value="<?= htmlspecialchars($c['subject']) ?>" required></div>
                <div class="form-row">
                    <div class="form-group"><label>Type</label>
                        <select name="class_type"><option <?= $c['class_type']==='LEC'?'selected':'' ?>>LEC</option><option <?= $c['class_type']==='LAB'?'selected':'' ?>>LAB</option></select>
                    </div>
                    <div class="form-group"><label>Units</label><input type="number" step="0.5" name="units" value="<?= $c['units'] ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Schedule (time)</label><input type="text" name="schedule_time" value="<?= htmlspecialchars($c['schedule_time']) ?>" placeholder="e.g. 8:31 - 9:31 PM"></div>
                    <div class="form-group"><label>Days</label><input type="text" name="days" value="<?= htmlspecialchars($c['days']) ?>" placeholder="e.g. MWF"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Room</label><input type="text" name="room" value="<?= htmlspecialchars($c['room']) ?>"></div>
                    <div class="form-group"><label>Section</label><input type="text" name="section" value="<?= htmlspecialchars($c['section']) ?>"></div>
                </div>
                <div class="form-group"><label>Details</label><input type="text" name="details" value="<?= htmlspecialchars($c['details']) ?>" placeholder="e.g. CAPSTONE PROJECT 1"></div>
                <button type="submit" class="btn btn-block">Save Changes</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            <h3>Add Class</h3>
            <form method="POST" action="actions/schedule_actions.php">
                <input type="hidden" name="action" value="add_class">
                <input type="hidden" name="semester_id" value="<?= $activeSemId ?>">
                <div class="form-row">
                    <div class="form-group"><label>Code</label><input type="text" name="code" placeholder="e.g. 08474"></div>
                    <div class="form-group"><label>Remarks</label><input type="text" name="remarks" placeholder="e.g. ONLINE/OFFLINE"></div>
                </div>
                <div class="form-group"><label>Subject</label><input type="text" name="subject" required placeholder="e.g. IT-CPSTONE30"></div>
                <div class="form-row">
                    <div class="form-group"><label>Type</label>
                        <select name="class_type"><option selected>LEC</option><option>LAB</option></select>
                    </div>
                    <div class="form-group"><label>Units</label><input type="number" step="0.5" name="units" placeholder="e.g. 3"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Schedule (time)</label><input type="text" name="schedule_time" placeholder="e.g. 8:31 - 9:31 PM"></div>
                    <div class="form-group"><label>Days</label><input type="text" name="days" placeholder="e.g. MWF"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Room</label><input type="text" name="room" placeholder="e.g. 538"></div>
                    <div class="form-group"><label>Section</label><input type="text" name="section" placeholder="e.g. BSIT 4O"></div>
                </div>
                <div class="form-group"><label>Details</label><input type="text" name="details" placeholder="e.g. CAPSTONE PROJECT 1"></div>
                <button type="submit" class="btn btn-block">Save Class</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="modal-overlay" id="addSemesterModal">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('addSemesterModal')">&times;</span>
            <h3>New Semester</h3>
            <form method="POST" action="actions/schedule_actions.php">
                <input type="hidden" name="action" value="add_semester">
                <div class="form-group"><label>Semester Name</label><input type="text" name="semester_name" required placeholder="e.g. 1st Sem, S.Y. 2025-2026"></div>
                <button type="submit" class="btn btn-block">Create Semester</button>
            </form>
        </div>
    </div>
<?php endif; ?>

</div>
<div class="content-side">
    <div class="card">
        <div class="card-header"><h3>⏰ Upcoming Deadlines</h3></div>
        <?php if ($sideDeadlines): foreach ($sideDeadlines as $d):
            $days = max(0, (int)((strtotime($d['d']) - strtotime($today)) / 86400)); ?>
            <div class="list-item">
                <div class="list-item-top"><span class="list-item-title"><?= htmlspecialchars($d['name']) ?></span><span class="days-left"><?= $days ?>d left</span></div>
                <div class="list-item-meta"><?= date('M j, Y', strtotime($d['d'])) ?></div>
            </div>
        <?php endforeach; else: ?><div class="empty-state">No upcoming deadlines.</div><?php endif; ?>
    </div>
    <div class="sidebar-tip" style="margin-top:0;">
        <span class="tip-icon">💡</span>
        <strong>Study Tip</strong>
        <div class="tip-sub">Break tasks into smaller steps and celebrate every progress!</div>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>