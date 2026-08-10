<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Projects";
$stmt = $pdo->prepare("SELECT * FROM personal_projects WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$current_user_id]);
$projects = $stmt->fetchAll();

$avgProgress = 0;
if ($projects) {
    $avgProgress = round(array_sum(array_column($projects, 'progress')) / count($projects));
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM project_goals WHERE user_id=? AND status != 'Done'");
$stmt->execute([$current_user_id]);
$openGoals = $stmt->fetchColumn();

// Fetch to-do items for every project, grouped by project_id
$todosByProject = [];
if ($projects) {
    $ids = array_column($projects, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM project_todos WHERE project_id IN ($in) ORDER BY id ASC");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $t) {
        $todosByProject[$t['project_id']][] = $t;
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$icons = ['💼','🚀','🌐','📦','🛠️','🎯'];
?>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>💼 Projects</h1>
            <div class="sub">Work on your personal and academic projects.</div>
        </div>
        <button class="btn" onclick="openModal('addModal')">+ New Project</button>
    </div>
</div>

<div class="tabs">
    <a href="personal_projects.php" class="tab-link active">My Projects</a>
    <a href="project_goals.php" class="tab-link">Project Goals</a>
</div>

<div class="content-wrap">
<div class="content-main">
    <div class="item-list">
        <?php if (!$projects): ?><div class="empty-state">No projects yet. Start one!</div><?php endif; ?>
        <?php foreach ($projects as $i => $p): ?>
            <div class="project-card">
                <div class="project-card-top">
                    <div class="project-icon"><?= $icons[$i % count($icons)] ?></div>
                    <span class="badge badge-<?= strtolower(str_replace(' ','',$p['status'])) ?>"><?= $p['status'] ?></span>
                </div>
                <h4 style="margin-top:10px;font-size:1rem;"><?= htmlspecialchars($p['project_name']) ?></h4>
                <?php if ($p['description']): ?><div class="desc" style="font-size:0.82rem;color:var(--muted);"><?= htmlspecialchars(mb_strimwidth($p['description'],0,70,'…')) ?></div><?php endif; ?>
                <?php if ($p['start_date']): ?><div class="meta" style="margin-top:6px;">🚀 Started <?= date('M j, Y', strtotime($p['start_date'])) ?></div><?php endif; ?>
                <div class="progress-bar-wrap" style="margin-top:10px;"><div class="progress-bar-fill" style="width:<?= (int)$p['progress'] ?>%;background:var(--purple);"></div></div>
                <div class="list-item-meta" style="display:flex;justify-content:space-between;margin-top:4px;">
                    <span><?= (int)$p['progress'] ?>% complete</span>
                    <?php if ($p['deadline']): ?><span>Due <?= date('M j', strtotime($p['deadline'])) ?></span><?php endif; ?>
                </div>
                <div class="item-actions">
                    <a href="project_view.php?id=<?= $p['id'] ?>" class="btn btn-small">Open</a>
                    <a href="actions/projects_actions.php?action=delete_project&id=<?= $p['id'] ?>" class="btn btn-small btn-danger confirm-delete">Delete</a>
                </div>

                <?php $todos = $todosByProject[$p['id']] ?? []; ?>
                <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--border);">
                    <div class="list-item-meta" style="font-weight:700;margin-bottom:4px;">✅ Things To Do</div>
                    <?php if (!$todos): ?>
                        <div class="empty-state" style="padding:6px 0;font-size:0.78rem;">Nothing added yet.</div>
                    <?php else: foreach ($todos as $t): ?>
                        <div class="todo-item <?= $t['is_done']?'done':'' ?>" style="padding:5px 0;">
                            <form method="POST" action="actions/projects_actions.php" style="display:flex;align-items:center;gap:8px;">
                                <input type="hidden" name="action" value="toggle_todo">
                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="is_done" value="<?= $t['is_done'] ? 0 : 1 ?>">
                                <button type="submit" class="task-check <?= $t['is_done']?'done':'' ?>"><?= $t['is_done']?'✓':'' ?></button>
                            </form>
                            <label style="flex:1;font-size:0.85rem;"><?= htmlspecialchars($t['task_name']) ?></label>
                            <a href="actions/projects_actions.php?action=delete_todo&id=<?= $t['id'] ?>&project_id=<?= $p['id'] ?>&return=list" class="confirm-delete" style="color:var(--red);font-size:0.78rem;">✕</a>
                        </div>
                    <?php endforeach; endif; ?>
                    <form method="POST" action="actions/projects_actions.php" style="margin-top:8px;display:flex;gap:6px;">
                        <input type="hidden" name="action" value="add_todo">
                        <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="return" value="list">
                        <input type="text" name="task_name" placeholder="Add a to-do..." required style="flex:1;padding:7px 9px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:0.82rem;">
                        <button type="submit" class="btn btn-small">Add</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="content-side">
    <div class="card" style="text-align:center;">
        <div class="card-header" style="justify-content:center;"><h3>Progress Overview</h3></div>
        <div class="ring ring-lg" data-progress="<?= $avgProgress ?>" data-color="#6c5ce7" style="margin:0 auto;"><span><?= $avgProgress ?>%</span></div>
        <div class="list-item-meta" style="margin-top:10px;">Total Progress</div>
    </div>
    <div class="card">
        <div class="card-header"><h3>🎯 Project Goals</h3><a href="project_goals.php" class="view-all">View All</a></div>
        <div class="list-item-meta"><?= $openGoals ?> goal<?= $openGoals==1?'':'s' ?> still in progress.</div>
        <a href="project_goals.php" class="btn btn-small btn-secondary" style="margin-top:10px;display:inline-block;">Manage Goals</a>
    </div>
</div>
</div>

<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
        <h3>New Project</h3>
        <form method="POST" action="actions/projects_actions.php">
            <input type="hidden" name="action" value="add_project">
            <div class="form-group"><label>Project Name</label><input type="text" name="project_name" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Status</label>
                    <select name="status"><option selected>Not Started</option><option>In Progress</option><option>On Hold</option><option>Completed</option></select>
                </div>
                <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="0"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
                <div class="form-group"><label>Deadline</label><input type="date" name="deadline"></div>
            </div>
            <button type="submit" class="btn btn-block">Create Project</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>