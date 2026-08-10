<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Project Goals";

$stmt = $pdo->prepare("SELECT * FROM project_goals WHERE user_id=? ORDER BY category, created_at DESC");
$stmt->execute([$current_user_id]);
$goals = $stmt->fetchAll();
$byCategory = ['Business'=>[], 'Development'=>[], 'Learning'=>[], 'Other'=>[]];
foreach ($goals as $g) { $byCategory[$g['category']][] = $g; }

$goalTodos = [];
if ($goals) {
    $ids = array_column($goals, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM goal_todos WHERE goal_id IN ($in) ORDER BY id ASC");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $t) {
        $goalTodos[$t['goal_id']][] = $t;
    }
}

$stmt = $pdo->prepare("SELECT * FROM project_milestones WHERE user_id=? ORDER BY milestone_date ASC, id ASC");
$stmt->execute([$current_user_id]);
$milestones = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>🎯 Project Goals</h1>
            <div class="sub">Track goals by category and celebrate milestones.</div>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary" onclick="openModal('milestoneModal')">+ Add Milestone</button>
            <button class="btn" onclick="openModal('goalModal')">+ Add Goal</button>
        </div>
    </div>
</div>

<div class="tabs">
    <a href="personal_projects.php" class="tab-link">My Projects</a>
    <a href="project_goals.php" class="tab-link active">Project Goals</a>
</div>

<?php foreach ($byCategory as $cat => $items): if (!$items) continue; ?>
    <div class="section-block">
        <h2><?= $cat ?> Goals</h2>
        <div class="item-list">
            <?php foreach ($items as $g): ?>
                <div class="item-card">
                    <h4><?= htmlspecialchars($g['goal_name']) ?></h4>
                    <?php if ($g['description']): ?><div class="desc"><?= nl2br(htmlspecialchars($g['description'])) ?></div><?php endif; ?>
                    <?php if ($g['target_date']): ?><div class="meta">🎯 Target: <?= date('M j, Y', strtotime($g['target_date'])) ?></div><?php endif; ?>
                    <div class="progress-label"><span>Progress</span><span><?= (int)$g['progress'] ?>%</span></div>
                    <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= (int)$g['progress'] ?>%"></div></div>
                    <span class="badge badge-<?= strtolower($g['priority']) ?>"><?= $g['priority'] ?></span>
                    <span class="badge badge-<?= strtolower(str_replace(' ','',$g['status'])) ?>"><?= $g['status'] ?></span>
                    <div class="item-actions">
                        <button type="button" class="btn btn-small btn-secondary" onclick="openModal('editGoal<?= $g['id'] ?>')">Edit</button>
                        <a href="actions/projects_actions.php?action=delete_goal&id=<?= $g['id'] ?>" class="btn btn-small btn-danger confirm-delete">Delete</a>
                    </div>

                    <?php $todos = $goalTodos[$g['id']] ?? []; ?>
                    <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--border);">
                        <div class="list-item-meta" style="font-weight:700;margin-bottom:4px;">✅ Things To Do</div>
                        <?php if (!$todos): ?>
                            <div class="empty-state" style="padding:6px 0;font-size:0.78rem;">Nothing added yet.</div>
                        <?php else: foreach ($todos as $t): ?>
                            <div class="todo-item <?= $t['is_done']?'done':'' ?>" style="padding:5px 0;">
                                <form method="POST" action="actions/projects_actions.php" style="display:flex;align-items:center;gap:8px;">
                                    <input type="hidden" name="action" value="toggle_goal_todo">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <input type="hidden" name="is_done" value="<?= $t['is_done'] ? 0 : 1 ?>">
                                    <button type="submit" class="task-check <?= $t['is_done']?'done':'' ?>"><?= $t['is_done']?'✓':'' ?></button>
                                </form>
                                <label style="flex:1;font-size:0.85rem;"><?= htmlspecialchars($t['task_name']) ?></label>
                                <a href="actions/projects_actions.php?action=delete_goal_todo&id=<?= $t['id'] ?>" class="confirm-delete" style="color:var(--red);font-size:0.78rem;">✕</a>
                            </div>
                        <?php endforeach; endif; ?>
                        <form method="POST" action="actions/projects_actions.php" style="margin-top:8px;display:flex;gap:6px;">
                            <input type="hidden" name="action" value="add_goal_todo">
                            <input type="hidden" name="goal_id" value="<?= $g['id'] ?>">
                            <input type="text" name="task_name" placeholder="Add a to-do..." required style="flex:1;padding:7px 9px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:0.82rem;">
                            <button type="submit" class="btn btn-small">Add</button>
                        </form>
                    </div>
                </div>
                <div class="modal-overlay" id="editGoal<?= $g['id'] ?>">
                    <div class="modal-box">
                        <span class="close-modal" onclick="closeModal('editGoal<?= $g['id'] ?>')">&times;</span>
                        <h3>Edit Goal</h3>
                        <form method="POST" action="actions/projects_actions.php">
                            <input type="hidden" name="action" value="update_goal">
                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
                            <div class="form-group"><label>Category</label>
                                <select name="category">
                                    <?php foreach (['Business','Development','Learning','Other'] as $opt): ?><option <?= $g['category']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Goal Name</label><input type="text" name="goal_name" value="<?= htmlspecialchars($g['goal_name']) ?>" required></div>
                            <div class="form-group"><label>Description</label><textarea name="description" rows="2"><?= htmlspecialchars($g['description']) ?></textarea></div>
                            <div class="form-row">
                                <div class="form-group"><label>Target Date</label><input type="date" name="target_date" value="<?= $g['target_date'] ?>"></div>
                                <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="<?= (int)$g['progress'] ?>"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label>Status</label>
                                    <select name="status"><?php foreach (['Pending','In Progress','Done'] as $opt): ?><option <?= $g['status']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                                </div>
                                <div class="form-group"><label>Priority</label>
                                    <select name="priority"><?php foreach (['Low','Medium','High'] as $opt): ?><option <?= $g['priority']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-block">Save Changes</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
<?php if (!$goals): ?><div class="empty-state">No goals set yet. Add your first goal!</div><?php endif; ?>

<div class="section-block">
    <h2>🏆 Milestones</h2>
    <div class="item-list">
        <?php foreach ($milestones as $m): ?>
            <div class="item-card">
                <h4><?= $m['status']==='Done'?'✔':'☐' ?> <?= htmlspecialchars($m['milestone_name']) ?></h4>
                <?php if ($m['milestone_date']): ?><div class="meta">📅 <?= date('M j, Y', strtotime($m['milestone_date'])) ?></div><?php endif; ?>
                <div class="item-actions">
                    <form method="POST" action="actions/projects_actions.php">
                        <input type="hidden" name="action" value="toggle_milestone">
                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                        <select name="status" class="auto-submit-checkbox" style="font-size:0.75rem;padding:3px 6px;">
                            <option value="Pending" <?= $m['status']==='Pending'?'selected':'' ?>>Pending</option>
                            <option value="Done" <?= $m['status']==='Done'?'selected':'' ?>>Done</option>
                        </select>
                    </form>
                    <a href="actions/projects_actions.php?action=delete_milestone&id=<?= $m['id'] ?>" class="btn btn-small btn-danger confirm-delete">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$milestones): ?><div class="empty-state">No milestones yet.</div><?php endif; ?>
    </div>
</div>

<div class="modal-overlay" id="goalModal">
    <div class="modal-box">
        <span class="close-modal" onclick="closeModal('goalModal')">&times;</span>
        <h3>Add Goal</h3>
        <form method="POST" action="actions/projects_actions.php">
            <input type="hidden" name="action" value="add_goal">
            <div class="form-group"><label>Category</label>
                <select name="category"><option>Business</option><option>Development</option><option>Learning</option><option>Other</option></select>
            </div>
            <div class="form-group"><label>Goal Name</label><input type="text" name="goal_name" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Target Date</label><input type="date" name="target_date"></div>
                <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="0"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Status</label>
                    <select name="status"><option selected>Pending</option><option>In Progress</option><option>Done</option></select>
                </div>
                <div class="form-group"><label>Priority</label>
                    <select name="priority"><option>Low</option><option selected>Medium</option><option>High</option></select>
                </div>
            </div>
            <button type="submit" class="btn btn-block">Save Goal</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="milestoneModal">
    <div class="modal-box">
        <span class="close-modal" onclick="closeModal('milestoneModal')">&times;</span>
        <h3>Add Milestone</h3>
        <form method="POST" action="actions/projects_actions.php">
            <input type="hidden" name="action" value="add_milestone">
            <div class="form-group"><label>Milestone Name</label><input type="text" name="milestone_name" required placeholder="e.g. Version 1, Beta Launch"></div>
            <div class="form-group"><label>Date</label><input type="date" name="milestone_date"></div>
            <div class="form-group"><label>Status</label>
                <select name="status"><option selected>Pending</option><option>Done</option></select>
            </div>
            <button type="submit" class="btn btn-block">Save Milestone</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>