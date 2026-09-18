# Sidebar Standardization Plan

## Current Problem Analysis

### Multiple Sidebar Implementations Found:

1. **sidebar.php** - PHP component with hardcoded navigation structure
   - Contains hardcoded `$nav_groups` array
   - Server-side rendering with PHP
   - Used by PHP pages (orders.php, etc.)

2. **dashboard.html** - HTML page with inline sidebar 
   - Uses JavaScript rendering via `subsystems.js`
   - Dynamic module loading
   - Different visual implementation

3. **module.html** - HTML page with inline sidebar
   - Also uses JavaScript rendering via `subsystems.js`
   - Slightly different styling and behavior

4. **subsystems.js** - JavaScript navigation registry
   - Contains `subsystemsData` object with modules
   - Acts as data source for HTML pages
   - Not used by PHP sidebar.php

### Issues Caused:

- **Multiple sources of truth**: Navigation data exists in both PHP and JavaScript
- **Inconsistent styling**: Different CSS classes and visual treatments
- **Different active state logic**: PHP uses file detection, JS uses URL parameters
- **Drifting behavior**: Collapse/expand behavior differs between implementations
- **Maintenance nightmare**: Changes need to be made in multiple places

## Recommended Solution: Unified JavaScript-Based Navigation

### Rationale:

1. **subsystems.js is already comprehensive** - Contains complete module structure
2. **Single source of truth** - One data structure for all navigation
3. **Consistent behavior** - Same JavaScript logic across all pages
4. **Easier maintenance** - Update navigation in one place
5. **Better for SPA-like experience** - Dynamic, responsive navigation

### Implementation Plan:

#### Phase 1: Create Unified Sidebar Component

**File: `unified_sidebar.js`**
```javascript
/**
 * Unified Sidebar Component
 * Single source of truth for all navigation across the application
 */

class UnifiedSidebar {
  constructor() {
    this.currentPath = this.getCurrentPath();
    this.currentModule = this.getCurrentModule();
    this.subsystem = this.getSubsystemFromUrl();
    this.init();
  }

  getCurrentPath() {
    return window.location.pathname.split('/').pop() || 'dashboard.html';
  }

  getCurrentModule() {
    return this.currentPath.replace('.php', '').replace('.html', '');
  }

  getSubsystemFromUrl() {
    const params = new URLSearchParams(window.location.search);
    return params.get('subsystem') || 'supply-chain';
  }

  init() {
    this.renderSidebar();
    this.setupEventListeners();
    this.updateActiveState();
  }

  renderSidebar() {
    const subsystemData = window.subsystemsData?.[this.subsystem] || window.subsystemsData?.['supply-chain'];
    if (!subsystemData) return;

    const navContainer = document.getElementById('sidebar-subsystem-modules-nav');
    if (!navContainer) return;

    navContainer.innerHTML = this.generateModuleHTML(subsystemData.modules);
  }

  generateModuleHTML(modules) {
    return modules.map(module => {
      const isOpen = this.isModuleOpen(module);
      const isActive = this.isModuleActive(module);
      
      return `
        <div class="sidebar-module-group ${isOpen ? 'open' : ''}" data-module-id="${module.id}">
          <button type="button" class="sidebar-module-toggle" aria-expanded="${isOpen}">
            <span class="flex items-center gap-2.5 truncate">
              <span class="material-symbols-outlined text-[18px] text-indigo-600">${module.icon}</span>
              <span class="truncate">${module.name}</span>
            </span>
            <span class="material-symbols-outlined sidebar-chevron text-[16px] text-slate-400 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}">expand_more</span>
          </button>
          <div class="sidebar-submenu pl-4 pr-1 py-1 space-y-1" style="${isOpen ? 'max-height: 500px; display: block;' : 'max-height: 0px; display: none;'}">
            ${module.subnav.map(item => this.generateSubmenuItem(item, module)).join('')}
          </div>
        </div>
      `;
    }).join('');
  }

  generateSubmenuItem(item, module) {
    const isActive = this.isSubitemActive(item);
    return `
      <a href="${item.href}" 
         class="sidebar-submenu-link flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs transition ${isActive ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'}"
         data-subitem-id="${item.id}">
        <span class="material-symbols-outlined sidebar-submenu-icon text-[16px] ${isActive ? 'text-white' : 'text-slate-400'}">${item.icon}</span>
        <span class="truncate">${item.label}</span>
      </a>
    `;
  }

  isModuleOpen(module) {
    // Check if any subitem is currently active
    return module.subnav.some(item => this.isSubitemActive(item));
  }

  isModuleActive(module) {
    return this.isModuleOpen(module);
  }

  isSubitemActive(item) {
    // Check if current path matches the subitem href
    const currentPath = this.getCurrentPath();
    return currentPath === item.href || currentPath === item.href.replace('.php', '');
  }

  updateActiveState() {
    // Update dashboard link
    const dashboardLink = document.getElementById('sidebar-dashboard-link');
    if (dashboardLink) {
      const isDashboardActive = this.currentPath === 'dashboard.html' || this.currentPath === 'dashboard.php';
      dashboardLink.classList.toggle('active', isDashboardActive);
    }

    // Update module groups and subitems
    document.querySelectorAll('.sidebar-module-group').forEach(group => {
      const isOpen = this.isModuleOpenFromDOM(group);
      group.classList.toggle('open', isOpen);
      
      const toggle = group.querySelector('.sidebar-module-toggle');
      const submenu = group.querySelector('.sidebar-submenu');
      const chevron = group.querySelector('.sidebar-chevron');
      
      if (toggle) toggle.setAttribute('aria-expanded', isOpen);
      if (submenu) {
        submenu.style.display = isOpen ? 'block' : 'none';
        submenu.style.maxHeight = isOpen ? '500px' : '0px';
      }
      if (chevron) chevron.classList.toggle('rotate-180', isOpen);
    });

    // Update subitem active states
    document.querySelectorAll('.sidebar-submenu-link').forEach(link => {
      const href = link.getAttribute('href');
      const isActive = this.currentPath === href || this.currentPath === href.replace('.php', '');
      link.classList.toggle('bg-indigo-600', isActive);
      link.classList.toggle('text-white', isActive);
      link.classList.toggle('font-semibold', isActive);
      link.classList.toggle('shadow-xs', isActive);
      link.classList.toggle('text-slate-600', !isActive);
      link.classList.toggle('hover:bg-slate-100', !isActive);
      link.classList.toggle('hover:text-slate-900', !isActive);
      
      const icon = link.querySelector('.sidebar-submenu-icon');
      if (icon) {
        icon.classList.toggle('text-white', isActive);
        icon.classList.toggle('text-slate-400', !isActive);
      }
    });
  }

  isModuleOpenFromDOM(group) {
    const subitems = group.querySelectorAll('.sidebar-submenu-link');
    return Array.from(subitems).some(link => {
      const href = link.getAttribute('href');
      return this.currentPath === href || this.currentPath === href.replace('.php', '');
    });
  }

  setupEventListeners() {
    // Module toggle functionality
    document.querySelectorAll('.sidebar-module-toggle').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const parentGroup = btn.closest('.sidebar-module-group');
        if (!parentGroup) return;

        const isOpen = parentGroup.classList.toggle('open');
        const submenu = parentGroup.querySelector('.sidebar-submenu');
        const chevron = btn.querySelector('.sidebar-chevron');
        
        if (submenu) {
          submenu.style.display = isOpen ? 'block' : 'none';
          submenu.style.maxHeight = isOpen ? '500px' : '0px';
        }
        if (chevron) chevron.classList.toggle('rotate-180', isOpen);
        btn.setAttribute('aria-expanded', isOpen);
      });
    });

    // Listen for navigation changes
    window.addEventListener('popstate', () => {
      this.currentPath = this.getCurrentPath();
      this.updateActiveState();
    });
  }
}

// Initialize unified sidebar when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.unifiedSidebar = new UnifiedSidebar();
  });
} else {
  window.unifiedSidebar = new UnifiedSidebar();
}
```

#### Phase 2: Create Shared Layout Template

**File: `shared_layout.php`**
```php
<?php
// shared_layout.php - Unified layout wrapper for all pages
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
    <title><?php echo htmlspecialchars($page_title ?? 'Supply Chain Management'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <?php if (isset($additional_head)) echo $additional_head; ?>
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
            <?php if (isset($flash_message)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg mb-4">
                    ✅ <?php echo htmlspecialchars($flash_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg mb-4">
                    ⚠️ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php echo $content ?? ''; ?>
        </main>
    </div>
</body>
</html>
```

#### Phase 3: Update Existing Pages

**Update PHP pages to use shared layout:**
```php
<?php
$page_title = "Purchase Order Management";
require_once 'shared_layout.php';

$content = '
    <div class="w-full max-w-7xl mx-auto space-y-6 sm:space-y-8 min-w-0">
        <!-- Page content here -->
    </div>
';

echo $content;
?>
```

#### Phase 4: Standardize HTML Pages

**Update dashboard.html and module.html to use unified sidebar:**
- Remove inline sidebar markup
- Include `unified_sidebar.js`
- Use shared sidebar container structure
- Ensure consistent CSS classes

#### Phase 5: Clean Up Legacy Files

**Files to remove or archive:**
1. `forgotpass_handler.tmp` - Temporary file (delete)
2. `sidebar.php` - Legacy PHP sidebar (archive or remove after migration)
3. Duplicate sidebar markup in `dashboard.html` and `module.html`

**Create archive directory:**
```bash
mkdir -p archive/legacy_navigation
mv sidebar.php archive/legacy_navigation/
mv forgotpass_handler.tmp archive/
```

## Implementation Steps:

1. **Create unified_sidebar.js** with the component above
2. **Create shared_layout.php** for PHP pages
3. **Update dashboard.html** to use unified sidebar
4. **Update module.html** to use unified sidebar  
5. **Migrate PHP pages** to use shared_layout.php gradually
6. **Test navigation** across all pages
7. **Remove legacy files** once migration is complete
8. **Update subsystems.js** if navigation structure changes

## Benefits:

- **Single source of truth** for navigation data (subsystems.js)
- **Consistent behavior** across all pages
- **Easier maintenance** - update in one place
- **Better UX** - unified navigation experience
- **Cleaner codebase** - remove duplicate implementations
- **Scalability** - easier to add new modules

## Testing Checklist:

- [ ] Navigation works on dashboard.html
- [ ] Navigation works on module.html
- [ ] Navigation works on PHP pages (orders.php, etc.)
- [ ] Active states highlight correctly
- [ ] Mobile collapse/expand works consistently
- [ ] Module groups open/close properly
- [ ] URL parameters work correctly
- [ ] No JavaScript errors in console
- [ ] Responsive design works on all screen sizes

## Timeline Estimate:

- Phase 1: Create unified component (2-3 hours)
- Phase 2: Create shared layout (1-2 hours)
- Phase 3: Update PHP pages (3-4 hours)
- Phase 4: Standardize HTML pages (1-2 hours)
- Phase 5: Clean up legacy files (1 hour)
- Testing and refinement (2-3 hours)

**Total: 10-15 hours of development time**

Would you like me to proceed with implementing this plan, or would you prefer to modify the approach first?
