<?php
require_once __DIR__ . '/includes/auth_check.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM personal_projects WHERE id=? AND user_id=?");
$stmt->execute([$id, $current_user_id]);
$project = $stmt->fetch();
if (!$project) {
    header("Location: personal_projects.php");
    exit;
}
$pageTitle = $project['project_name'];

$stmt = $pdo->prepare("SELECT * FROM project_todos WHERE project_id=? ORDER BY id ASC");
$stmt->execute([$id]);
$todos = $stmt->fetchAll();
$total = count($todos);
$done = count(array_filter($todos, fn($t) => $t['is_done']));

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<a href="personal_projects.php" style="color:var(--muted);font-size:0.85rem;">&larr; Back to Projects</a>
<div class="page-header">
    <h1>💼 <?= htmlspecialchars($project['project_name']) ?></h1>
    <div class="sub">Project workspace</div>
</div>

<div class="content-wrap">
<div class="content-main">
    <div class="card">
        <div class="card-header"><h3>📋 Project Details</h3><button class="btn btn-small btn-secondary" onclick="openModal('editModal')">Edit</button></div>
        <p style="margin-bottom:12px;color:var(--text);font-size:0.9rem;"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
            <span class="badge badge-<?= strtolower(str_replace(' ','',$project['status'])) ?>"><?= $project['status'] ?></span>
            <?php if ($project['start_date']): ?><span class="list-item-meta">🚀 Start Date: <?= date('M j, Y', strtotime($project['start_date'])) ?></span><?php endif; ?>
            <?php if ($project['deadline']): ?><span class="list-item-meta">📅 Deadline: <?= date('M j, Y', strtotime($project['deadline'])) ?></span><?php endif; ?>
        </div>
        <div class="progress-label"><span>Progress</span><span><?= (int)$project['progress'] ?>%</span></div>
        <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= (int)$project['progress'] ?>%;background:var(--purple);"></div></div>
    </div>
</div>

<div class="content-side">
    <div class="card">
        <div class="card-header"><h3>✅ Things To Do (<?= $done ?>/<?= $total ?>)</h3></div>
        <?php foreach ($todos as $t): ?>
            <div class="todo-item <?= $t['is_done']?'done':'' ?>">
                <form method="POST" action="actions/projects_actions.php" style="display:flex;align-items:center;gap:8px;">
                    <input type="hidden" name="action" value="toggle_todo">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="project_id" value="<?= $id ?>">
                    <input type="hidden" name="is_done" value="<?= $t['is_done'] ? 0 : 1 ?>">
                    <button type="submit" class="task-check <?= $t['is_done']?'done':'' ?>"><?= $t['is_done']?'✓':'' ?></button>
                </form>
                <label style="flex:1;"><?= htmlspecialchars($t['task_name']) ?></label>
                <a href="actions/projects_actions.php?action=delete_todo&id=<?= $t['id'] ?>&project_id=<?= $id ?>" class="confirm-delete" style="color:var(--red);font-size:0.8rem;">✕</a>
            </div>
        <?php endforeach; ?>
        <?php if (!$todos): ?><div class="empty-state">No to-dos yet.</div><?php endif; ?>
        <form method="POST" action="actions/projects_actions.php" style="margin-top:12px;display:flex;gap:8px;">
            <input type="hidden" name="action" value="add_todo">
            <input type="hidden" name="project_id" value="<?= $id ?>">
            <input type="text" name="task_name" placeholder="Add a to-do..." required style="flex:1;padding:9px;border:1px solid var(--border);border-radius:9px;background:var(--bg);color:var(--text);">
            <button type="submit" class="btn btn-small">Add</button>
        </form>
    </div>
</div>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <span class="close-modal" onclick="closeModal('editModal')">&times;</span>
        <h3>Edit Project</h3>
        <form method="POST" action="actions/projects_actions.php">
            <input type="hidden" name="action" value="update_project">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-group"><label>Project Name</label><input type="text" name="project_name" value="<?= htmlspecialchars($project['project_name']) ?>" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="3"><?= htmlspecialchars($project['description']) ?></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Status</label>
                    <select name="status">
                        <?php foreach (['Not Started','In Progress','On Hold','Completed'] as $s): ?>
                            <option <?= $project['status']===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="<?= (int)$project['progress'] ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?= $project['start_date'] ?>"></div>
                <div class="form-group"><label>Deadline</label><input type="date" name="deadline" value="<?= $project['deadline'] ?>"></div>
            </div>
            <button type="submit" class="btn btn-block">Save Changes</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>