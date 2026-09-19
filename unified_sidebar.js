/**
 * Unified Sidebar Component
 * Single source of truth for all navigation across the application
 * Replaces multiple sidebar implementations with one consistent system
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
    if (!subsystemData) {
      console.warn('Subsystem data not found for:', this.subsystem);
      return;
    }

    const navContainer = document.getElementById('sidebar-subsystem-modules-nav');
    if (!navContainer) {
      console.warn('Sidebar navigation container not found');
      return;
    }

    navContainer.innerHTML = this.generateModuleHTML(subsystemData.modules);
  }

  generateModuleHTML(modules) {
    if (!modules || !Array.isArray(modules)) {
      return '';
    }

    return modules.map(module => {
      const isOpen = this.isModuleOpen(module);
      const isActive = this.isModuleActive(module);
      
      return `
        <div class="sidebar-module-group ${isOpen ? 'open' : ''}" data-module-id="${module.id}">
          <button type="button" class="sidebar-module-toggle w-full flex items-center justify-between px-3 py-2 rounded-xl border border-transparent text-slate-700 hover:bg-slate-100 font-medium text-xs transition ${isActive ? 'bg-indigo-50 text-indigo-700 font-semibold' : ''}" aria-expanded="${isOpen}">
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
    if (!module.subnav || !Array.isArray(module.subnav)) {
      return false;
    }
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
    // Update module groups and subitems
    document.querySelectorAll('.sidebar-module-group').forEach(group => {
      const isOpen = this.isModuleOpenFromDOM(group);
      group.classList.toggle('open', isOpen);
      
      const toggle = group.querySelector('.sidebar-module-toggle');
      const submenu = group.querySelector('.sidebar-submenu');
      const chevron = group.querySelector('.sidebar-chevron');
      
      if (toggle) {
        toggle.setAttribute('aria-expanded', isOpen);
        toggle.classList.toggle('bg-indigo-50', isOpen);
        toggle.classList.toggle('text-indigo-700', isOpen);
        toggle.classList.toggle('font-semibold', isOpen);
      }
      
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

  // Public method to refresh sidebar (useful for dynamic content)
  refresh() {
    this.currentPath = this.getCurrentPath();
    this.renderSidebar();
    this.updateActiveState();
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