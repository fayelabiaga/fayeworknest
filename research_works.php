<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Research Works";
$tab = $_GET['tab'] ?? 'activities';
if (!in_array($tab, ['activities','deadlines','documents','progress'])) $tab = 'activities';

if ($tab === 'activities') {
    $stmt = $pdo->prepare("SELECT * FROM research_activities WHERE user_id=? ORDER BY deadline ASC, id DESC");
    $stmt->execute([$current_user_id]); $rows = $stmt->fetchAll();
} elseif ($tab === 'deadlines') {
    $stmt = $pdo->prepare("SELECT * FROM research_deadlines WHERE user_id=? ORDER BY deadline_date ASC, id DESC");
    $stmt->execute([$current_user_id]); $rows = $stmt->fetchAll();
} elseif ($tab === 'documents') {
    $stmt = $pdo->prepare("SELECT * FROM research_documents WHERE user_id=? ORDER BY uploaded_at DESC");
    $stmt->execute([$current_user_id]); $rows = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM research_progress WHERE user_id=? ORDER BY id ASC");
    $stmt->execute([$current_user_id]); $rows = $stmt->fetchAll();
}

$today = date('Y-m-d');

// ---- Stat cards ----
$stmt = $pdo->prepare("SELECT COUNT(*) t, SUM(status='Done') d FROM research_activities WHERE user_id=?");
$stmt->execute([$current_user_id]);
$taskStats = $stmt->fetch();
$tasksTotal = (int)$taskStats['t'];
$tasksDone = (int)$taskStats['d'];
$tasksPending = $tasksTotal - $tasksDone;

$stmt = $pdo->prepare("SELECT COUNT(*) t, SUM(status='Done') d FROM research_deadlines WHERE user_id=? AND deadline_date BETWEEN ? AND DATE_ADD(?, INTERVAL 14 DAY)");
$stmt->execute([$current_user_id, $today, $today]);
$formStats = $stmt->fetch();
$formsDueSoon = (int)$formStats['t'];
$formsDone = (int)$formStats['d'];
$formsPending = $formsDueSoon - $formsDone;

$stmt = $pdo->prepare("SELECT AVG(progress) a FROM research_activities WHERE user_id=?");
$stmt->execute([$current_user_id]);
$overallProgress = round($stmt->fetchColumn() ?: 0);

$stmt = $pdo->prepare("SELECT deadline_name AS name, deadline_date AS d FROM research_deadlines WHERE user_id=? AND deadline_date >= ? AND status!='Done' ORDER BY deadline_date ASC LIMIT 1");
$stmt->execute([$current_user_id, $today]);
$nextDeadline = $stmt->fetch();

// ---- Research Progress (sidebar mini overview) ----
$stmt = $pdo->prepare("SELECT * FROM research_progress WHERE user_id=? ORDER BY id ASC LIMIT 6");
$stmt->execute([$current_user_id]);
$progressStages = $stmt->fetchAll();

// ---- Reminder (nearest upcoming form deadline) ----
$stmt = $pdo->prepare("SELECT deadline_name AS name, deadline_date AS d FROM research_deadlines WHERE user_id=? AND deadline_date >= ? AND status!='Done' ORDER BY deadline_date ASC LIMIT 1");
$stmt->execute([$current_user_id, $today]);
$reminder = $stmt->fetch();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>🔬 Research Works</h1>
            <div class="sub">Manage your research tasks, forms, deadlines, and progress.</div>
        </div>
        <?php if ($tab !== 'progress'): ?>
        <button class="btn" onclick="openModal('addModal')">+ Add <?= $tab==='activities'?'Task':($tab==='deadlines'?'Form':'Document') ?></button>
        <?php endif; ?>
    </div>
</div>

<div class="tabs">
    <a href="?tab=activities" class="tab-link <?= $tab==='activities'?'active':'' ?>">Activities To Do</a>
    <a href="?tab=deadlines" class="tab-link <?= $tab==='deadlines'?'active':'' ?>">Deadlines of Forms</a>
    <a href="?tab=progress" class="tab-link <?= $tab==='progress'?'active':'' ?>">Research Progress</a>
    <a href="?tab=documents" class="tab-link <?= $tab==='documents'?'active':'' ?>">Documents Overview</a>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-icon purple">📋</div><div><div class="stat-num"><?= $tasksTotal ?></div><div class="stat-label">Tasks To Do</div><div class="stat-extra"><?= $tasksPending ?> pending · <?= $tasksDone ?> done</div></div></div>
    <div class="stat-card"><div class="stat-icon green">📄</div><div><div class="stat-num"><?= $formsDueSoon ?></div><div class="stat-label">Forms Due Soon</div><div class="stat-extra"><?= $formsPending ?> pending · <?= $formsDone ?> done</div></div></div>
    <div class="stat-card"><div class="ring ring-lg" data-progress="<?= $overallProgress ?>" data-color="#6c5ce7"><span><?= $overallProgress ?>%</span></div><div><div class="stat-label" style="font-weight:700;color:var(--text);">Overall Progress</div><div class="stat-extra">Keep it up!</div></div></div>
    <div class="stat-card"><div class="stat-icon orange">📅</div><div><div class="stat-label" style="font-weight:700;color:var(--text);">Next Deadline</div><div class="stat-num" style="font-size:1.05rem;"><?= $nextDeadline ? date('M j, Y', strtotime($nextDeadline['d'])) : '—' ?></div><div class="stat-extra"><?= $nextDeadline ? htmlspecialchars($nextDeadline['name']) : 'Nothing upcoming' ?></div></div></div>
</div>

<div class="content-wrap">
<div class="content-main">

<?php if ($tab === 'activities'): ?>
    <div class="table-card">
        <?php if (!$rows): ?><div class="empty-state">No research tasks yet.</div><?php else: ?>
        <table class="data-table">
            <tr><th></th><th>Task</th><th>Description</th><th>Status</th><th>Deadline</th><th>Priority</th><th></th></tr>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td>
                    <form method="POST" action="actions/research_actions.php">
                        <input type="hidden" name="action" value="update_activity_status">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="tab" value="activities">
                        <input type="hidden" name="status" value="<?= $r['status']==='Done'?'Pending':'Done' ?>">
                        <button type="submit" class="task-check <?= $r['status']==='Done'?'done':'' ?>"><?= $r['status']==='Done'?'✓':'' ?></button>
                    </form>
                </td>
                <td class="<?= $r['status']==='Done'?'task-title done':'' ?>"><?= htmlspecialchars($r['activity_name']) ?><?php if($r['assigned_to']):?><div class="list-item-meta">Assigned: <?= htmlspecialchars($r['assigned_to']) ?></div><?php endif; ?></td>
                <td><?= htmlspecialchars(mb_strimwidth($r['description'] ?? '', 0, 60, '…')) ?></td>
                <td>
                    <form method="POST" action="actions/research_actions.php">
                        <input type="hidden" name="action" value="update_activity_status">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="tab" value="activities">
                        <select name="status" class="auto-submit-checkbox" style="font-size:0.78rem;padding:5px 8px;border-radius:8px;border:1px solid var(--border);background:var(--card-bg);color:var(--text);">
                            <option value="Pending" <?= $r['status']==='Pending'?'selected':'' ?>>Pending</option>
                            <option value="In Progress" <?= $r['status']==='In Progress'?'selected':'' ?>>In Progress</option>
                            <option value="Done" <?= $r['status']==='Done'?'selected':'' ?>>Done</option>
                        </select>
                    </form>
                </td>
                <td><?= $r['deadline']?date('M j, Y', strtotime($r['deadline'])):'—' ?></td>
                <td><span class="badge badge-<?= strtolower($r['priority']) ?>"><?= $r['priority'] ?></span></td>
                <td><a href="javascript:void(0)" onclick="openModal('edit<?= $r['id'] ?>')" style="color:var(--purple);font-size:0.85rem;font-weight:600;margin-right:10px;">Edit</a><a href="actions/research_actions.php?action=delete_activity&id=<?= $r['id'] ?>&tab=activities" class="confirm-delete" style="color:var(--red);font-size:0.9rem;">✕</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

    <?php foreach ($rows as $r): ?>
    <div class="modal-overlay" id="edit<?= $r['id'] ?>">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('edit<?= $r['id'] ?>')">&times;</span>
            <h3>Edit Research Task</h3>
            <form method="POST" action="actions/research_actions.php">
                <input type="hidden" name="action" value="update_activity">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="tab" value="activities">
                <div class="form-group"><label>Task Name</label><input type="text" name="activity_name" value="<?= htmlspecialchars($r['activity_name']) ?>" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="3"><?= htmlspecialchars($r['description']) ?></textarea></div>
                <div class="form-group"><label>Assigned To</label><input type="text" name="assigned_to" value="<?= htmlspecialchars($r['assigned_to']) ?>"></div>
                <div class="form-row">
                    <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?= $r['start_date'] ?>"></div>
                    <div class="form-group"><label>Deadline</label><input type="date" name="deadline" value="<?= $r['deadline'] ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="<?= (int)$r['progress'] ?>"></div>
                    <div class="form-group"><label>Priority</label>
                        <select name="priority"><?php foreach (['Low','Medium','High'] as $opt): ?><option <?= $r['priority']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                    </div>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="status"><?php foreach (['Pending','In Progress','Done'] as $opt): ?><option <?= $r['status']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                </div>
                <button type="submit" class="btn btn-block">Save Changes</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            <h3>Add Research Task</h3>
            <form method="POST" action="actions/research_actions.php">
                <input type="hidden" name="action" value="add_activity">
                <input type="hidden" name="tab" value="activities">
                <div class="form-group"><label>Task Name</label><input type="text" name="activity_name" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
                    <div class="form-group"><label>Deadline</label><input type="date" name="deadline"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="0"></div>
                    <div class="form-group"><label>Priority</label>
                        <select name="priority"><option>Low</option><option selected>Medium</option><option>High</option></select>
                    </div>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="status"><option selected>Pending</option><option>In Progress</option><option>Done</option></select>
                </div>
                <button type="submit" class="btn btn-block">Save Task</button>
            </form>
        </div>
    </div>

<?php elseif ($tab === 'deadlines'): ?>
    <div class="table-card">
        <?php if (!$rows): ?><div class="empty-state">No deadlines yet.</div><?php else: ?>
        <table class="data-table">
            <tr><th>Form</th><th>Date</th><th>Priority</th><th>Reminder</th><th>Status</th><th></th></tr>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['deadline_name']) ?><?php if($r['description']):?><div class="list-item-meta"><?= htmlspecialchars($r['description']) ?></div><?php endif; ?></td>
                <td><?= $r['deadline_date']?date('M j, Y', strtotime($r['deadline_date'])):'—' ?></td>
                <td><span class="badge badge-<?= strtolower($r['priority']) ?>"><?= $r['priority'] ?></span></td>
                <td><?= htmlspecialchars($r['reminder']) ?></td>
                <td>
                    <form method="POST" action="actions/research_actions.php">
                        <input type="hidden" name="action" value="toggle_deadline">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="tab" value="deadlines">
                        <select name="status" class="auto-submit-checkbox" style="font-size:0.75rem;padding:4px 6px;">
                            <option value="Pending" <?= $r['status']==='Pending'?'selected':'' ?>>Pending</option>
                            <option value="Done" <?= $r['status']==='Done'?'selected':'' ?>>Done</option>
                        </select>
                    </form>
                </td>
                <td><a href="javascript:void(0)" onclick="openModal('edit<?= $r['id'] ?>')" style="color:var(--purple);font-size:0.85rem;font-weight:600;margin-right:10px;">Edit</a><a href="actions/research_actions.php?action=delete_deadline&id=<?= $r['id'] ?>&tab=deadlines" class="confirm-delete" style="color:var(--red);font-size:0.9rem;">✕</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

    <?php foreach ($rows as $r): ?>
    <div class="modal-overlay" id="edit<?= $r['id'] ?>">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('edit<?= $r['id'] ?>')">&times;</span>
            <h3>Edit Deadline</h3>
            <form method="POST" action="actions/research_actions.php">
                <input type="hidden" name="action" value="update_deadline">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="tab" value="deadlines">
                <div class="form-group"><label>Deadline Name</label><input type="text" name="deadline_name" value="<?= htmlspecialchars($r['deadline_name']) ?>" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="2"><?= htmlspecialchars($r['description']) ?></textarea></div>
                <div class="form-group"><label>Date</label><input type="date" name="deadline_date" value="<?= $r['deadline_date'] ?>"></div>
                <div class="form-group"><label>Reminder</label><input type="text" name="reminder" value="<?= htmlspecialchars($r['reminder']) ?>"></div>
                <div class="form-row">
                    <div class="form-group"><label>Priority</label>
                        <select name="priority"><?php foreach (['Low','Medium','High'] as $opt): ?><option <?= $r['priority']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="form-group"><label>Status</label>
                        <select name="status"><?php foreach (['Pending','Done'] as $opt): ?><option <?= $r['status']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                    </div>
                </div>
                <button type="submit" class="btn btn-block">Save Changes</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            <h3>Add Deadline</h3>
            <form method="POST" action="actions/research_actions.php">
                <input type="hidden" name="action" value="add_deadline">
                <input type="hidden" name="tab" value="deadlines">
                <div class="form-group"><label>Deadline Name</label><input type="text" name="deadline_name" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
                <div class="form-group"><label>Date</label><input type="date" name="deadline_date"></div>
                <div class="form-group"><label>Reminder</label><input type="text" name="reminder" placeholder="e.g. 2 days before"></div>
                <div class="form-row">
                    <div class="form-group"><label>Priority</label>
                        <select name="priority"><option>Low</option><option selected>Medium</option><option>High</option></select>
                    </div>
                    <div class="form-group"><label>Status</label>
                        <select name="status"><option selected>Pending</option><option>Done</option></select>
                    </div>
                </div>
                <button type="submit" class="btn btn-block">Save Deadline</button>
            </form>
        </div>
    </div>

<?php elseif ($tab === 'documents'): ?>
    <div class="item-list">
        <?php if (!$rows): ?><div class="empty-state">No documents uploaded yet.</div><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <div class="item-card">
                <h4>📄 <?= htmlspecialchars($r['title']) ?></h4>
                <?php if ($r['category']): ?><div class="meta">Category: <?= htmlspecialchars($r['category']) ?></div><?php endif; ?>
                <div class="meta">Uploaded: <?= date('M j, Y', strtotime($r['uploaded_at'])) ?></div>
                <div class="item-actions">
                    <?php if ($r['file_path']): ?><a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank" class="btn btn-small btn-secondary">View</a><?php endif; ?>
                    <a href="actions/research_actions.php?action=delete_document&id=<?= $r['id'] ?>&tab=documents" class="btn btn-small btn-danger confirm-delete">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            <h3>Add Document</h3>
            <form method="POST" action="actions/research_actions.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_document">
                <input type="hidden" name="tab" value="documents">
                <div class="form-group"><label>Title</label><input type="text" name="title" required placeholder="e.g. Chapter 1, Proposal, Defense"></div>
                <div class="form-group"><label>Category</label><input type="text" name="category" placeholder="e.g. Proposal, Chapter, Questionnaire"></div>
                <div class="form-group"><label>File</label><input type="file" name="file"></div>
                <button type="submit" class="btn btn-block">Save Document</button>
            </form>
        </div>
    </div>

<?php else: /* progress */ ?>
    <div class="card">
        <div class="card-header"><h3>📈 Research Progress</h3></div>
        <form method="POST" action="actions/research_actions.php">
            <input type="hidden" name="action" value="save_progress">
            <input type="hidden" name="tab" value="progress">
            <?php foreach ($rows as $r): ?>
                <div class="form-row" style="align-items:center;">
                    <input type="hidden" name="stage_id[]" value="<?= $r['id'] ?>">
                    <div class="form-group" style="flex:2;"><input type="text" name="stage_name[]" value="<?= htmlspecialchars($r['stage_name']) ?>"></div>
                    <div class="form-group" style="flex:1;"><input type="number" name="stage_progress[]" value="<?= (int)$r['progress'] ?>" min="0" max="100"></div>
                    <a href="actions/research_actions.php?action=delete_progress&id=<?= $r['id'] ?>&tab=progress" class="btn btn-small btn-danger confirm-delete" style="margin-bottom:14px;">✕</a>
                </div>
            <?php endforeach; ?>
            <div class="form-row" style="align-items:center;">
                <input type="hidden" name="stage_id[]" value="">
                <div class="form-group" style="flex:2;"><input type="text" name="stage_name[]" placeholder="New stage (e.g. Chapter 3)"></div>
                <div class="form-group" style="flex:1;"><input type="number" name="stage_progress[]" placeholder="0-100" min="0" max="100"></div>
                <span style="width:30px;"></span>
            </div>
            <button type="submit" class="btn">Save Progress</button>
        </form>
    </div>
    <?php if ($rows): ?>
    <div class="card" style="margin-top:16px;">
        <div class="card-header"><h3>Overview</h3></div>
        <?php foreach ($rows as $r): ?>
            <div class="progress-label"><span><?= htmlspecialchars($r['stage_name']) ?></span><span><?= (int)$r['progress'] ?>%</span></div>
            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= (int)$r['progress'] ?>%;background:var(--green);"></div></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

</div>
<div class="content-side">
    <div class="card">
        <div class="card-header"><h3>📈 Research Progress</h3><a href="?tab=progress" class="view-all">View Details</a></div>
        <?php if (!$progressStages): ?><div class="empty-state">No progress stages set yet.</div><?php endif; ?>
        <?php foreach ($progressStages as $ps): ?>
            <div class="list-item">
                <div class="list-item-top"><span class="list-item-title"><?= htmlspecialchars($ps['stage_name']) ?></span><span style="font-size:0.78rem;color:var(--muted);font-weight:700;"><?= (int)$ps['progress'] ?>%</span></div>
                <div class="progress-bar-wrap" style="margin-top:4px;"><div class="progress-bar-fill" style="width:<?= (int)$ps['progress'] ?>%;background:var(--green);"></div></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-header"><h3>📌 Reminder</h3><a href="?tab=deadlines" class="view-all">View All</a></div>
        <?php if ($reminder):
            $daysLeft = max(0, (int)((strtotime($reminder['d']) - strtotime($today)) / 86400)); ?>
            <div class="list-item-title"><?= htmlspecialchars($reminder['name']) ?></div>
            <div class="list-item-meta" style="margin-top:4px;"><span style="color:var(--red);font-weight:700;">Due in <?= $daysLeft ?> day<?= $daysLeft==1?'':'s' ?></span> · <?= date('M j, Y', strtotime($reminder['d'])) ?></div>
        <?php else: ?>
            <div class="empty-state">Nothing due soon.</div>
        <?php endif; ?>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>