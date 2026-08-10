<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Dashboard";
$today = date('Y-m-d');

// ---------- STAT CARDS ----------
$stmt = $pdo->prepare("SELECT
    (SELECT COUNT(*) FROM school_activities WHERE user_id=? AND activity_date=?) +
    (SELECT COUNT(*) FROM school_assignments WHERE user_id=? AND deadline=?) AS total_today,
    (SELECT COUNT(*) FROM school_activities WHERE user_id=? AND activity_date=? AND status='Done') +
    (SELECT COUNT(*) FROM school_assignments WHERE user_id=? AND deadline=? AND status='Done') AS done_today");
$stmt->execute([$current_user_id,$today,$current_user_id,$today,$current_user_id,$today,$current_user_id,$today]);
$taskStats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT
    (SELECT COUNT(*) FROM school_assignments WHERE user_id=? AND deadline BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY) AND status!='Done') +
    (SELECT COUNT(*) FROM research_deadlines WHERE user_id=? AND deadline_date BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY) AND status!='Done') AS cnt");
$stmt->execute([$current_user_id,$today,$today,$current_user_id,$today,$today]);
$deadlineCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM personal_projects WHERE user_id=? AND status='In Progress'");
$stmt->execute([$current_user_id]);
$activeProjects = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM skills WHERE user_id=? AND status='Learning'");
$stmt->execute([$current_user_id]);
$learningSkills = $stmt->fetchColumn();

// ---------- TODAY'S TASKS ----------
$stmt = $pdo->prepare("
    SELECT activity_name AS name, status, activity_time AS t, 'School' AS tag, id, 'activity' AS src FROM school_activities WHERE user_id=? AND activity_date=?
    UNION ALL
    SELECT assignment_name AS name, status, NULL AS t, 'School' AS tag, id, 'assignment' AS src FROM school_assignments WHERE user_id=? AND deadline=?
    ORDER BY t IS NULL, t ASC LIMIT 8");
$stmt->execute([$current_user_id, $today, $current_user_id, $today]);
$todays_tasks = $stmt->fetchAll();

// ---------- PROGRESS OVERVIEW ----------
function pct($done, $total) { return $total > 0 ? round(($done / $total) * 100) : 0; }

$stmt = $pdo->prepare("SELECT COUNT(*) t, SUM(status='Done') d FROM school_activities WHERE user_id=?"); $stmt->execute([$current_user_id]); $sa = $stmt->fetch();
$stmt = $pdo->prepare("SELECT COUNT(*) t, SUM(status='Done') d FROM school_assignments WHERE user_id=?"); $stmt->execute([$current_user_id]); $sb = $stmt->fetch();
$schoolPct = pct(($sa['d']?:0)+($sb['d']?:0), ($sa['t']?:0)+($sb['t']?:0));

$stmt = $pdo->prepare("SELECT AVG(progress) a FROM research_activities WHERE user_id=?"); $stmt->execute([$current_user_id]); $researchPct = round($stmt->fetchColumn() ?: 0);

$stmt = $pdo->prepare("SELECT AVG(progress) a FROM personal_projects WHERE user_id=?"); $stmt->execute([$current_user_id]); $projectsPct = round($stmt->fetchColumn() ?: 0);

$stmt = $pdo->prepare("SELECT AVG(progress) a FROM skills WHERE user_id=?"); $stmt->execute([$current_user_id]); $learningPct = round($stmt->fetchColumn() ?: 0);

// ---------- UPCOMING DEADLINES ----------
$stmt = $pdo->prepare("
    SELECT assignment_name AS name, deadline AS due_date FROM school_assignments WHERE user_id=? AND deadline >= ? AND status!='Done'
    UNION ALL
    SELECT deadline_name AS name, deadline_date AS due_date FROM research_deadlines WHERE user_id=? AND deadline_date >= ? AND status!='Done'
    ORDER BY due_date ASC LIMIT 4");
$stmt->execute([$current_user_id, $today, $current_user_id, $today]);
$upcoming_deadlines = $stmt->fetchAll();

// ---------- CURRENT PROJECTS ----------
$stmt = $pdo->prepare("SELECT id, project_name, status, progress FROM personal_projects WHERE user_id=? AND status != 'Completed' ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$current_user_id]);
$current_projects = $stmt->fetchAll();

// ---------- LEARNING PROGRESS ----------
$stmt = $pdo->prepare("SELECT skill_name, progress, level FROM skills WHERE user_id=? ORDER BY progress DESC LIMIT 4");
$stmt->execute([$current_user_id]);
$learning_progress = $stmt->fetchAll();

// ---------- UPCOMING EVENTS (sidebar) ----------
$stmt = $pdo->prepare("
    SELECT event_name AS name, event_date AS d, event_time AS t, venue FROM school_events WHERE user_id=? AND event_date >= ? AND status='Upcoming'
    ORDER BY event_date ASC LIMIT 3");
$stmt->execute([$current_user_id, $today]);
$events = $stmt->fetchAll();

// ---------- MINI CALENDAR (sidebar) ----------
$m = (int)date('n'); $y = (int)date('Y');
$daysInMonth = (int)date('t');
$startWeekday = (int)date('w', mktime(0,0,0,$m,1,$y));
$markDays = [];
$rangeStart = date('Y-m-01'); $rangeEnd = date('Y-m-t');
$stmt = $pdo->prepare("SELECT DISTINCT DAY(activity_date) d FROM school_activities WHERE user_id=? AND activity_date BETWEEN ? AND ?");
$stmt->execute([$current_user_id, $rangeStart, $rangeEnd]);
foreach ($stmt->fetchAll() as $r) $markDays[] = (int)$r['d'];
$stmt = $pdo->prepare("SELECT DISTINCT DAY(deadline) d FROM school_assignments WHERE user_id=? AND deadline BETWEEN ? AND ?");
$stmt->execute([$current_user_id, $rangeStart, $rangeEnd]);
foreach ($stmt->fetchAll() as $r) $markDays[] = (int)$r['d'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="page-header">
    <h1>Good day, <?= htmlspecialchars($current_user_name) ?>! 🌿</h1>
    <div class="sub">Let's make today productive and amazing.</div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon purple">📋</div>
        <div><div class="stat-num"><?= (int)$taskStats['total_today'] ?></div><div class="stat-label">Tasks Today</div><div class="stat-extra"><?= (int)$taskStats['done_today'] ?> done</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">📅</div>
        <div><div class="stat-num"><?= (int)$deadlineCount ?></div><div class="stat-label">Upcoming Deadlines</div><div class="stat-extra">This week</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">💼</div>
        <div><div class="stat-num"><?= (int)$activeProjects ?></div><div class="stat-label">Active Projects</div><div class="stat-extra">In progress</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📖</div>
        <div><div class="stat-num"><?= (int)$learningSkills ?></div><div class="stat-label">Skills Learning</div><div class="stat-extra">Keep going!</div></div>
    </div>
</div>

<div class="content-wrap">
<div class="content-main">

    <div class="widget-grid" style="grid-template-columns:1.1fr 1fr;">
        <div class="card">
            <div class="card-header"><h3>✅ Today's Tasks</h3><a href="school_works.php" class="view-all">View All</a></div>
            <?php if ($todays_tasks): foreach ($todays_tasks as $t): ?>
                <div class="task-row">
                    <span class="task-check <?= $t['status']==='Done'?'done':'' ?>"><?= $t['status']==='Done'?'✓':'' ?></span>
                    <span class="task-title <?= $t['status']==='Done'?'done':'' ?>"><?= htmlspecialchars($t['name']) ?></span>
                    <span class="task-tag tag-school"><?= $t['tag'] ?></span>
                    <?php if ($t['t']): ?><span class="task-time"><?= date('g:i A', strtotime($t['t'])) ?></span><?php endif; ?>
                </div>
            <?php endforeach; else: ?><div class="empty-state">Nothing due today. 🎉</div><?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"><h3>📊 Progress Overview</h3></div>
            <div class="overview-row"><div class="ic-badge" style="background:var(--purple-light);color:var(--purple);">🎓</div><div class="label">School Works</div><div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $schoolPct ?>%;background:var(--purple);"></div></div><div class="pct"><?= $schoolPct ?>%</div></div>
            <div class="overview-row"><div class="ic-badge" style="background:var(--green-light);color:var(--green);">🔬</div><div class="label">Research Works</div><div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $researchPct ?>%;background:var(--green);"></div></div><div class="pct"><?= $researchPct ?>%</div></div>
            <div class="overview-row"><div class="ic-badge" style="background:var(--blue-light);color:var(--blue);">💼</div><div class="label">Projects</div><div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $projectsPct ?>%;background:var(--blue);"></div></div><div class="pct"><?= $projectsPct ?>%</div></div>
            <div class="overview-row"><div class="ic-badge" style="background:var(--orange-light);color:var(--orange);">📖</div><div class="label">Learning Studies</div><div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $learningPct ?>%;background:var(--orange);"></div></div><div class="pct"><?= $learningPct ?>%</div></div>
        </div>
    </div>

    <div class="widget-grid" style="grid-template-columns:1fr 1fr 1fr;">
        <div class="card">
            <div class="card-header"><h3>⏰ Upcoming Deadlines</h3><a href="calendar.php" class="view-all">View All</a></div>
            <?php if ($upcoming_deadlines): foreach ($upcoming_deadlines as $d):
                $days = max(0, (int)((strtotime($d['due_date']) - strtotime($today)) / 86400)); ?>
                <div class="list-item">
                    <div class="list-item-top">
                        <span class="list-item-title"><?= htmlspecialchars($d['name']) ?></span>
                        <span class="days-left"><?= $days ?> day<?= $days==1?'':'s' ?></span>
                    </div>
                    <div class="list-item-meta"><?= date('M j, Y', strtotime($d['due_date'])) ?></div>
                </div>
            <?php endforeach; else: ?><div class="empty-state">No upcoming deadlines.</div><?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"><h3>💼 Current Projects</h3><a href="personal_projects.php" class="view-all">View All</a></div>
            <?php if ($current_projects): foreach ($current_projects as $p): ?>
                <div class="list-item">
                    <div class="list-item-top">
                        <span class="list-item-title"><a href="project_view.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['project_name']) ?></a></span>
                        <span class="badge badge-<?= strtolower(str_replace(' ','',$p['status'])) ?>"><?= $p['status'] ?></span>
                    </div>
                    <div class="progress-bar-wrap" style="margin-top:6px;"><div class="progress-bar-fill" style="width:<?= (int)$p['progress'] ?>%;background:var(--blue);"></div></div>
                </div>
            <?php endforeach; else: ?><div class="empty-state">No active projects.</div><?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"><h3>📖 Learning Progress</h3><a href="learning.php" class="view-all">View All</a></div>
            <?php if ($learning_progress): foreach ($learning_progress as $s): ?>
                <div class="list-item">
                    <div class="list-item-top">
                        <span class="list-item-title"><?= htmlspecialchars($s['skill_name']) ?></span>
                        <span class="days-left" style="background:var(--orange-light);color:var(--orange);"><?= (int)$s['progress'] ?>%</span>
                    </div>
                    <div class="list-item-meta"><?= htmlspecialchars($s['level']) ?></div>
                </div>
            <?php endforeach; else: ?><div class="empty-state">No skills tracked yet.</div><?php endif; ?>
        </div>
    </div>

</div>

<div class="content-side">
    <div class="card">
        <div class="card-header"><h3>🎉 Upcoming Events</h3><a href="calendar.php" class="view-all">View Calendar</a></div>
        <?php if ($events): foreach ($events as $e): ?>
            <div class="event-card">
                <div class="event-date-box">
                    <div class="mon"><?= date('M', strtotime($e['d'])) ?></div>
                    <div class="day"><?= date('j', strtotime($e['d'])) ?></div>
                </div>
                <div>
                    <div class="event-title"><?= htmlspecialchars($e['name']) ?></div>
                    <div class="event-meta"><?= $e['t'] ? date('g:i A', strtotime($e['t'])) : '' ?><?= $e['venue'] ? ' · ' . htmlspecialchars($e['venue']) : '' ?></div>
                </div>
            </div>
        <?php endforeach; else: ?><div class="empty-state">No upcoming events.</div><?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header"><h3>📅 Calendar</h3><span style="font-size:0.8rem;color:var(--muted);"><?= date('F Y') ?></span></div>
        <div class="mini-cal-grid">
            <?php foreach (['S','M','T','W','T','F','S'] as $d): ?><div class="dow"><?= $d ?></div><?php endforeach; ?>
            <?php for ($i=0;$i<$startWeekday;$i++): ?><div class="day muted"></div><?php endfor; ?>
            <?php for ($d=1;$d<=$daysInMonth;$d++):
                $isToday = $d == (int)date('j');
            ?>
                <div class="day <?= $isToday?'today':'' ?>"><?= $d ?><?php if (in_array($d,$markDays) && !$isToday): ?><span class="dot"></span><?php endif; ?></div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="quote-card">
        <p>"Small habits today,<br>big changes tomorrow."</p>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
