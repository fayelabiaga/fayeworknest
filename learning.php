<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Learning Studies";
$tab = $_GET['tab'] ?? 'skills';
if (!in_array($tab, ['skills','plan','resources','notes'])) $tab = 'skills';

$stmt = $pdo->prepare("SELECT * FROM skills WHERE user_id=? ORDER BY progress DESC, id DESC");
$stmt->execute([$current_user_id]);
$skills = $stmt->fetchAll();

// Next milestone per skill (earliest pending)
$nextMilestoneBySkill = [];
if ($skills) {
    $ids = array_column($skills, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM skill_milestones WHERE skill_id IN ($in) AND status != 'Done' ORDER BY milestone_date ASC, id ASC");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $m) {
        if (!isset($nextMilestoneBySkill[$m['skill_id']])) $nextMilestoneBySkill[$m['skill_id']] = $m;
    }
}

// ---- Stat cards ----
$learningCount = count(array_filter($skills, fn($s) => $s['status'] !== 'Mastered'));
$overallProgress = $skills ? round(array_sum(array_column($skills, 'progress')) / count($skills)) : 0;

// "Days in a Row" streak — consecutive days (from today backwards) with at least one completed skill to-do
$streak = 0;
if ($skills) {
    $ids = array_column($skills, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT DISTINCT DATE(completed_at) d FROM skill_todos WHERE skill_id IN ($in) AND completed_at IS NOT NULL");
    $stmt->execute($ids);
    $doneDates = array_flip(array_column($stmt->fetchAll(), 'd'));
    $cursor = date('Y-m-d');
    if (!isset($doneDates[$cursor])) $cursor = date('Y-m-d', strtotime('-1 day'));
    while (isset($doneDates[$cursor])) {
        $streak++;
        $cursor = date('Y-m-d', strtotime('-1 day', strtotime($cursor)));
    }
}

// Upcoming milestones across all skills (for sidebar)
$upcomingMilestones = $nextMilestoneBySkill;
usort($upcomingMilestones, fn($a, $b) => strcmp($a['milestone_date'] ?? '9999', $b['milestone_date'] ?? '9999'));
$upcomingMilestones = array_slice($upcomingMilestones, 0, 4);
$skillNameById = [];
foreach ($skills as $s) { $skillNameById[$s['id']] = $s['skill_name']; }

if ($tab === 'resources') {
    $stmt = $pdo->prepare("SELECT * FROM learning_resources WHERE user_id=? ORDER BY created_at DESC");
    $stmt->execute([$current_user_id]); $resourceRows = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>📖 Learning Studies</h1>
            <div class="sub">Track your skills, learning plans, and resources.</div>
        </div>
        <?php if ($tab === 'skills'): ?>
            <button class="btn" onclick="openModal('addModal')">+ Add Skill</button>
        <?php elseif ($tab === 'resources'): ?>
            <button class="btn" onclick="openModal('addResourceModal')">+ Add Resource</button>
        <?php endif; ?>
    </div>
</div>

<div class="tabs">
    <a href="?tab=skills" class="tab-link <?= $tab==='skills'?'active':'' ?>">My Skills</a>
    <a href="?tab=plan" class="tab-link <?= $tab==='plan'?'active':'' ?>">Learning Plan</a>
    <a href="?tab=resources" class="tab-link <?= $tab==='resources'?'active':'' ?>">Resources to Learn</a>
    <a href="?tab=notes" class="tab-link <?= $tab==='notes'?'active':'' ?>">Notes</a>
</div>

<?php if ($tab === 'skills'): ?>

    <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);">
        <div class="stat-card"><div class="stat-icon purple">🎓</div><div><div class="stat-num"><?= $learningCount ?></div><div class="stat-label">Skills I'm Learning</div><div class="stat-extra">Keep going!</div></div></div>
        <div class="stat-card"><div class="ring ring-lg" data-progress="<?= $overallProgress ?>" data-color="#6c5ce7"><span><?= $overallProgress ?>%</span></div><div><div class="stat-label" style="font-weight:700;color:var(--text);">Overall Progress</div><div class="stat-extra">You're doing great!</div></div></div>
        <div class="stat-card"><div class="stat-icon orange">🔥</div><div><div class="stat-num"><?= $streak ?></div><div class="stat-label">Days in a Row</div><div class="stat-extra">Keep the streak!</div></div></div>
    </div>

    <div class="content-wrap">
    <div class="content-main">
        <div class="table-card">
            <?php if (!$skills): ?><div class="empty-state">No skills tracked yet. Add your first one!</div><?php else: ?>
            <table class="data-table">
                <tr><th>Skill</th><th>Description</th><th>Level</th><th>Progress</th><th>Status</th><th>Next Milestone</th><th></th></tr>
                <?php foreach ($skills as $s): $nm = $nextMilestoneBySkill[$s['id']] ?? null; ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s['skill_name']) ?></strong></td>
                    <td style="max-width:220px;color:var(--muted);"><?= htmlspecialchars(mb_strimwidth($s['description'] ?? '', 0, 60, '…')) ?></td>
                    <td><span class="badge" style="background:var(--purple-light);color:var(--purple);"><?= htmlspecialchars($s['level']) ?></span></td>
                    <td>
                        <div class="table-progress">
                            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= (int)$s['progress'] ?>%;background:var(--purple);"></div></div>
                            <span style="font-size:0.78rem;color:var(--muted);"><?= (int)$s['progress'] ?>%</span>
                        </div>
                    </td>
                    <td><span class="badge badge-<?= strtolower(str_replace(' ','',$s['status'])) ?>"><?= $s['status'] ?></span></td>
                    <td style="font-size:0.8rem;">
                        <?php if ($nm): ?>
                            <?= htmlspecialchars($nm['milestone_name']) ?>
                            <?php if ($nm['milestone_date']): ?><div class="list-item-meta"><?= date('M j, Y', strtotime($nm['milestone_date'])) ?></div><?php endif; ?>
                        <?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?>
                    </td>
                    <td>
                        <a href="skill_plan.php?id=<?= $s['id'] ?>" style="color:var(--purple);font-size:0.85rem;font-weight:600;margin-right:8px;">Plan</a>
                        <a href="javascript:void(0)" onclick="openModal('edit<?= $s['id'] ?>')" style="color:var(--purple);font-size:0.85rem;font-weight:600;margin-right:8px;">Edit</a>
                        <a href="actions/learning_actions.php?action=delete_skill&id=<?= $s['id'] ?>&tab=skills" class="confirm-delete" style="color:var(--red);">✕</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>

        <?php foreach ($skills as $s): ?>
        <div class="modal-overlay" id="edit<?= $s['id'] ?>">
            <div class="modal-box">
                <span class="close-modal" onclick="closeModal('edit<?= $s['id'] ?>')">&times;</span>
                <h3><?= htmlspecialchars($s['skill_name']) ?></h3>
                <form method="POST" action="actions/learning_actions.php">
                    <input type="hidden" name="action" value="update_skill_notes">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="tab" value="skills">
                    <div class="form-group"><label>Description</label><textarea name="description" rows="2"><?= htmlspecialchars($s['description']) ?></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="<?= (int)$s['progress'] ?>"></div>
                        <div class="form-group"><label>Level</label>
                            <select name="level"><?php foreach (['Beginner','Intermediate','Advanced'] as $l): ?><option <?= $s['level']===$l?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select>
                        </div>
                    </div>
                    <div class="form-group"><label>Status</label>
                        <select name="status"><?php foreach (['Not Started','Learning','Mastered'] as $st): ?><option <?= $s['status']===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select>
                    </div>
                    <button type="submit" class="btn btn-block">Save Changes</button>
                </form>
                <a href="skill_plan.php?id=<?= $s['id'] ?>" class="btn btn-small btn-secondary" style="margin-top:10px;display:inline-block;">Open Learning Plan &rarr;</a>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="modal-overlay" id="addModal">
            <div class="modal-box">
                <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
                <h3>Add Skill</h3>
                <form method="POST" action="actions/learning_actions.php">
                    <input type="hidden" name="action" value="add_skill">
                    <input type="hidden" name="tab" value="skills">
                    <div class="form-group"><label>Skill Name</label><input type="text" name="skill_name" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="0"></div>
                        <div class="form-group"><label>Level</label>
                            <select name="level"><option selected>Beginner</option><option>Intermediate</option><option>Advanced</option></select>
                        </div>
                    </div>
                    <div class="form-group"><label>Status</label>
                        <select name="status"><option selected>Not Started</option><option>Learning</option><option>Mastered</option></select>
                    </div>
                    <button type="submit" class="btn btn-block">Save Skill</button>
                </form>
            </div>
        </div>
    </div>

    <div class="content-side">
        <div class="card">
            <div class="card-header"><h3>📈 Learning Progress Overview</h3></div>
            <?php if (!$skills): ?><div class="empty-state">Nothing to show yet.</div><?php endif; ?>
            <?php foreach ($skills as $s): ?>
                <div class="list-item">
                    <div class="list-item-top"><span class="list-item-title"><?= htmlspecialchars($s['skill_name']) ?></span><span style="font-size:0.78rem;color:var(--muted);font-weight:700;"><?= (int)$s['progress'] ?>%</span></div>
                    <div class="progress-bar-wrap" style="margin-top:4px;"><div class="progress-bar-fill" style="width:<?= (int)$s['progress'] ?>%;background:var(--purple);"></div></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header"><h3>🚩 Upcoming Learning Milestones</h3></div>
            <?php if (!$upcomingMilestones): ?><div class="empty-state">No milestones set yet.</div><?php endif; ?>
            <?php foreach ($upcomingMilestones as $m): ?>
                <div class="list-item">
                    <div class="list-item-title"><?= htmlspecialchars($m['milestone_name']) ?></div>
                    <div class="list-item-meta"><?= htmlspecialchars($skillNameById[$m['skill_id']] ?? '') ?><?= $m['milestone_date'] ? ' · ' . date('M j', strtotime($m['milestone_date'])) : '' ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="quote-card">
            <p>"Invest in your learning.<br>It will give you the best returns."</p>
        </div>
    </div>
    </div>

<?php elseif ($tab === 'plan'): ?>

    <div class="item-list">
        <?php if (!$skills): ?><div class="empty-state">Add a skill first, then build its learning plan.</div><?php endif; ?>
        <?php foreach ($skills as $s): ?>
            <div class="item-card">
                <h4><?= htmlspecialchars($s['skill_name']) ?></h4>
                <div class="meta"><?= htmlspecialchars($s['level']) ?> · <?= (int)$s['progress'] ?>% complete</div>
                <div class="progress-bar-wrap" style="margin-top:8px;"><div class="progress-bar-fill" style="width:<?= (int)$s['progress'] ?>%;background:var(--purple);"></div></div>
                <div class="item-actions">
                    <a href="skill_plan.php?id=<?= $s['id'] ?>" class="btn btn-small">View Plan</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php elseif ($tab === 'resources'): ?>

    <div class="item-list">
        <?php if (!$resourceRows): ?><div class="empty-state">No resources yet.</div><?php endif; ?>
        <?php foreach ($resourceRows as $r): ?>
            <div class="item-card">
                <div style="display:flex;align-items:flex-start;gap:10px;">
                    <form method="POST" action="actions/learning_actions.php">
                        <input type="hidden" name="action" value="toggle_resource">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="tab" value="resources">
                        <input type="hidden" name="status" value="<?= $r['status']==='Completed'?'Not Started':'Completed' ?>">
                        <button type="submit" class="task-check <?= $r['status']==='Completed'?'done':'' ?>"><?= $r['status']==='Completed'?'✓':'' ?></button>
                    </form>
                    <h4 class="<?= $r['status']==='Completed'?'task-title done':'' ?>" style="flex:1;"><?= htmlspecialchars($r['title']) ?></h4>
                </div>
                <?php if ($r['category']): ?><div class="meta"><?= htmlspecialchars($r['category']) ?></div><?php endif; ?>
                <div class="meta"><?= htmlspecialchars($r['resource_type']) ?> · <?= htmlspecialchars($r['estimated_time']) ?></div>
                <span class="badge badge-<?= strtolower($r['difficulty']) ?>"><?= $r['difficulty'] ?></span>
                <span class="badge badge-<?= strtolower(str_replace(' ','',$r['status'])) ?>"><?= $r['status'] ?></span>
                <div class="item-actions">
                    <?php if (!empty($r['link'])): ?><a href="<?= htmlspecialchars($r['link']) ?>" target="_blank" rel="noopener" class="btn btn-small btn-secondary">🔗 Open Link</a><?php endif; ?>
                    <a href="actions/learning_actions.php?action=delete_resource&id=<?= $r['id'] ?>&tab=resources" class="btn btn-small btn-danger confirm-delete">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="modal-overlay" id="addResourceModal">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('addResourceModal')">&times;</span>
            <h3>Add Resource</h3>
            <form method="POST" action="actions/learning_actions.php">
                <input type="hidden" name="action" value="add_resource">
                <input type="hidden" name="tab" value="resources">
                <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
                <div class="form-group"><label>Link (URL)</label><input type="url" name="link" placeholder="https://..."></div>
                <div class="form-group"><label>Category</label><input type="text" name="category" placeholder="e.g. YouTube, Udemy"></div>
                <div class="form-row">
                    <div class="form-group"><label>Type</label>
                        <select name="resource_type"><option>Website</option><option>Course</option><option>Book</option><option>Video</option><option>Documentation</option></select>
                    </div>
                    <div class="form-group"><label>Difficulty</label>
                        <select name="difficulty"><option selected>Beginner</option><option>Intermediate</option><option>Advanced</option></select>
                    </div>
                </div>
                <div class="form-group"><label>Estimated Time</label><input type="text" name="estimated_time" placeholder="e.g. 2 Hours"></div>
                <div class="form-group"><label>Status</label>
                    <select name="status"><option selected>Not Started</option><option>In Progress</option><option>Completed</option></select>
                </div>
                <button type="submit" class="btn btn-block">Save Resource</button>
            </form>
        </div>
    </div>

<?php else: /* notes */ ?>

    <div class="item-list">
        <?php if (!$skills): ?><div class="empty-state">No skills yet — add one first to keep notes on it.</div><?php endif; ?>
        <?php foreach ($skills as $s): ?>
            <div class="item-card">
                <h4><?= htmlspecialchars($s['skill_name']) ?></h4>
                <form method="POST" action="actions/skill_plan_actions.php">
                    <input type="hidden" name="action" value="update_notes">
                    <input type="hidden" name="skill_id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="plan_tab" value="notes">
                    <textarea name="notes" rows="4" placeholder="Notes for this skill..." style="width:100%;margin-top:8px;padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:0.85rem;font-family:inherit;"><?= htmlspecialchars($s['notes']) ?></textarea>
                    <button type="submit" class="btn btn-small" style="margin-top:8px;">Save Notes</button>
                    <a href="skill_plan.php?id=<?= $s['id'] ?>&tab=notes" style="margin-left:8px;font-size:0.8rem;color:var(--purple);font-weight:600;">Open full view &rarr;</a>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>