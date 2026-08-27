<?php
// Shared topbar header — keeps every real supply-chain page visually
// identical to dashboard.html / module.html (logo, sidebar toggle,
// notifications, profile dropdown, logout).
//
// Relies on $admin_user / $user_role already being set by the including
// page (same pattern sidebar.php uses); falls back to session data.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$header_admin_user = $admin_user ?? ($_SESSION['username'] ?? 'Admin User');
$header_user_role  = $user_role ?? ($_SESSION['role'] ?? 'Supply Chain Manager');
$header_email      = $_SESSION['email'] ?? 'admin@example.com';

$header_initials = strtoupper(substr(trim($header_admin_user), 0, 1));
$header_name_parts = preg_split('/[\s._-]+/', trim($header_admin_user));
if (count($header_name_parts) > 1 && $header_name_parts[0] !== '' && $header_name_parts[1] !== '') {
    $header_initials = strtoupper(substr($header_name_parts[0], 0, 1) . substr($header_name_parts[1], 0, 1));
} elseif ($header_admin_user !== '') {
    $header_initials = strtoupper(substr($header_admin_user, 0, 2));
}
?>
<header class="bg-surface/80 backdrop-blur-md shadow-sm border-b border-outline-variant/30 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
    <div class="flex items-center gap-3">
        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%23f1f5f9'/%3E%3Cpath d='M10 16a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm12 6H10l4-5 2.5 3 2-2.5 3.5 4.5z' fill='%23cbd5e1'/%3E%3C/svg%3E" alt="Supply Chain Logo" class="w-8 h-8 rounded-lg object-cover border border-outline-variant/30 shadow-2xs shrink-0">
        <button id="desktop-sidebar-toggle" type="button" class="flex items-center justify-center p-2 rounded-xl text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low focus:outline-none transition border border-outline-variant/30 shrink-0 min-w-[2.5rem] min-h-[2.5rem]" title="Close Sidebar">
            <span class="material-symbols-outlined text-xl" id="sidebar-toggle-icon">menu_open</span>
        </button>
    </div>

    <div class="relative flex items-center gap-2">
        <button type="button" class="p-2 rounded-full hover:bg-surface-container-low transition-colors focus:outline-none border border-transparent hover:border-outline-variant/30 cursor-pointer" title="Notifications">
            <span class="material-symbols-outlined text-on-surface-variant text-xl">notifications</span>
        </button>
        <button id="profile-dropdown-toggle" type="button" class="flex items-center gap-3 p-1.5 rounded-full hover:bg-surface-container-low transition-colors focus:outline-none border border-transparent hover:border-outline-variant/30 cursor-pointer" aria-expanded="false" title="Account Menu">
            <div class="w-9 h-9 rounded-full bg-primary-container/30 border border-primary/20 flex items-center justify-center text-primary font-bold text-sm shadow-2xs shrink-0 overflow-hidden">
                <img src="/SupplyChain/Image/profile.jpg" alt="User profile" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                <span style="display:none;" class="w-full h-full items-center justify-center"><?php echo htmlspecialchars($header_initials); ?></span>
            </div>
            <span class="material-symbols-outlined text-on-surface-variant text-sm hidden sm:inline-block">expand_more</span>
        </button>

        <div id="profile-dropdown-menu" class="absolute right-0 top-14 w-60 bg-surface rounded-2xl shadow-xl border border-outline-variant/30 py-2 z-50 transition-all duration-200 hidden opacity-0 scale-95 origin-top-right">
            <div class="px-4 py-3 border-b border-outline-variant/20 md:hidden">
                <p class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($header_admin_user); ?></p>
                <p class="text-xs text-on-surface-variant truncate"><?php echo htmlspecialchars($header_email); ?></p>
            </div>
            <div class="px-4 py-2 hidden md:block">
                <p class="text-xs font-medium text-on-surface-variant uppercase tracking-wider">Account</p>
            </div>
            <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container-low hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-lg text-on-surface-variant">person</span>
                <span class="font-medium">My Profile</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container-low hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-lg text-on-surface-variant">manage_accounts</span>
                <span class="font-medium">Settings</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container-low hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-lg text-on-surface-variant">notifications</span>
                <span class="font-medium">Notifications</span>
            </a>
            <div class="my-1 border-t border-outline-variant/20"></div>
            <a href="#" onclick="openDashboardModal('dash-logout-modal'); return false;" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors font-medium cursor-pointer">
                <span class="material-symbols-outlined text-lg">logout</span>
                <span>Log Out</span>
            </a>
        </div>
    </div>
</header>

<div id="dash-logout-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200" onclick="if(event.target.id==='dash-logout-modal') closeDashboardModal('dash-logout-modal')">
    <div class="bg-surface rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-outline-variant/30 relative transform scale-95 transition-transform duration-200 text-center" onclick="event.stopPropagation()">
        <button type="button" onclick="closeDashboardModal('dash-logout-modal')" class="absolute top-4 right-4 p-1.5 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors" title="Close modal">
            <span class="material-symbols-outlined text-xl">close</span>
        </button>
        <div class="w-14 h-14 shrink-0 rounded-full bg-surface-container-high border border-outline-variant/40 flex items-center justify-center text-on-surface mx-auto mb-4">
            <span class="material-symbols-outlined text-3xl text-on-surface-variant">logout</span>
        </div>
        <h3 class="text-xl font-headline font-bold text-on-surface mb-2">Sign Out of Session?</h3>
        <p class="text-sm text-on-surface-variant leading-relaxed mb-6">Are you sure you want to log out?</p>
        <div class="flex gap-3">
            <button type="button" onclick="closeDashboardModal('dash-logout-modal')" class="flex-1 px-4 py-2.5 rounded-xl border border-outline-variant/50 text-on-surface font-medium text-sm hover:bg-surface-container-low transition-colors cursor-pointer">Cancel</button>
            <button type="button" onclick="performLogout()" class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm shadow-md shadow-red-600/20 transition-all cursor-pointer">Sign Out</button>
        </div>
    </div>
</div>

<div id="dash-toast-container" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

<script>
    function openDashboardModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.remove('hidden', 'opacity-0');
        modal.classList.add('opacity-100');
        const box = modal.firstElementChild;
        if (box) {
            box.classList.remove('scale-95');
            box.classList.add('scale-100');
        }
        document.body.style.overflow = 'hidden';
    }

    function closeDashboardModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        const box = modal.firstElementChild;
        if (box) {
            box.classList.remove('scale-100');
            box.classList.add('scale-95');
        }
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 200);
    }

    function confirmDashboardAction(toastMessage) {
        const container = document.getElementById('dash-toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'bg-surface text-on-surface px-5 py-3.5 rounded-2xl shadow-xl border border-outline-variant/40 flex items-center gap-3 transform translate-y-4 opacity-0 transition-all duration-300 pointer-events-auto';
        toast.innerHTML = `
            <div class="w-7 h-7 rounded-full bg-green-100 text-green-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-sm font-bold">check</span>
            </div>
            <span class="text-sm font-medium">${toastMessage}</span>
        `;
        container.appendChild(toast);
        void toast.offsetWidth;
        toast.classList.remove('translate-y-4', 'opacity-0');
        setTimeout(() => {
            toast.classList.add('translate-y-4', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    async function performLogout() {
        closeDashboardModal('dash-logout-modal');
        try {
            await fetch('logout.php', { method: 'POST', credentials: 'same-origin' });
        } catch (e) {
            console.warn('Logout server call skipped:', e);
        }
        window.location.replace('index.html');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id$="-modal"]').forEach(modal => {
                if (!modal.classList.contains('hidden')) {
                    closeDashboardModal(modal.id);
                }
            });
        }
    });
</script>