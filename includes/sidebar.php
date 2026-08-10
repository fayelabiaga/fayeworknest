<?php
$current = basename($_SERVER['PHP_SELF']);
function navClass($files, $current) {
    $files = (array)$files;
    return in_array($current, $files) ? 'nav-link active' : 'nav-link';
}
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <span class="bird">🕊️</span>
        <span class="brand">Faye<br>WorkNest</span>
    </div>
    <div class="sidebar-tagline">Organize. Learn. Achieve. 💜</div>
    <nav>
        <a href="dashboard.php" class="<?= navClass('dashboard.php', $current) ?>"><span class="ic">🏠</span> Dashboard</a>
        <a href="school_works.php" class="<?= navClass('school_works.php', $current) ?>"><span class="ic">🎓</span> School Works</a>
        <a href="research_works.php" class="<?= navClass('research_works.php', $current) ?>"><span class="ic">🔬</span> Research Works</a>
        <a href="personal_projects.php" class="<?= navClass(['personal_projects.php','project_view.php','project_goals.php'], $current) ?>"><span class="ic">💼</span> Projects</a>
        <a href="learning.php" class="<?= navClass('learning.php', $current) ?>"><span class="ic">📖</span> Learning Studies</a>
        <a href="calendar.php" class="<?= navClass('calendar.php', $current) ?>"><span class="ic">📅</span> Calendar</a>
        <a href="habits.php" class="<?= navClass('habits.php', $current) ?>"><span class="ic">🌿</span> Habits to Change</a>
    </nav>
    <div class="sidebar-tip">
        <span class="tip-icon">🌱</span>
        Small steps every day lead to big achievements.
        <div class="tip-sub">— Faye WorkNest</div>
    </div>
</aside>
<div class="main-content-wrap">
    <header class="topbar">
        <form class="topbar-search" action="search.php" method="get">
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" placeholder="Search tasks, projects, skills...">
            </div>
        </form>
        <div class="topbar-actions">
            <a href="notifications.php" class="icon-btn" title="Notifications">
                🔔
                <?php if ($unread_count > 0): ?>
                    <span class="notif-badge"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>
            <button type="button" class="icon-btn" id="themeToggle" title="Toggle theme">🌙</button>
            <a href="profile.php" class="topbar-avatar" title="Profile">
                <?= strtoupper(substr($current_user_name ?? 'U', 0, 1)) ?>
            </a>
        </div>
    </header>
    <main class="main-content">
