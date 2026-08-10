<?php
require_once __DIR__ . '/includes/auth_check.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM skills WHERE id=? AND user_id=?");
$stmt->execute([$id, $current_user_id]);
$skill = $stmt->fetch();
if (!$skill) {
    header("Location: learning.php?tab=plan");
    exit;
}
$pageTitle = $skill['skill_name'];
$planTab = $_GET['tab'] ?? 'study';
if (!in_array($planTab, ['study','milestones','notes','schedule'])) $planTab = 'study';

$stmt = $pdo->prepare("SELECT * FROM skill_plan_weeks WHERE skill_id=? ORDER BY week_number ASC");
$stmt->execute([$id]);
$weeks = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM skill_todos WHERE skill_id=? ORDER BY id ASC");
$stmt->execute([$id]);
$todos = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM skill_milestones WHERE skill_id=? ORDER BY milestone_date ASC, id ASC");
$stmt->execute([$id]);
$milestones = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM learning_resources WHERE skill_id=? ORDER BY created_at DESC");
$stmt->execute([$id]);
$resources = $stmt->fetchAll();

// ---- Overview stats ----
$totalWeeks = count($weeks);
$completedWeeks = count(array_filter($weeks, fn($w) => $w['status'] === 'Done'));
$remainingWeeks = $totalWeeks - $completedWeeks;
$totalTasks = 0;
foreach ($weeks as $w) {
    $lines = array_filter(array_map('trim', explode("\n", $w['tasks'] ?? '')));
    $totalTasks += count($lines);
}
$targetCompletion = $totalWeeks ? end($weeks)['target_date'] : null;

$nextMilestone = null;
foreach ($milestones as $m) {
    if ($m['status'] !== 'Done') { $nextMilestone = $m; break; }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$levelColors = ['Beginner' => 'var(--blue)', 'Intermediate' => 'var(--orange)', 'Advanced' => 'var(--purple)'];
?>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>📅 Learning Plan</h1>
            <div class="sub">Plan your learning journey and track your progress.</div>
        </div>
        <a href="learning.php?tab=plan" class="btn btn-secondary">&larr; Back to Learning Studies</a>
    </div>
</div>

<div class="content-wrap">
<div class="content-main">

    <div class="card" style="margin-bottom:18px;">
        <div style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap;justify-content:space-between;">
            <div style="display:flex;gap:14px;">
                <div class="project-icon" style="width:52px;height:52px;font-size:1.4rem;">🎓</div>
                <div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <h3 style="font-size:1.15rem;"><?= htmlspecialchars($skill['skill_name']) ?></h3>
                        <span class="badge" style="background:var(--purple-light);color:var(--purple);"><?= htmlspecialchars($skill['level']) ?></span>
                    </div>
                    <div class="list-item-meta" style="margin-top:4px;max-width:420px;"><?= nl2br(htmlspecialchars($skill['description'])) ?></div>
                </div>
            </div>
            <div style="display:flex;gap:22px;flex-wrap:wrap;">
                <div>
                    <div class="stat-extra">🎯 Target Completion</div>
                    <div style="font-weight:700;font-size:0.85rem;"><?= $targetCompletion ? date('M j, Y', strtotime($targetCompletion)) : '—' ?></div>
                </div>
                <div>
                    <div class="stat-extra">🕐 Total Duration</div>
                    <div style="font-weight:700;font-size:0.85rem;"><?= $totalWeeks ?> Week<?= $totalWeeks==1?'':'s' ?></div>
                </div>
                <div>
                    <div class="stat-extra">🚩 Next Milestone</div>
                    <div style="font-weight:700;font-size:0.85rem;"><?= $nextMilestone ? htmlspecialchars($nextMilestone['milestone_name']) : '—' ?></div>
                </div>
            </div>
        </div>
        <div class="progress-label" style="margin-top:14px;"><span>Progress</span><span><?= (int)$skill['progress'] ?>%</span></div>
        <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= (int)$skill['progress'] ?>%;background:var(--purple);"></div></div>
    </div>

    <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon purple">📚</div><div><div class="stat-num"><?= $totalWeeks ?></div><div class="stat-label">Total Weeks</div></div></div>
        <div class="stat-card"><div class="stat-icon blue">📝</div><div><div class="stat-num"><?= $totalTasks ?></div><div class="stat-label">Total Tasks</div></div></div>
        <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-num"><?= $completedWeeks ?></div><div class="stat-label">Completed</div></div></div>
        <div class="stat-card"><div class="stat-icon orange">⏳</div><div><div class="stat-num"><?= $remainingWeeks ?></div><div class="stat-label">Remaining</div></div></div>
    </div>

    <div class="tabs">
        <a href="?id=<?= $id ?>&tab=study" class="tab-link <?= $planTab==='study'?'active':'' ?>">Study Plan</a>
        <a href="?id=<?= $id ?>&tab=milestones" class="tab-link <?= $planTab==='milestones'?'active':'' ?>">Milestones</a>
        <a href="?id=<?= $id ?>&tab=notes" class="tab-link <?= $planTab==='notes'?'active':'' ?>">Notes</a>
        <a href="?id=<?= $id ?>&tab=schedule" class="tab-link <?= $planTab==='schedule'?'active':'' ?>">Study Schedule</a>
    </div>

    <?php if ($planTab === 'study'): ?>
        <div class="table-card">
            <?php if (!$weeks): ?><div class="empty-state">No weeks planned yet. Add your first week below!</div><?php else: ?>
            <table class="data-table">
                <tr><th>Week / Topic</th><th>Tasks</th><th>Status</th><th>Progress</th><th></th></tr>
                <?php foreach ($weeks as $w): $taskLines = array_filter(array_map('trim', explode("\n", $w['tasks'] ?? ''))); ?>
                <tr>
                    <td style="white-space:nowrap;"><strong>Week <?= (int)$w['week_number'] ?></strong><div class="list-item-meta"><?= htmlspecialchars($w['topic']) ?></div></td>
                    <td>
                        <ul style="list-style:disc;padding-left:16px;">
                            <?php foreach ($taskLines as $line): ?><li style="font-size:0.82rem;"><?= htmlspecialchars($line) ?></li><?php endforeach; ?>
                        </ul>
                    </td>
                    <td><span class="badge badge-<?= strtolower(str_replace(' ','',$w['status'])) ?>"><?= $w['status'] ?></span></td>
                    <td>
                        <div class="table-progress">
                            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= (int)$w['progress'] ?>%;background:var(--purple);"></div></div>
                            <span style="font-size:0.78rem;color:var(--muted);"><?= (int)$w['progress'] ?>%</span>
                        </div>
                    </td>
                    <td>
                        <a href="javascript:void(0)" onclick="openModal('editWeek<?= $w['id'] ?>')" style="color:var(--purple);font-size:0.85rem;font-weight:600;margin-right:8px;">Edit</a>
                        <a href="actions/skill_plan_actions.php?action=delete_week&week_id=<?= $w['id'] ?>&skill_id=<?= $id ?>&plan_tab=study" class="confirm-delete" style="color:var(--red);">✕</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>

        <?php foreach ($weeks as $w): ?>
        <div class="modal-overlay" id="editWeek<?= $w['id'] ?>">
            <div class="modal-box">
                <span class="close-modal" onclick="closeModal('editWeek<?= $w['id'] ?>')">&times;</span>
                <h3>Edit Week <?= (int)$w['week_number'] ?></h3>
                <form method="POST" action="actions/skill_plan_actions.php">
                    <input type="hidden" name="action" value="update_week">
                    <input type="hidden" name="id" value="<?= $w['id'] ?>">
                    <input type="hidden" name="skill_id" value="<?= $id ?>">
                    <input type="hidden" name="plan_tab" value="study">
                    <div class="form-row">
                        <div class="form-group"><label>Week Number</label><input type="number" name="week_number" min="1" value="<?= (int)$w['week_number'] ?>"></div>
                        <div class="form-group"><label>Target Date</label><input type="date" name="target_date" value="<?= $w['target_date'] ?>"></div>
                    </div>
                    <div class="form-group"><label>Topic</label><input type="text" name="topic" value="<?= htmlspecialchars($w['topic']) ?>" required></div>
                    <div class="form-group"><label>Tasks (one per line)</label><textarea name="tasks" rows="4"><?= htmlspecialchars($w['tasks']) ?></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>Status</label>
                            <select name="status"><?php foreach (['Pending','In Progress','Done'] as $opt): ?><option <?= $w['status']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                        </div>
                        <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="<?= (int)$w['progress'] ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-block">Save Changes</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <button class="btn" style="margin-top:14px;" onclick="openModal('addWeekModal')">+ Add Week / Topic</button>

        <div class="modal-overlay" id="addWeekModal">
            <div class="modal-box">
                <span class="close-modal" onclick="closeModal('addWeekModal')">&times;</span>
                <h3>Add Week / Topic</h3>
                <form method="POST" action="actions/skill_plan_actions.php">
                    <input type="hidden" name="action" value="add_week">
                    <input type="hidden" name="skill_id" value="<?= $id ?>">
                    <input type="hidden" name="plan_tab" value="study">
                    <div class="form-row">
                        <div class="form-group"><label>Week Number</label><input type="number" name="week_number" min="1" value="<?= $totalWeeks + 1 ?>"></div>
                        <div class="form-group"><label>Target Date</label><input type="date" name="target_date"></div>
                    </div>
                    <div class="form-group"><label>Topic</label><input type="text" name="topic" required placeholder="e.g. Flexbox (Layout)"></div>
                    <div class="form-group"><label>Tasks (one per line)</label><textarea name="tasks" rows="4" placeholder="Flex container &amp; items&#10;Justify content &amp; align items&#10;Real-world layouts"></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>Status</label>
                            <select name="status"><option selected>Pending</option><option>In Progress</option><option>Done</option></select>
                        </div>
                        <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="0"></div>
                    </div>
                    <button type="submit" class="btn btn-block">Save Week</button>
                </form>
            </div>
        </div>

    <?php elseif ($planTab === 'milestones'): ?>
        <div class="item-list">
            <?php if (!$milestones): ?><div class="empty-state">No milestones yet.</div><?php endif; ?>
            <?php foreach ($milestones as $m): ?>
                <div class="item-card">
                    <h4><?= $m['status']==='Done'?'✔':'☐' ?> <?= htmlspecialchars($m['milestone_name']) ?></h4>
                    <?php if ($m['milestone_date']): ?><div class="meta">📅 <?= date('M j, Y', strtotime($m['milestone_date'])) ?></div><?php endif; ?>
                    <div class="item-actions">
                        <form method="POST" action="actions/skill_plan_actions.php">
                            <input type="hidden" name="action" value="toggle_milestone">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <input type="hidden" name="skill_id" value="<?= $id ?>">
                            <input type="hidden" name="plan_tab" value="milestones">
                            <select name="status" class="auto-submit-checkbox" style="font-size:0.75rem;padding:4px 8px;">
                                <option value="Pending" <?= $m['status']==='Pending'?'selected':'' ?>>Pending</option>
                                <option value="Done" <?= $m['status']==='Done'?'selected':'' ?>>Done</option>
                            </select>
                        </form>
                        <a href="actions/skill_plan_actions.php?action=delete_milestone&milestone_id=<?= $m['id'] ?>&skill_id=<?= $id ?>&plan_tab=milestones" class="btn btn-small btn-danger confirm-delete">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="btn" style="margin-top:14px;" onclick="openModal('addMilestoneModal')">+ Add Milestone</button>
        <div class="modal-overlay" id="addMilestoneModal">
            <div class="modal-box">
                <span class="close-modal" onclick="closeModal('addMilestoneModal')">&times;</span>
                <h3>Add Milestone</h3>
                <form method="POST" action="actions/skill_plan_actions.php">
                    <input type="hidden" name="action" value="add_milestone">
                    <input type="hidden" name="skill_id" value="<?= $id ?>">
                    <input type="hidden" name="plan_tab" value="milestones">
                    <div class="form-group"><label>Milestone Name</label><input type="text" name="milestone_name" required placeholder="e.g. Finish Flexbox"></div>
                    <div class="form-group"><label>Date</label><input type="date" name="milestone_date"></div>
                    <div class="form-group"><label>Status</label>
                        <select name="status"><option selected>Pending</option><option>Done</option></select>
                    </div>
                    <button type="submit" class="btn btn-block">Save Milestone</button>
                </form>
            </div>
        </div>

    <?php elseif ($planTab === 'notes'): ?>
        <div class="card">
            <form method="POST" action="actions/skill_plan_actions.php">
                <input type="hidden" name="action" value="update_notes">
                <input type="hidden" name="skill_id" value="<?= $id ?>">
                <input type="hidden" name="plan_tab" value="notes">
                <div class="form-group"><label>Notes for <?= htmlspecialchars($skill['skill_name']) ?></label><textarea name="notes" rows="10" placeholder="Jot down anything worth remembering..."><?= htmlspecialchars($skill['notes']) ?></textarea></div>
                <button type="submit" class="btn">Save Notes</button>
            </form>
        </div>

    <?php else: /* schedule */ ?>
        <div class="card">
            <form method="POST" action="actions/skill_plan_actions.php">
                <input type="hidden" name="action" value="update_schedule">
                <input type="hidden" name="skill_id" value="<?= $id ?>">
                <input type="hidden" name="plan_tab" value="schedule">
                <div class="form-group"><label>Study Schedule</label><textarea name="study_schedule" rows="8" placeholder="e.g. Mon/Wed/Fri 7-8PM, Sat 10AM-12NN"><?= htmlspecialchars($skill['study_schedule']) ?></textarea></div>
                <button type="submit" class="btn">Save Schedule</button>
            </form>
        </div>
    <?php endif; ?>

</div>

<div class="content-side">
    <div class="card">
        <div class="card-header"><h3>✅ Things To Do</h3></div>
        <?php if (!$todos): ?><div class="empty-state">Nothing added yet.</div><?php endif; ?>
        <?php foreach ($todos as $t): ?>
            <div class="todo-item <?= $t['is_done']?'done':'' ?>">
                <form method="POST" action="actions/skill_plan_actions.php" style="display:flex;align-items:center;gap:8px;">
                    <input type="hidden" name="action" value="toggle_skill_todo">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="skill_id" value="<?= $id ?>">
                    <input type="hidden" name="plan_tab" value="<?= $planTab ?>">
                    <input type="hidden" name="is_done" value="<?= $t['is_done'] ? 0 : 1 ?>">
                    <button type="submit" class="task-check <?= $t['is_done']?'done':'' ?>"><?= $t['is_done']?'✓':'' ?></button>
                </form>
                <label style="flex:1;font-size:0.85rem;"><?= htmlspecialchars($t['task_name']) ?></label>
                <a href="actions/skill_plan_actions.php?action=delete_skill_todo&todo_id=<?= $t['id'] ?>&skill_id=<?= $id ?>&plan_tab=<?= $planTab ?>" class="confirm-delete" style="color:var(--red);font-size:0.78rem;">✕</a>
            </div>
        <?php endforeach; ?>
        <form method="POST" action="actions/skill_plan_actions.php" style="margin-top:10px;display:flex;gap:6px;">
            <input type="hidden" name="action" value="add_skill_todo">
            <input type="hidden" name="skill_id" value="<?= $id ?>">
            <input type="hidden" name="plan_tab" value="<?= $planTab ?>">
            <input type="text" name="task_name" placeholder="Add a to-do..." required style="flex:1;padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:0.83rem;">
            <button type="submit" class="btn btn-small">Add</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3>🔗 Resources for <?= htmlspecialchars($skill['skill_name']) ?></h3></div>
        <?php if (!$resources): ?><div class="empty-state">No resources linked yet.</div><?php endif; ?>
        <?php foreach ($resources as $r): ?>
            <div class="list-item">
                <div class="list-item-top">
                    <span class="list-item-title"><?= htmlspecialchars($r['title']) ?></span>
                    <a href="actions/skill_plan_actions.php?action=delete_skill_resource&res_id=<?= $r['id'] ?>&skill_id=<?= $id ?>&plan_tab=study" class="confirm-delete" style="color:var(--red);font-size:0.75rem;">✕</a>
                </div>
                <div class="list-item-meta"><?= htmlspecialchars($r['resource_type']) ?><?= $r['estimated_time'] ? ' · ' . htmlspecialchars($r['estimated_time']) : '' ?></div>
                <?php if (!empty($r['link'])): ?><a href="<?= htmlspecialchars($r['link']) ?>" target="_blank" rel="noopener" style="font-size:0.78rem;color:var(--purple);font-weight:600;">🔗 Open Link</a><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-small" style="margin-top:10px;" onclick="openModal('addResourceModal')">+ Add Resource</button>
    </div>

    <div class="sidebar-tip" style="margin-top:0;">
        <span class="tip-icon">💡</span>
        <strong>Study Tip</strong>
        <div class="tip-sub">Focus more on practice than perfection. Build small projects and learn by doing!</div>
    </div>
</div>
</div>

<div class="modal-overlay" id="addResourceModal">
    <div class="modal-box">
        <span class="close-modal" onclick="closeModal('addResourceModal')">&times;</span>
        <h3>Add Resource for <?= htmlspecialchars($skill['skill_name']) ?></h3>
        <form method="POST" action="actions/skill_plan_actions.php">
            <input type="hidden" name="action" value="add_skill_resource">
            <input type="hidden" name="skill_id" value="<?= $id ?>">
            <input type="hidden" name="plan_tab" value="study">
            <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
            <div class="form-group"><label>Link (URL)</label><input type="url" name="link" placeholder="https://..."></div>
            <div class="form-row">
                <div class="form-group"><label>Type</label>
                    <select name="resource_type"><option>Website</option><option>Course</option><option>Book</option><option>Video</option><option>Documentation</option></select>
                </div>
                <div class="form-group"><label>Estimated Time</label><input type="text" name="estimated_time" placeholder="e.g. 1.5 hrs"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Difficulty</label>
                    <select name="difficulty"><option selected>Beginner</option><option>Intermediate</option><option>Advanced</option></select>
                </div>
                <div class="form-group"><label>Category</label><input type="text" name="category" placeholder="e.g. YouTube"></div>
            </div>
            <input type="hidden" name="status" value="Not Started">
            <button type="submit" class="btn btn-block">Save Resource</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>