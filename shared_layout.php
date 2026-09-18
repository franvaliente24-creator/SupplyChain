<?php
/**
 * shared_layout.php - Unified layout wrapper for all PHP pages
 * Provides consistent sidebar, header, and layout structure
 */

// Load centralized session configuration
require_once __DIR__ . '/session_config.php';

// Check authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Check session timeout
if (!checkSessionTimeout()) {
    header("Location: index.html");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_title ?? 'Supply Chain Management';
$flash_message = $flash_message ?? null;
$error_message = $error_message ?? null;
$additional_head = $additional_head ?? '';
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <link href="app.css" rel="stylesheet"/>
    <script src="subsystems.js" defer></script>
    <script src="unified_sidebar.js" defer></script>
    <script src="session-profile.js" defer></script>
    <script src="app.js" defer></script>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <?php echo $additional_head; ?>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">
    <!-- Mobile Overlay Backdrop -->
    <div id="sidebar-backdrop" class="fixed top-16 bottom-0 left-0 right-0 md:inset-0 bg-gray-900/50 backdrop-blur-md z-40 hidden md:hidden transition-opacity duration-300 opacity-0"></div>

    <!-- Unified Sidebar -->
    <aside id="app-sidebar" class="sidebar w-72 bg-surface border-r border-outline-variant/30 flex flex-col shrink-0 transition-all duration-300 relative overflow-visible">
        <div id="sidebar-resize-handle" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-primary/20 z-40"></div>
        <nav class="flex-1 flex flex-col overflow-y-auto overflow-x-hidden">
            <div class="sidebar-brand-section">
                <div class="sidebar-brand-card">
                    <div class="sidebar-brand-icon w-14 h-14 rounded-xl flex items-center justify-center shrink-0 overflow-hidden">
                        <img src="img/logo.png" alt="Supply Chain Logo" class="w-full h-full object-cover"/>
                    </div>

                    <div class="sidebar-brand-title" id="sidebar-brand-title">
                        Supply Chain Management System
                    </div>

                    <div class="sidebar-brand-subtitle" id="sidebar-brand-category">
                        Logistics & Inventory Operations
                    </div>
                </div>
            </div>

            <!-- Dashboard Link -->
            <a id="sidebar-dashboard-link" class="sidebar-main-link flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 font-medium text-xs transition <?php echo ($current_page === 'dashboard.html' || $current_page === 'dashboard.php') ? 'bg-indigo-50 text-indigo-700 font-semibold' : ''; ?>" href="dashboard.html?subsystem=supply-chain">
                <span class="material-symbols-outlined text-indigo-600 text-[18px]">dashboard</span>
                <span>Dashboard</span>
            </a>

            <!-- Module Navigation Container -->
            <div id="sidebar-subsystem-nav-panel" class="sidebar-subsystem-nav-panel">
                <nav id="sidebar-subsystem-modules-nav" class="sidebar-subsystem-modules"></nav>
            </div>
        </nav>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <?php include 'header.php'; ?>
        
        <main class="flex-1 overflow-y-auto bg-surface-dim p-3 sm:p-6 md:p-10 text-on-surface antialiased overflow-x-hidden w-full max-w-full">
            <?php if ($flash_message): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg mb-4">
                    ✅ <?php echo htmlspecialchars($flash_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg mb-4">
                    ⚠️ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php echo $content ?? ''; ?>
        </main>
    </div>
</body>
</html>