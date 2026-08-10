<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Habits to Change";
$today = date('Y-m-d');

// Monday-start current week
$mondayOffset = (int)date('N') - 1; // 0 if Monday
$weekStart = date('Y-m-d', strtotime("-$mondayOffset days", strtotime($today)));
$weekDates = [];
for ($i = 0; $i < 7; $i++) {
    $weekDates[] = date('Y-m-d', strtotime("+$i days", strtotime($weekStart)));
}

$stmt = $pdo->prepare("SELECT * FROM habits WHERE user_id=? ORDER BY created_at ASC");
$stmt->execute([$current_user_id]);
$habits = $stmt->fetchAll();

function getStreak($pdo, $habitId, $today) {
    $stmt = $pdo->prepare("SELECT log_date FROM habit_logs WHERE habit_id=? AND completed=1 ORDER BY log_date DESC");
    $stmt->execute([$habitId]);
    $dates = array_column($stmt->fetchAll(), 'log_date');
    $dateSet = array_flip($dates);
    $streak = 0;
    $cursor = $today;
    // If today isn't logged yet, start counting from yesterday (streak still "alive")
    if (!isset($dateSet[$cursor])) {
        $cursor = date('Y-m-d', strtotime('-1 day', strtotime($cursor)));
    }
    while (isset($dateSet[$cursor])) {
        $streak++;
        $cursor = date('Y-m-d', strtotime('-1 day', strtotime($cursor)));
    }
    return $streak;
}

$habitData = [];
$totalStreak = 0;
$weekCompletedCount = 0;
foreach ($habits as $h) {
    $stmt = $pdo->prepare("SELECT log_date FROM habit_logs WHERE habit_id=? AND completed=1 AND log_date BETWEEN ? AND ?");
    $stmt->execute([$h['id'], $weekStart, end($weekDates)]);
    $weekLogs = array_flip(array_column($stmt->fetchAll(), 'log_date'));
    $streak = getStreak($pdo, $h['id'], $today);
    $totalStreak = max($totalStreak, $streak);
    $weekCompletedCount += count($weekLogs);
    $habitData[] = [
        'habit' => $h,
        'streak' => $streak,
        'week' => $weekLogs,
        'today_done' => isset($weekLogs[$today]),
    ];
}
$activeHabits = count($habits);
$avgConsistency = $activeHabits > 0 ? round(($weekCompletedCount / ($activeHabits * 7)) * 100) : 0;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>🌿 Habits to Change</h1>
            <div class="sub">Build better habits for a better you.</div>
        </div>
        <button class="btn" onclick="openModal('addModal')">+ Add Habit</button>
    </div>
</div>

<div class="habit-grid">
    <div class="habit-stat"><div class="num"><?= $activeHabits ?></div><div class="lbl">Active Habits</div></div>
    <div class="habit-stat"><div class="num"><?= $totalStreak ?></div><div class="lbl">Days Streak (Best)</div></div>
    <div class="habit-stat"><div class="num"><?= $avgConsistency ?>%</div><div class="lbl">Average Consistency</div></div>
    <div class="quote-card" style="display:flex;align-items:center;justify-content:center;">
        <p>"Discipline today,<br>freedom tomorrow."</p>
    </div>
</div>

<div class="table-card">
    <?php if (!$habitData): ?>
        <div class="empty-state">No habits yet. Add the first one you'd like to build or change!</div>
    <?php else: ?>
    <table class="data-table">
        <tr>
            <th>Habit</th>
            <th>Why It Matters</th>
            <th>Streak</th>
            <th>Today</th>
            <th>Weekly Progress</th>
            <th></th>
        </tr>
        <?php foreach ($habitData as $hd): $h = $hd['habit']; ?>
        <tr>
            <td><?= htmlspecialchars($h['icon']) ?> <?= htmlspecialchars($h['habit_name']) ?></td>
            <td style="color:var(--muted);"><?= htmlspecialchars($h['why_it_matters']) ?></td>
            <td><?= $hd['streak'] ?> day<?= $hd['streak']==1?'':'s' ?></td>
            <td>
                <form method="POST" action="actions/habits_actions.php">
                    <input type="hidden" name="action" value="toggle_today">
                    <input type="hidden" name="habit_id" value="<?= $h['id'] ?>">
                    <button type="submit" class="habit-today-btn <?= $hd['today_done']?'done':'' ?>"><?= $hd['today_done']?'✓':'' ?></button>
                </form>
            </td>
            <td>
                <div class="week-dots">
                    <?php foreach (['M','T','W','T','F','S','S'] as $i => $label):
                        $d = $weekDates[$i];
                        $filled = isset($hd['week'][$d]);
                        $isToday = $d === $today;
                    ?>
                        <div class="week-dot <?= $filled?'filled':'' ?> <?= $isToday?'today-outline':'' ?>" title="<?= date('D, M j', strtotime($d)) ?>"><?= $filled?'✓':'' ?></div>
                    <?php endforeach; ?>
                </div>
            </td>
            <td><a href="javascript:void(0)" onclick="openModal('editHabit<?= $h['id'] ?>')" style="color:var(--purple);font-size:0.85rem;font-weight:600;margin-right:10px;">Edit</a><a href="actions/habits_actions.php?action=delete_habit&id=<?= $h['id'] ?>" class="confirm-delete" style="color:var(--red);">✕</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<?php foreach ($habitData as $hd): $h = $hd['habit']; ?>
<div class="modal-overlay" id="editHabit<?= $h['id'] ?>">
    <div class="modal-box">
        <span class="close-modal" onclick="closeModal('editHabit<?= $h['id'] ?>')">&times;</span>
        <h3>Edit Habit</h3>
        <form method="POST" action="actions/habits_actions.php">
            <input type="hidden" name="action" value="update_habit">
            <input type="hidden" name="id" value="<?= $h['id'] ?>">
            <div class="form-group"><label>Habit Name</label><input type="text" name="habit_name" value="<?= htmlspecialchars($h['habit_name']) ?>" required></div>
            <div class="form-group"><label>Why It Matters</label><input type="text" name="why_it_matters" value="<?= htmlspecialchars($h['why_it_matters']) ?>"></div>
            <div class="form-group">
                <label>Icon</label>
                <div class="emoji-picker">
                    <?php foreach (['💧','🏃','😴','📖','🙏','🧘','🚭','🥗','💪','📵','🛌','🎯','✍️','🚶','🧹','💰','⭐'] as $e): ?>
                        <label class="emoji-option">
                            <input type="radio" name="icon" value="<?= $e ?>" <?= $h['icon']===$e?'checked':'' ?>>
                            <?= $e ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-block" style="margin-top:12px;">Save Changes</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
        <h3>Add Habit</h3>
        <form method="POST" action="actions/habits_actions.php">
            <input type="hidden" name="action" value="add_habit">
            <div class="form-group"><label>Habit Name</label><input type="text" name="habit_name" required placeholder="e.g. Drink More Water"></div>
            <div class="form-group"><label>Why It Matters</label><input type="text" name="why_it_matters" placeholder="e.g. Stay hydrated for better body and mind."></div>
            <div class="form-group">
                <label>Icon</label>
                <div class="emoji-picker">
                    <?php foreach (['💧','🏃','😴','📖','🙏','🧘','🚭','🥗','💪','📵','🛌','🎯','✍️','🚶','🧹','💰','⭐'] as $i => $e): ?>
                        <label class="emoji-option">
                            <input type="radio" name="icon" value="<?= $e ?>" <?= $i===16?'checked':'' ?>>
                            <?= $e ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-block" style="margin-top:12px;">Save Habit</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>