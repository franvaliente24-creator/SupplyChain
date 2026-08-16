function renderModulePage() {
    const subsystemId = getSubsystemFromUrl() || 'supply-chain';
    const moduleId = getModuleFromUrl();
    const subsystem = getSubsystemById(subsystemId);
    const module = moduleId ? getModuleById(subsystemId, moduleId) : null;

    const sidebarBrandTitle = document.getElementById('sidebar-brand-title');
    const sidebarBrandCategory = document.getElementById('sidebar-brand-category');
    const sidebarSubsystemNavPanel = document.getElementById('sidebar-subsystem-nav-panel');
    const sidebarSubsystemModulesNav = document.getElementById('sidebar-subsystem-modules-nav');
    const sidebarDashboardLink = document.getElementById('sidebar-dashboard-link');
    const moduleContent = document.getElementById('module-content');

    // Guard against false redirects - only redirect if subsystems.js has loaded and module genuinely doesn't exist
    if (typeof SUBSYSTEMS === 'undefined' || !subsystem || (moduleId && !module) || !moduleContent) {
        // If we're on a module page but the module lookup failed, only redirect if we're confident
        // This prevents race conditions from causing false redirects
        if (typeof SUBSYSTEMS !== 'undefined' && subsystem && moduleId && !module) {
            // Module genuinely doesn't exist
            window.location.replace(getDashboardHref(subsystemId));
            return;
        }
        // If subsystems.js hasn't loaded yet, wait and try again
        if (typeof SUBSYSTEMS === 'undefined') {
            setTimeout(renderModulePage, 100);
            return;
        }
        // If we don't have a module ID (shouldn't happen on module.html), redirect
        if (!moduleId) {
            window.location.replace(getDashboardHref(subsystemId));
            return;
        }
    }

    document.title = `${module.name} — ${subsystem.title}`;

    if (sidebarBrandTitle) sidebarBrandTitle.textContent = subsystem.title;
    if (sidebarBrandCategory) sidebarBrandCategory.textContent = subsystem.category;

    // Ensure dashboard link is not active
    if (sidebarDashboardLink) {
        sidebarDashboardLink.href = getDashboardHref(subsystemId);
        sidebarDashboardLink.classList.remove('active');
    }

    // Get current view from URL params
    const params = new URLSearchParams(window.location.search);
    const currentView = params.get('view');

    // Render module links with correct active state
    if (sidebarSubsystemModulesNav && subsystem.modules) {
        sidebarSubsystemModulesNav.innerHTML = subsystem.modules.map((entry) => {
            const mod = normalizeModule(entry);
            const isActive = mod.id === moduleId;
            const hasSubnav = mod.subnav && mod.subnav.length > 0;
            
            if (hasSubnav) {
                // Module with submenu
                const isModuleOpen = isActive;
                const activeSubItemId = isActive ? currentView : null;
                const defaultViewId = mod.subnav[0]?.id;
                const defaultRender = mod.subnav[0]?.render;
                
                return `
                    <div class="sidebar-module-group ${isModuleOpen ? 'open' : ''}" data-module-id="${mod.id}">
                        <button type="button" class="sidebar-subsystem-link sidebar-module-toggle ${isActive ? 'active' : ''}" data-module="${mod.id}" data-default-render="${defaultRender}">
                            <span class="sidebar-subsystem-link-icon">
                                <span class="material-symbols-outlined">${getModuleIcon(mod.name)}</span>
                            </span>
                            <span class="truncate flex-1 text-left">${mod.name}</span>
                            <span class="material-symbols-outlined sidebar-chevron text-base">expand_more</span>
                        </button>
                        <div class="sidebar-submenu" data-submenu-for="${mod.id}">
                            ${mod.subnav.map(sub => {
                                const isSubActive = activeSubItemId === sub.id || (!activeSubItemId && sub.id === defaultViewId);
                                return `
                                    <a href="${getModuleHref(subsystemId, mod.id)}&view=${sub.id}" 
                                       class="sidebar-submenu-link ${isSubActive ? 'active' : ''}" 
                                       data-view="${sub.id}"
                                       data-render="${sub.render}">
                                        <span class="material-symbols-outlined sidebar-submenu-icon">${sub.icon}</span>
                                        <span class="truncate">${sub.label}</span>
                                    </a>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
            } else {
                // Plain module link (no submenu)
                return `
                    <a href="${getModuleHref(subsystemId, mod.id)}" class="sidebar-subsystem-link ${isActive ? 'active' : ''}">
                        <span class="material-symbols-outlined sidebar-subsystem-link-icon">${getModuleIcon(mod.name)}</span>
                        <span class="truncate">${mod.name}</span>
                    </a>
                `;
            }
        }).join('');
    }
    if (sidebarSubsystemNavPanel) sidebarSubsystemNavPanel.classList.remove('hidden');

    // Module-specific primary action button
    let primaryActionButton = '';
    if (moduleId === 'sws') {
        primaryActionButton = `
            <button id="module-primary-action" class="btn-primary dashboard-action-button m-0">
                <span class="material-symbols-outlined">qr_code_scanner</span>
                Scan Asset QR
            </button>
        `;
    } else if (moduleId === 'dtrs') {
        primaryActionButton = `
            <button id="module-primary-action" class="btn-primary dashboard-action-button m-0">
                <span class="material-symbols-outlined">local_shipping</span>
                Generate Waybill
            </button>
        `;
    }

    const moduleKPIs = getModuleKPIs(moduleId);

    moduleContent.innerHTML = `
        <div class="max-w-7xl mx-auto space-y-8">
            <div class="bg-surface-container-lowest rounded-xl sm:rounded-2xl border border-outline-variant/60 shadow-sm p-4 sm:px-6 sm:py-4.5 flex flex-col md:flex-row md:items-center justify-between gap-4 w-full max-w-full">
                <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm">
                    <a href="${getDashboardHref(subsystemId)}" class="text-primary hover:underline">${subsystem.title}</a>
                    <span class="text-on-surface-variant">/</span>
                    <span class="font-semibold text-on-surface">${module.name}</span>
                </nav>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 w-full">
                <div>
                    <h1 class="text-3xl font-headline font-bold text-on-surface">${module.name}</h1>
                </div>
                <div class="flex flex-wrap items-center justify-start sm:justify-end gap-3 shrink-0">
                    ${primaryActionButton}
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                ${moduleKPIs.map(kpi => `
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="material-symbols-outlined text-xl ${kpi.tone === 'positive' ? 'text-emerald-600' : kpi.tone === 'caution' ? 'text-amber-600' : kpi.tone === 'negative' ? 'text-red-600' : 'text-slate-600'}">${kpi.icon}</span>
                            ${kpi.trend ? `<span class="text-xs font-medium ${kpi.trendDirection === 'up' ? 'text-emerald-600' : 'text-red-600'}">${kpi.trendDirection === 'up' ? '↑' : '↓'} ${kpi.trend}</span>` : ''}
                        </div>
                        <p class="text-2xl font-bold text-on-surface">${kpi.value}</p>
                        <p class="text-sm text-on-surface-variant mt-1">${kpi.label}</p>
                    </div>
                `).join('')}
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <h2 id="data-view-title" class="text-lg font-headline font-semibold text-on-surface">Module Workspace</h2>
                    <div id="data-view-filters" class="flex flex-wrap gap-2"></div>
                </div>
                <div id="quick-actions-toolbar" class="flex flex-wrap gap-2 mb-4">
                    <button type="button" id="quick-action-workspace" class="px-3 py-2 rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low transition-colors cursor-pointer" title="Open module workspace">
                        <span class="material-symbols-outlined text-lg">dashboard</span>
                    </button>
                    <button type="button" id="quick-action-records" class="px-3 py-2 rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low transition-colors cursor-pointer" title="View related records">
                        <span class="material-symbols-outlined text-lg">list_alt</span>
                    </button>
                    <button type="button" id="quick-action-export" class="px-3 py-2 rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low transition-colors cursor-pointer" title="Export module report">
                        <span class="material-symbols-outlined text-lg">download</span>
                    </button>
                    <button type="button" id="quick-action-qr" class="px-3 py-2 rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low transition-colors cursor-pointer" title="Generate QR Code" onclick="generateQRCode('AST-001')">
                        <span class="material-symbols-outlined text-lg">qr_code</span>
                    </button>
                </div>
                <div id="data-view-content" class="min-h-[200px]">
                    <p class="text-sm text-on-surface-variant">Click "Open module workspace" to view data.</p>
                </div>
            </div>
        </div>
    `;

    // Add modals for SWS and DTRS
    addModuleModals(moduleId);

    // Wire quick action buttons to module-specific handlers
    wireQuickActionButtons(moduleId);
}

document.addEventListener('DOMContentLoaded', () => {
    renderModulePage();
    initSidebarSubmenus();
    initSidebarNavigation();
});

function initSidebarNavigation() {
    // Handle sidebar link clicks for in-page navigation
    const handleSidebarClick = (e) => {
        const link = e.target.closest('a');
        if (!link) return;

        // Allow ctrl/cmd/middle-click for opening in new tab
        if (e.ctrlKey || e.metaKey || e.button === 1) return;

        const href = link.getAttribute('href');
        if (!href) return;

        // Check if this is a submenu link (sub-view navigation within same module)
        const isSubmenuLink = link.classList.contains('sidebar-submenu-link');
        
        // For submenu links, do in-page navigation
        if (isSubmenuLink) {
            // Let the submenu click handler in initSidebarSubmenus handle this
            return;
        }

        // For module-level links, do full page navigation
        // This ensures proper module switching
        if (href.includes('module.html') || href.includes('dashboard.html')) {
            // Let the default anchor behavior handle the navigation
            return;
        }
    };

    // Attach to sidebar navigation container
    const sidebarNav = document.querySelector('.sidebar-subsystem-modules');
    if (sidebarNav) {
        sidebarNav.addEventListener('click', handleSidebarClick);
    }

    // Attach to dashboard link
    const dashboardLink = document.getElementById('sidebar-dashboard-link');
    if (dashboardLink) {
        dashboardLink.addEventListener('click', handleSidebarClick);
    }

    // Handle browser back/forward buttons
    window.addEventListener('popstate', () => {
        const currentPath = window.location.pathname;
        if (currentPath.includes('module.html')) {
            renderModulePage();
            initSidebarSubmenus();
        } else {
            window.location.reload();
        }
    });
}

function wireQuickActionButtons(moduleId) {
    const workspaceBtn = document.getElementById('quick-action-workspace');
    const recordsBtn = document.getElementById('quick-action-records');
    const exportBtn = document.getElementById('quick-action-export');
    const primaryActionBtn = document.getElementById('module-primary-action');

    if (!workspaceBtn || !recordsBtn || !exportBtn) return;

    switch (moduleId) {
        case 'sws':
            workspaceBtn.addEventListener('click', () => {
                renderSWSWorkspace();
            });
            recordsBtn.addEventListener('click', () => {
                renderSWSRecords();
            });
            exportBtn.addEventListener('click', () => {
                exportSWSReport();
            });
            if (primaryActionBtn) {
                primaryActionBtn.addEventListener('click', () => {
                    openDashboardModal('sws-scan-modal');
                });
            }
            break;
        case 'ims':
            workspaceBtn.addEventListener('click', () => {
                renderIMSWorkspace();
            });
            recordsBtn.addEventListener('click', () => {
                renderIMSRecords();
            });
            exportBtn.addEventListener('click', () => {
                exportIMSReport();
            });
            break;
        case 'psm':
            workspaceBtn.addEventListener('click', () => {
                renderPSMWorkspace();
            });
            recordsBtn.addEventListener('click', () => {
                renderPSMRecords();
            });
            exportBtn.addEventListener('click', () => {
                exportPSMReport();
            });
            break;
        case 'svm':
            workspaceBtn.addEventListener('click', () => {
                renderSVMWorkspace();
            });
            recordsBtn.addEventListener('click', () => {
                renderSVMRecords();
            });
            exportBtn.addEventListener('click', () => {
                exportSVMReport();
            });
            break;
        case 'pom':
            workspaceBtn.addEventListener('click', () => {
                renderPOMWorkspace();
            });
            recordsBtn.addEventListener('click', () => {
                renderPOMRecords();
            });
            exportBtn.addEventListener('click', () => {
                exportPOMReport();
            });
            break;
        case 'dtrs':
            workspaceBtn.addEventListener('click', () => {
                renderDTRSWorkspace();
            });
            recordsBtn.addEventListener('click', () => {
                renderDTRSRecords();
            });
            exportBtn.addEventListener('click', () => {
                exportDTRSReport();
            });
            if (primaryActionBtn) {
                primaryActionBtn.addEventListener('click', () => {
                    openDashboardModal('dtrs-waybill-modal');
                });
            }
            break;
    }
}

function initSidebarSubmenus() {
    // Handle module toggle clicks (accordion behavior + navigate to module)
    document.querySelectorAll('.sidebar-module-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const group = toggle.closest('.sidebar-module-group');
            const moduleId = toggle.dataset.module;
            
            // Toggle accordion
            if (group) {
                group.classList.toggle('open');
            }
            
            // Navigate to the module with its default view
            const subsystemId = getSubsystemFromUrl() || 'supply-chain';
            const firstSubmenuLink = group.querySelector('.sidebar-submenu-link');
            const defaultViewId = firstSubmenuLink ? firstSubmenuLink.dataset.view : null;
            const defaultRender = firstSubmenuLink ? firstSubmenuLink.dataset.render : null;
            
            // Check if we're already on module.html
            const isOnModulePage = window.location.pathname.includes('module.html');
            const currentModuleId = getModuleFromUrl();
            
            if (isOnModulePage && currentModuleId === moduleId) {
                // Already on this module, just update the view in-page
                if (defaultRender && window[defaultRender]) {
                    const url = new URL(window.location);
                    url.searchParams.set('view', defaultViewId);
                    window.history.pushState({}, '', url);
                    window[defaultRender]();
                }
            } else {
                // Navigate to the module page
                const moduleHref = `module.html?subsystem=${encodeURIComponent(subsystemId)}&module=${encodeURIComponent(moduleId)}`;
                window.location.href = moduleHref + (defaultViewId ? `&view=${defaultViewId}` : '');
            }
        });
    });

    // Handle submenu link clicks
    document.querySelectorAll('.sidebar-submenu-link').forEach(link => {
        link.addEventListener('click', (e) => {
            // Update active state for all submenu items in the same group
            const submenu = link.closest('.sidebar-submenu');
            if (submenu) {
                submenu.querySelectorAll('.sidebar-submenu-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            }

            // Get render function from data attribute and call it
            const renderFunc = link.dataset.render;
            if (renderFunc && window[renderFunc]) {
                e.preventDefault();
                // Update URL without full page reload
                const url = new URL(window.location);
                url.searchParams.set('view', link.dataset.view);
                window.history.pushState({}, '', url);
                
                // Call the render function
                window[renderFunc]();
            }
        });
    });

    // Handle collapsed sidebar flyout
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        document.querySelectorAll('.sidebar-module-group').forEach(group => {
            const toggle = group.querySelector('.sidebar-module-toggle');
            const moduleId = group.dataset.moduleId;
            
            // Create flyout menu
            const flyout = document.createElement('div');
            flyout.className = 'sidebar-flyout-menu';
            flyout.id = `flyout-${moduleId}`;
            
            // Get module name and subnav items
            const subsystem = getSubsystemById(getSubsystemFromUrl());
            let moduleName = '';
            let subnavItems = [];
            
            if (subsystem && subsystem.modules) {
                const mod = subsystem.modules.find(m => normalizeModule(m).id === moduleId);
                if (mod) {
                    moduleName = mod.name;
                    subnavItems = mod.subnav || [];
                }
            }
            
            // Build flyout content
            flyout.innerHTML = `
                <div class="sidebar-flyout-header">${moduleName}</div>
                ${subnavItems.map(sub => `
                    <a href="${getModuleHref(getSubsystemFromUrl(), moduleId)}&view=${sub.id}" 
                       class="sidebar-flyout-item ${sub.id === new URLSearchParams(window.location.search).get('view') ? 'active' : ''}"
                       data-view="${sub.id}"
                       data-render="${sub.render}">
                        <span class="material-symbols-outlined sidebar-submenu-icon">${sub.icon}</span>
                        <span>${sub.label}</span>
                    </a>
                `).join('')}
            `;
            
            group.appendChild(flyout);
            
            // Show flyout on hover when sidebar is collapsed
            toggle.addEventListener('mouseenter', () => {
                if (sidebar.classList.contains('w-20')) {
                    const rect = toggle.getBoundingClientRect();
                    flyout.style.top = `${rect.top}px`;
                    flyout.classList.add('visible');
                }
            });
            
            // Hide flyout on mouseleave
            group.addEventListener('mouseleave', () => {
                flyout.classList.remove('visible');
            });
            
            // Handle flyout item clicks
            flyout.querySelectorAll('.sidebar-flyout-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    const renderFunc = item.dataset.render;
                    if (renderFunc && window[renderFunc]) {
                        e.preventDefault();
                        // Update URL
                        const url = new URL(window.location);
                        url.searchParams.set('view', item.dataset.view);
                        window.history.pushState({}, '', url);
                        
                        // Call render function
                        window[renderFunc]();
                        
                        // Hide flyout
                        flyout.classList.remove('visible');
                    }
                });
            });
        });
    }

    // On page load, check URL params and call appropriate render function
    const params = new URLSearchParams(window.location.search);
    const view = params.get('view');
    const module = params.get('module');

    if (view && module) {
        // Find the corresponding render function from subsystem data
        const subsystem = getSubsystemById(params.get('subsystem'));
        if (subsystem && subsystem.modules) {
            const mod = subsystem.modules.find(m => normalizeModule(m).id === module);
            if (mod && mod.subnav) {
                const subItem = mod.subnav.find(s => s.id === view);
                if (subItem && subItem.render && window[subItem.render]) {
                    // Call the render function after a short delay to ensure DOM is ready
                    setTimeout(() => window[subItem.render](), 100);
                }
            }
        }
    }
}

function syncSidebarWithView(renderFuncName) {
    // Find which subnav item corresponds to this render function
    const params = new URLSearchParams(window.location.search);
    const module = params.get('module');
    const subsystem = getSubsystemById(params.get('subsystem'));
    
    if (subsystem && subsystem.modules) {
        const mod = subsystem.modules.find(m => normalizeModule(m).id === module);
        if (mod && mod.subnav) {
            const subItem = mod.subnav.find(s => s.render === renderFuncName);
            if (subItem) {
                // Update URL
                const url = new URL(window.location);
                url.searchParams.set('view', subItem.id);
                window.history.pushState({}, '', url);
                
                // Update sidebar active states
                document.querySelectorAll('.sidebar-submenu-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.dataset.view === subItem.id) {
                        link.classList.add('active');
                    }
                });
                
                // Update flyout active states
                document.querySelectorAll('.sidebar-flyout-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.dataset.view === subItem.id) {
                        item.classList.add('active');
                    }
                });
            }
        }
    }
}

// Render functions for each module
function renderSWSWorkspace() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Warehouse Zone Map';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderSWSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Zone Map</button>
            <button onclick="renderSWSBinLookup()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Bin Lookup</button>
            <button onclick="renderSWSTaskQueues()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Task Queues</button>
            <button onclick="renderSWSCycleCount()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Cycle Count</button>
        `;
    }
    
    syncSidebarWithView('renderSWSWorkspace');
    
    if (contentEl) {
        const zones = getWarehouseCapacity();
        const selectedZone = window.selectedZone || null;
        
        contentEl.innerHTML = `
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                ${zones.map(zone => {
                    const colorClass = getUtilizationColorClass(zone.utilization);
                    const progressColor = getProgressBarColor(zone.utilization);
                    const isSelected = selectedZone === zone.zone;
                    return `
                        <div class="border rounded-xl p-4 cursor-pointer transition-all hover:shadow-md ${colorClass} ${isSelected ? 'ring-2 ring-primary ring-offset-2' : ''}" onclick="filterZoneBins('${zone.zone}')">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <span class="font-semibold text-sm">${zone.zone}</span>
                                    <span class="text-xs ml-1 opacity-75">${zone.category}</span>
                                </div>
                                <span class="text-xs font-medium">${zone.utilization}%</span>
                            </div>
                            <div class="text-xs opacity-75">
                                ${zone.used} / ${zone.capacity} slots
                            </div>
                            <div class="mt-2 bg-white/50 rounded-full h-2">
                                <div class="h-2 rounded-full ${progressColor}" style="width: ${zone.utilization}%"></div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
            
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm text-on-surface">Zone Bin Layout ${selectedZone ? `- ${selectedZone}` : '(All Zones)'}</h3>
                <button onclick="clearZoneFilter()" class="text-xs text-primary hover:underline ${!selectedZone ? 'hidden' : ''}">Show All Zones</button>
            </div>
            
            <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                <div class="flex items-center gap-4 mb-3 text-xs text-on-surface-variant">
                    <div class="flex items-center gap-1">
                        <div class="w-4 h-4 rounded bg-primary"></div>
                        <span>Occupied</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-4 h-4 rounded bg-surface-container-low border border-outline-variant/30"></div>
                        <span>Empty</span>
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-2" id="zone-bin-grid">
                    ${renderZoneBins(selectedZone)}
                </div>
            </div>
        `;
    }
    confirmDashboardAction('Showing warehouse zone map');
}

function renderZoneBins(selectedZone) {
    const allBins = [];
    const zoneLetters = ['A', 'B', 'C', 'D'];
    
    zoneLetters.forEach(letter => {
        if (selectedZone && selectedZone !== `Zone ${letter}`) return;
        
        for (let i = 1; i <= 4; i++) {
            const occupied = Math.random() > 0.4;
            const binId = `${letter}-${i}`;
            const assetName = occupied ? getRandomAsset() : null;
            
            allBins.push({
                binId,
                occupied,
                assetName
            });
        }
    });
    
    return allBins.map(bin => `
        <div class="aspect-square rounded-lg flex items-center justify-center text-xs font-medium cursor-pointer transition-all hover:scale-105 hover:shadow-md ${bin.occupied ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface-variant border border-outline-variant/30'}" 
             onclick="openBinLookup('${bin.binId}')"
             title="${bin.occupied ? bin.assetName : 'Empty bin'}">
            ${bin.binId}
        </div>
    `).join('');
}

function getRandomAsset() {
    const assets = [
        'Laptop Dell XPS 15',
        'Monitor 27" 4K',
        'Keyboard Wireless',
        'Mouse Ergonomic',
        'Headset USB',
        'Docking Station'
    ];
    return assets[Math.floor(Math.random() * assets.length)];
}

function filterZoneBins(zoneName) {
    window.selectedZone = zoneName;
    renderSWSWorkspace();
}

function clearZoneFilter() {
    window.selectedZone = null;
    renderSWSWorkspace();
}

function openBinLookup(binId) {
    renderSWSBinLookup();
    // Pre-fill search with bin ID
    setTimeout(() => {
        const searchInput = document.querySelector('input[type="text"]');
        if (searchInput) {
            searchInput.value = binId;
            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }, 100);
}

function renderSWSBinLookup() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Bin-Level Stock Lookup';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderSWSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Zone Map</button>
            <button onclick="renderSWSBinLookup()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Bin Lookup</button>
            <button onclick="renderSWSTaskQueues()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Task Queues</button>
            <button onclick="renderSWSCycleCount()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Cycle Count</button>
        `;
    }
    
    syncSidebarWithView('renderSWSBinLookup');
    
    if (contentEl) {
        const mockBins = [
            { bin: 'A-12-03', asset: 'Laptop Dell XPS 15', status: 'Ready for Dispatch', lastScan: '2026-08-08 14:30' },
            { bin: 'A-12-04', asset: 'Monitor 27" 4K', status: 'Ready for Dispatch', lastScan: '2026-08-08 14:25' },
            { bin: 'B-08-01', asset: 'Keyboard Wireless', status: 'Pending IT Config', lastScan: '2026-08-08 13:15' },
            { bin: 'B-08-02', asset: 'Mouse Ergonomic', status: 'Pending IT Config', lastScan: '2026-08-08 13:10' },
            { bin: 'C-05-02', asset: null, status: 'Available', lastScan: '2026-08-07 16:45' },
            { bin: 'C-05-03', asset: null, status: 'Available', lastScan: '2026-08-07 16:40' }
        ];
        
        contentEl.innerHTML = `
            <div class="mb-4 flex flex-col sm:flex-row gap-2">
                <input type="text" id="bin-lookup-search" placeholder="Search by bin ID, item name, or status..." class="flex-1 px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary text-sm" oninput="filterBinLookup()">
                <select id="bin-lookup-status-filter" class="px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary text-sm" onchange="filterBinLookup()">
                    <option value="">All Statuses</option>
                    <option value="Ready for Dispatch">Ready for Dispatch</option>
                    <option value="Pending IT Config">Pending IT Config</option>
                    <option value="Available">Available</option>
                </select>
                <button onclick="filterBinLookup()" class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium">Search</button>
            </div>
            
            <div class="mb-3 flex items-center gap-4 text-xs text-on-surface-variant">
                <div class="flex items-center gap-1">
                    <span class="font-medium">Status Legend:</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Ready for Dispatch</span>
                    <span class="material-symbols-outlined text-xs ml-1" title="Item is prepped and ready to be sent out to its destination">info</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Pending IT Config</span>
                    <span class="material-symbols-outlined text-xs ml-1" title="Item is functional but awaiting IT setup (OS/software/login) before dispatch">info</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span>Available</span>
                    <span class="material-symbols-outlined text-xs ml-1" title="The bin/slot itself is empty and open to receive new stock">info</span>
                </div>
            </div>
            
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-surface-container-low/50">
                            <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Bin ID</th>
                            <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Asset</th>
                            <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                            <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Last Scan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20" id="bin-lookup-table-body">
                        ${renderBinLookupRows(mockBins)}
                    </tbody>
                </table>
            </div>
        `;
        
        // Store bins data for filtering
        window.binLookupData = mockBins;
    }
    confirmDashboardAction('Showing bin-level stock lookup');
}

function renderBinLookupRows(bins) {
    return bins.map(bin => `
        <tr class="hover:bg-surface-container-lowest transition-colors">
            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${bin.bin}</td>
            <td class="px-4 py-3 text-sm text-on-surface">${bin.asset || '—'}</td>
            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${bin.status === 'Ready for Dispatch' ? 'bg-emerald-100 text-emerald-700' : bin.status === 'Pending IT Config' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'}">${bin.status}</span></td>
            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${bin.lastScan}</td>
        </tr>
    `).join('');
}

function filterBinLookup() {
    const searchTerm = document.getElementById('bin-lookup-search')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('bin-lookup-status-filter')?.value || '';
    
    const filteredBins = window.binLookupData.filter(bin => {
        const matchesSearch = !searchTerm || 
            bin.bin.toLowerCase().includes(searchTerm) ||
            (bin.asset && bin.asset.toLowerCase().includes(searchTerm)) ||
            bin.status.toLowerCase().includes(searchTerm);
        
        const matchesStatus = !statusFilter || bin.status === statusFilter;
        
        return matchesSearch && matchesStatus;
    });
    
    const tableBody = document.getElementById('bin-lookup-table-body');
    if (tableBody) {
        tableBody.innerHTML = renderBinLookupRows(filteredBins);
    }
}

function renderSWSTaskQueues() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Putaway & Picking Task Queues';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderSWSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Zone Map</button>
            <button onclick="renderSWSBinLookup()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Bin Lookup</button>
            <button onclick="renderSWSTaskQueues()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Task Queues</button>
            <button onclick="renderSWSCycleCount()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Cycle Count</button>
        `;
    }
    
    syncSidebarWithView('renderSWSTaskQueues');
    
    if (contentEl) {
        const mockTasks = [
            { id: 'TASK-001', type: 'Putaway', priority: 'High', asset: 'Laptop Dell XPS 15', targetBin: 'A-12-05', assignedTo: 'John D.' },
            { id: 'TASK-002', type: 'Picking', priority: 'High', asset: 'Monitor 27" 4K', targetBin: 'A-12-03', assignedTo: 'Maria S.' },
            { id: 'TASK-003', type: 'Putaway', priority: 'Medium', asset: 'Keyboard Wireless', targetBin: 'B-08-03', assignedTo: 'Unassigned' },
            { id: 'TASK-004', type: 'Picking', priority: 'Medium', asset: 'Mouse Ergonomic', targetBin: 'B-08-01', assignedTo: 'Alex R.' }
        ];
        contentEl.innerHTML = `
            <div class="mb-3 flex items-center gap-4 text-xs text-on-surface-variant">
                <div class="flex items-center gap-1">
                    <span class="font-medium">Priority Legend:</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span>High</span>
                    <span class="material-symbols-outlined text-xs ml-1" title="High urgency - reflects task urgency (e.g., SLA, order deadline)">info</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Medium</span>
                    <span class="material-symbols-outlined text-xs ml-1" title="Medium urgency - reflects task urgency (e.g., SLA, order deadline)">info</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span>Low</span>
                    <span class="material-symbols-outlined text-xs ml-1" title="Low urgency - reflects task urgency (e.g., SLA, order deadline)">info</span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                    <h3 class="font-semibold text-sm text-on-surface mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-600">input</span>
                        Putaway Queue (2)
                    </h3>
                    <div class="space-y-2">
                        ${mockTasks.filter(t => t.type === 'Putaway').map(task => `
                            <div class="flex items-center justify-between p-3 rounded-lg bg-white border border-outline-variant/20">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-on-surface">${task.asset}</p>
                                    <p class="text-xs text-on-surface-variant">→ ${task.targetBin} • Assignee: ${task.assignedTo}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs px-2 py-0.5 rounded-full ${task.priority === 'High' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'}">${task.priority}</span>
                                    <button onclick="startTask('${task.id}')" class="px-2 py-1 text-xs rounded-lg bg-primary text-white hover:bg-primary/90 transition-colors cursor-pointer">Start</button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                    <h3 class="font-semibold text-sm text-on-surface mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600">output</span>
                        Picking Queue (2)
                    </h3>
                    <div class="space-y-2">
                        ${mockTasks.filter(t => t.type === 'Picking').map(task => `
                            <div class="flex items-center justify-between p-3 rounded-lg bg-white border border-outline-variant/20">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-on-surface">${task.asset}</p>
                                    <p class="text-xs text-on-surface-variant">→ ${task.targetBin} • Assignee: ${task.assignedTo}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs px-2 py-0.5 rounded-full ${task.priority === 'High' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'}">${task.priority}</span>
                                    <button onclick="startTask('${task.id}')" class="px-2 py-1 text-xs rounded-lg bg-primary text-white hover:bg-primary/90 transition-colors cursor-pointer">Start</button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }
    confirmDashboardAction('Showing putaway & picking task queues');
}

function startTask(taskId) {
    alert(`Starting task: ${taskId}\n\nThis would mark the task as "In Progress" and assign it to the current user.`);
}

function renderSWSCycleCount() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Cycle Count Scheduling';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderSWSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Zone Map</button>
            <button onclick="renderSWSBinLookup()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Bin Lookup</button>
            <button onclick="renderSWSTaskQueues()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Task Queues</button>
            <button onclick="renderSWSCycleCount()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Cycle Count</button>
        `;
    }
    
    syncSidebarWithView('renderSWSCycleCount');
    
    if (contentEl) {
        const mockSchedule = [
            { zone: 'Zone A', scheduledDate: '2026-08-10', scheduledBy: 'John D.', status: 'Scheduled', expectedCount: 20, actualCount: null, variance: null },
            { zone: 'Zone B', scheduledDate: '2026-08-12', scheduledBy: 'Maria S.', status: 'Scheduled', expectedCount: 20, actualCount: null, variance: null },
            { zone: 'Zone C', scheduledDate: '2026-08-08', scheduledBy: 'Alex R.', status: 'In Progress', expectedCount: 20, actualCount: 15, variance: -5 },
            { zone: 'Zone D', scheduledDate: '2026-08-05', scheduledBy: 'John D.', status: 'Completed', expectedCount: 20, actualCount: 20, variance: 0 },
            { zone: 'Zone E', scheduledDate: '2026-08-01', scheduledBy: 'John D.', status: 'Overdue', expectedCount: 20, actualCount: null, variance: null }
        ];
        
        contentEl.innerHTML = `
            <div class="mb-3 flex items-center gap-4 text-xs text-on-surface-variant">
                <div class="flex items-center gap-1">
                    <span class="font-medium">Status Legend:</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span>Scheduled</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span>In Progress</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Completed</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span>Overdue</span>
                </div>
                <span class="material-symbols-outlined text-xs ml-2" title="Cycle counts are periodic physical audits verifying system records match actual on-shelf inventory (not continuous real-time monitoring)">info</span>
            </div>
            
            <div class="mb-4 flex justify-end">
                <button class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium">Schedule New Count</button>
            </div>
            
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Zone</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Scheduled Date</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Scheduled By</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockSchedule.map(item => {
                        const isOverdue = item.status === 'Scheduled' && new Date(item.scheduledDate) < new Date();
                        const displayStatus = isOverdue ? 'Overdue' : item.status;
                        const statusColor = displayStatus === 'Completed' ? 'bg-emerald-100 text-emerald-700' : 
                                           displayStatus === 'In Progress' ? 'bg-blue-100 text-blue-700' : 
                                           displayStatus === 'Overdue' ? 'bg-red-100 text-red-700' : 
                                           'bg-slate-100 text-slate-700';
                        return `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${item.zone}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.scheduledDate}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.scheduledBy}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${statusColor}">${displayStatus}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button onclick="viewCycleCountDetails('${item.zone}', '${item.scheduledDate}', '${item.status}', ${item.expectedCount}, ${item.actualCount}, ${item.variance})" class="text-xs text-primary hover:underline">View Details</button>
                            </td>
                        </tr>
                    `}).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing cycle count scheduling');
}

function viewCycleCountDetails(zone, scheduledDate, status, expectedCount, actualCount, variance) {
    const isOverdue = status === 'Scheduled' && new Date(scheduledDate) < new Date();
    const displayStatus = isOverdue ? 'Overdue' : status;
    
    const modalHTML = `
        <div id="cycle-count-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200 module-specific-modal" onclick="if(event.target.id==='cycle-count-modal') closeDashboardModal('cycle-count-modal')">
            <div class="bg-surface rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-outline-variant/30 relative transform scale-95 transition-transform duration-200" onclick="event.stopPropagation()">
                <button type="button" onclick="closeDashboardModal('cycle-count-modal')" class="absolute top-4 right-4 p-1.5 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors" title="Close modal">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-2xl">fact_check</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-headline font-bold text-on-surface">Cycle Count Details</h3>
                        <p class="text-xs text-on-surface-variant">${zone} • ${scheduledDate}</p>
                    </div>
                </div>
                <div class="space-y-4 mb-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-3 rounded-lg bg-surface-container-low">
                            <p class="text-xs text-on-surface-variant mb-1">Expected Count</p>
                            <p class="text-lg font-bold text-on-surface">${expectedCount}</p>
                        </div>
                        <div class="p-3 rounded-lg bg-surface-container-low">
                            <p class="text-xs text-on-surface-variant mb-1">Actual Count</p>
                            <p class="text-lg font-bold text-on-surface">${actualCount !== null ? actualCount : '—'}</p>
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-surface-container-low">
                        <p class="text-xs text-on-surface-variant mb-1">Variance</p>
                        <p class="text-lg font-bold ${variance === 0 ? 'text-emerald-600' : variance !== null ? 'text-red-600' : 'text-on-surface'}">${variance !== null ? (variance > 0 ? '+' : '') + variance : '—'}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-surface-container-low">
                        <p class="text-xs text-on-surface-variant mb-1">Status</p>
                        <p class="text-sm font-medium text-on-surface">${displayStatus}</p>
                    </div>
                    ${variance !== null && variance !== 0 ? `
                    <div class="p-3 rounded-lg bg-red-50 border border-red-200">
                        <p class="text-xs text-red-700 mb-1">Discrepancies Found</p>
                        <p class="text-sm text-red-800">Bins with count differences detected. Review required.</p>
                    </div>
                    ` : ''}
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/20">
                    <button onclick="closeDashboardModal('cycle-count-modal')" class="px-4 py-2 rounded-xl border border-outline-variant/50 text-on-surface font-medium text-sm hover:bg-surface-container-low transition-colors cursor-pointer">Close</button>
                    ${variance !== null && variance !== 0 ? `
                    <button onclick="reconcileInventory('${zone}')" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm shadow-md shadow-primary/20 transition-all cursor-pointer">Reconcile Inventory</button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modal = document.getElementById('cycle-count-modal');
    if (modal) {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('div.transform').classList.remove('scale-95');
        }, 10);
    }
}

function reconcileInventory(zone) {
    alert(`Reconciling inventory for ${zone}\n\nThis would adjust the system records to match the actual count from the cycle count.`);
    closeDashboardModal('cycle-count-modal');
}

function renderSWSRecords() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    if (titleEl) titleEl.textContent = 'Recent Scan/Movement Log';
    if (contentEl) {
        const mockLog = [
            { timestamp: '2026-08-08 14:30', asset: 'Laptop Dell XPS 15', action: 'Scan In', zone: 'Zone A-12-03' },
            { timestamp: '2026-08-08 13:15', asset: 'Monitor 27" 4K', action: 'Zone Change', zone: 'Zone B-08-01' },
            { timestamp: '2026-08-08 11:45', asset: 'Keyboard Wireless', action: 'Scan Out', zone: 'Zone C-05-02' }
        ];
        
        contentEl.innerHTML = `
            <div class="mb-3 flex items-center gap-4 text-xs text-on-surface-variant">
                <div class="flex items-center gap-1">
                    <span class="font-medium">Action Legend:</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-emerald-600 text-sm">login</span>
                    <span>Scan In</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-slate-600 text-sm">logout</span>
                    <span>Scan Out</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-blue-600 text-sm">swap_horiz</span>
                    <span>Zone Change</span>
                </div>
            </div>
            
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Timestamp</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Asset</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Action</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Zone</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockLog.map(log => {
                        const actionIcon = log.action === 'Scan In' ? 'login' : 
                                          log.action === 'Scan Out' ? 'logout' : 
                                          'swap_horiz';
                        const actionColor = log.action === 'Scan In' ? 'text-emerald-600' : 
                                           log.action === 'Scan Out' ? 'text-slate-600' : 
                                           'text-blue-600';
                        return `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${log.timestamp}</td>
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${log.asset}</td>
                            <td class="px-4 py-3 text-sm text-on-surface whitespace-nowrap flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm ${actionColor}">${actionIcon}</span>
                                <span>${log.action}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${log.zone}</td>
                        </tr>
                    `}).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing recent scan/movement log');
}

function exportSWSReport() {
    const data = getWarehouseCapacity();
    const csv = 'Zone,Capacity,Used,Utilization\n' + data.map(z => `${z.zone},${z.capacity},${z.used},${z.utilization}%`).join('\n');
    downloadCSV(csv, 'sws-warehouse-capacity.csv');
    confirmDashboardAction('Exported warehouse capacity data to CSV');
}

function renderIMSWorkspace() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Item Master List';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderIMSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Item Master</button>
            <button onclick="renderIMSStockLevels()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Stock Levels</button>
            <button onclick="renderIMSUtilizationOverview()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Utilization Overview</button>
            <button onclick="renderIMSAdjustmentWorkflow()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Adjustments</button>
            <div class="w-px h-6 bg-outline-variant/30 mx-1"></div>
            <button onclick="renderIMSReceived()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Received</button>
            <button onclick="renderIMSAvailable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Available</button>
            <button onclick="renderIMSReleased()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Released</button>
            <button onclick="renderIMSRepairable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Repairable</button>
            <button onclick="renderIMSDisposal()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Disposal</button>
        `;
    }
    
    syncSidebarWithView('renderIMSWorkspace');
    
    if (contentEl) {
        const mockItems = [
            { assetId: 'AST-1001', name: 'Dell XPS 15 Laptop', brand: 'Dell', model: 'XPS 15', category: 'Laptops', assetValue: 1899, currentStock: 12, reorderPoint: 15, abcClass: 'A', zone: 'IT Storage Room' },
            { assetId: 'AST-1002', name: 'HP EliteBook 840', brand: 'HP', model: 'EliteBook 840', category: 'Laptops', assetValue: 1450, currentStock: 8, reorderPoint: 10, abcClass: 'A', zone: '3rd Floor Cage' },
            { assetId: 'AST-2001', name: 'Dell OptiPlex Desktop', brand: 'Dell', model: 'OptiPlex 7090', category: 'Desktop PCs', assetValue: 999, currentStock: 15, reorderPoint: 20, abcClass: 'A', zone: 'IT Storage Room' },
            { assetId: 'AST-3001', name: '27" 4K Monitor', brand: 'LG', model: '27UL850', category: 'Monitors', assetValue: 449, currentStock: 25, reorderPoint: 30, abcClass: 'B', zone: 'IT Storage Room' },
            { assetId: 'AST-3002', name: '24" Office Monitor', brand: 'Dell', model: 'P2419H', category: 'Monitors', assetValue: 279, currentStock: 18, reorderPoint: 25, abcClass: 'B', zone: '3rd Floor Cage' },
            { assetId: 'AST-4001', name: 'Wireless Keyboard', brand: 'Logitech', model: 'MX Keys', category: 'Keyboards', assetValue: 99, currentStock: 35, reorderPoint: 40, abcClass: 'C', zone: 'IT Storage Room' },
            { assetId: 'AST-4002', name: 'Ergonomic Mouse', brand: 'Logitech', model: 'MX Master 3', category: 'Mice', assetValue: 89, currentStock: 42, reorderPoint: 50, abcClass: 'C', zone: 'IT Storage Room' },
            { assetId: 'AST-5001', name: 'USB Headset', brand: 'Jabra', model: 'Evolve2 40', category: 'Headsets', assetValue: 129, currentStock: 28, reorderPoint: 35, abcClass: 'B', zone: '3rd Floor Cage' },
            { assetId: 'AST-6001', name: 'USB-C Docking Station', brand: 'Dell', model: 'WD19TB', category: 'Docking Stations', assetValue: 259, currentStock: 22, reorderPoint: 25, abcClass: 'B', zone: 'IT Storage Room' },
            { assetId: 'AST-7001', name: 'Ergonomic Office Chair', brand: 'Herman Miller', model: 'Aeron', category: 'Office Chairs', assetValue: 1295, currentStock: 10, reorderPoint: 15, abcClass: 'A', zone: '3rd Floor Cage' },
            { assetId: 'AST-8001', name: 'Company iPhone', brand: 'Apple', model: 'iPhone 15', category: 'Company Phones', assetValue: 799, currentStock: 5, reorderPoint: 10, abcClass: 'A', zone: 'IT Storage Room' },
            { assetId: 'AST-8002', name: 'Company Android Phone', brand: 'Samsung', model: 'Galaxy S24', category: 'Company Phones', assetValue: 699, currentStock: 8, reorderPoint: 12, abcClass: 'A', zone: 'IT Storage Room' },
            { assetId: 'AST-9001', name: 'SIM Card', brand: 'Various', model: 'Standard SIM', category: 'SIM Cards', assetValue: 10, currentStock: 50, reorderPoint: 60, abcClass: 'C', zone: 'IT Storage Room' },
            { assetId: 'AST-10001', name: 'ID/Access Card', brand: 'HID', model: 'Prox Card II', category: 'ID/Access Cards', assetValue: 15, currentStock: 45, reorderPoint: 50, abcClass: 'C', zone: '3rd Floor Cage' },
            { assetId: 'AST-11001', name: 'HD Webcam', brand: 'Logitech', model: 'C920', category: 'Webcams', assetValue: 79, currentStock: 20, reorderPoint: 25, abcClass: 'B', zone: 'IT Storage Room' },
            { assetId: 'AST-12001', name: '65W Power Adapter', brand: 'Dell', model: 'DA130PM130', category: 'Power Adapters', assetValue: 45, currentStock: 30, reorderPoint: 40, abcClass: 'C', zone: 'IT Storage Room' }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <input type="text" placeholder="Search by Asset ID or name..." class="flex-1 px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary text-sm">
                <select class="px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary text-sm">
                    <option>All Categories</option>
                    <option>Laptops</option>
                    <option>Desktop PCs</option>
                    <option>Monitors</option>
                    <option>Keyboards</option>
                    <option>Mice</option>
                    <option>Headsets</option>
                    <option>Docking Stations</option>
                    <option>Office Chairs</option>
                    <option>Company Phones</option>
                    <option>SIM Cards</option>
                    <option>ID/Access Cards</option>
                    <option>Webcams</option>
                    <option>Power Adapters</option>
                </select>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Asset ID</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Name</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Brand</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Category</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Asset Value</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Zone</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Stock</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap group relative">Reorder Point<span class="material-symbols-outlined text-xs ml-1 text-on-surface-variant cursor-help" title="Minimum stock threshold — when available units fall to or below this number, a restock request should be triggered">info</span></th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap group relative">ABC<span class="material-symbols-outlined text-xs ml-1 text-on-surface-variant cursor-help" title="A = High priority, B = Medium, C = Low — used for procurement and audit prioritization">info</span></th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockItems.map(item => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${item.assetId}</td>
                            <td class="px-4 py-3 text-sm text-on-surface">${item.name}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.brand}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.category}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">$${item.assetValue.toLocaleString()}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.zone}</td>
                            <td class="px-4 py-3 text-sm ${item.currentStock < item.reorderPoint ? 'text-red-600 font-medium' : 'text-on-surface-variant'} whitespace-nowrap">${item.currentStock}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.reorderPoint}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${item.abcClass === 'A' ? 'bg-red-100 text-red-700' : item.abcClass === 'B' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'}">${item.abcClass}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button onclick="generateQRCode('${item.assetId}')" class="text-on-surface-variant hover:text-primary transition-colors" title="Generate QR Code">
                                    <span class="material-symbols-outlined text-lg">qr_code</span>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing item master list');
}

function renderIMSStockLevels() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Stock Levels by Zone';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderIMSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Item Master</button>
            <button onclick="renderIMSStockLevels()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Stock Levels</button>
            <button onclick="renderIMSUtilizationOverview()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Utilization Overview</button>
            <button onclick="renderIMSAdjustmentWorkflow()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Adjustments</button>
            <div class="w-px h-6 bg-outline-variant/30 mx-1"></div>
            <button onclick="renderIMSReceived()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Received</button>
            <button onclick="renderIMSAvailable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Available</button>
            <button onclick="renderIMSReleased()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Released</button>
            <button onclick="renderIMSRepairable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Repairable</button>
            <button onclick="renderIMSDisposal()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Disposal</button>
        `;
    }
    
    syncSidebarWithView('renderIMSStockLevels');
    
    if (contentEl) {
        const mockStock = [
            { zone: 'IT Storage Room', assetId: 'AST-1001', assetName: 'Dell XPS 15 Laptop', quantity: 8, status: 'Normal' },
            { zone: 'IT Storage Room', assetId: 'AST-2001', assetName: 'Dell OptiPlex Desktop', quantity: 4, status: 'Low Stock' },
            { zone: 'IT Storage Room', assetId: 'AST-3001', assetName: '27" 4K Monitor', quantity: 15, status: 'Normal' },
            { zone: '3rd Floor Cage', assetId: 'AST-1002', assetName: 'HP EliteBook 840', quantity: 3, status: 'Low Stock' },
            { zone: '3rd Floor Cage', assetId: 'AST-3002', assetName: '24" Office Monitor', quantity: 18, status: 'Normal' },
            { zone: '3rd Floor Cage', assetId: 'AST-7001', assetName: 'Ergonomic Office Chair', quantity: 0, status: 'Out of Stock' }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-100 text-amber-700">Low Stock (2)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Normal (3)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Out of Stock (1)</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Zone</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Asset ID</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Asset Name</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Quantity</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockStock.map(stock => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${stock.zone}</td>
                            <td class="px-4 py-3 text-sm text-on-surface whitespace-nowrap">${stock.assetId}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${stock.assetName}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${stock.quantity}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${stock.status === 'Out of Stock' ? 'bg-red-100 text-red-700' : stock.status === 'Low Stock' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}">${stock.status}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button onclick="openTransferModal('${stock.assetId}', '${stock.assetName}', '${stock.zone}')" class="text-xs text-primary hover:underline">Transfer</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing stock levels by zone');
}

function openTransferModal(assetId, assetName, currentZone) {
    const zones = ['IT Storage Room', '3rd Floor Cage', 'Server Room', 'Reception Desk', 'Remote Office'];
    
    const modalHTML = `
        <div id="transfer-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200 module-specific-modal" onclick="if(event.target.id==='transfer-modal') closeDashboardModal('transfer-modal')">
            <div class="bg-surface rounded-3xl max-w-md w-full p-6 shadow-2xl border border-outline-variant/30 relative transform scale-95 transition-transform duration-200" onclick="event.stopPropagation()">
                <button type="button" onclick="closeDashboardModal('transfer-modal')" class="absolute top-4 right-4 p-1.5 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors" title="Close modal">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-2xl">local_shipping</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-headline font-bold text-on-surface">Transfer Asset</h3>
                        <p class="text-xs text-on-surface-variant">${assetId} - ${assetName}</p>
                    </div>
                </div>
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-1">Current Zone</label>
                        <input type="text" value="${currentZone}" disabled class="w-full px-3 py-2 rounded-lg border border-outline-variant/50 bg-surface-container-low text-on-surface-variant text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-1">Destination Zone</label>
                        <select id="transfer-destination-zone" class="w-full px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary text-sm">
                            ${zones.filter(zone => zone !== currentZone).map(zone => `
                                <option value="${zone}">${zone}</option>
                            `).join('')}
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/20">
                    <button type="button" onclick="closeDashboardModal('transfer-modal')" class="px-5 py-2 rounded-xl border border-outline-variant/50 text-on-surface hover:bg-surface-container-low font-semibold text-sm transition-all cursor-pointer">Cancel</button>
                    <button type="button" onclick="confirmTransfer('${assetId}', '${assetName}', '${currentZone}')" class="flex items-center gap-1.5 px-5 py-2 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm shadow-md shadow-primary/20 transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-base">check</span>
                        <span>Confirm Transfer</span>
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modal = document.getElementById('transfer-modal');
    if (modal) {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('div.transform').classList.remove('scale-95');
        }, 10);
    }
}

function confirmTransfer(assetId, assetName, currentZone) {
    const destinationZone = document.getElementById('transfer-destination-zone')?.value;
    if (!destinationZone) {
        alert('Please select a destination zone');
        return;
    }
    
    closeDashboardModal('transfer-modal');
    confirmDashboardAction(`Asset ${assetId} transferred from ${currentZone} to ${destinationZone}`);
    
    // Re-render the stock levels to show updated zone (mock behavior)
    setTimeout(() => {
        renderIMSStockLevels();
    }, 500);
}

function renderIMSUtilizationOverview() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Utilization Overview';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderIMSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Item Master</button>
            <button onclick="renderIMSStockLevels()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Stock Levels</button>
            <button onclick="renderIMSUtilizationOverview()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Utilization Overview</button>
            <button onclick="renderIMSAdjustmentWorkflow()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Adjustments</button>
            <div class="w-px h-6 bg-outline-variant/30 mx-1"></div>
            <button onclick="renderIMSReceived()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Received</button>
            <button onclick="renderIMSAvailable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Available</button>
            <button onclick="renderIMSReleased()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Released</button>
            <button onclick="renderIMSRepairable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Repairable</button>
            <button onclick="renderIMSDisposal()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Disposal</button>
        `;
    }
    
    syncSidebarWithView('renderIMSUtilizationOverview');
    
    if (contentEl) {
        const mockUtilization = [
            { category: 'Laptops', available: 12, deployed: 45, underRepair: 3, disposed: 2 },
            { category: 'Desktop PCs', available: 15, deployed: 38, underRepair: 2, disposed: 1 },
            { category: 'Monitors', available: 25, deployed: 62, underRepair: 4, disposed: 3 },
            { category: 'Peripherals', available: 30, deployed: 85, underRepair: 5, disposed: 2 },
            { category: 'Mobile Devices', available: 8, deployed: 22, underRepair: 1, disposed: 1 }
        ];
        contentEl.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                ${mockUtilization.map(util => `
                    <div class="border rounded-xl p-4 bg-blue-50 border-blue-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-lg font-bold text-blue-800">${util.category}</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-blue-700">Available:</span>
                                <span class="font-medium text-blue-800">${util.available}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-blue-700">Deployed:</span>
                                <span class="font-medium text-blue-800">${util.deployed}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-blue-700">Under Repair:</span>
                                <span class="font-medium text-blue-800">${util.underRepair}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-blue-700">Disposed:</span>
                                <span class="font-medium text-blue-800">${util.disposed}</span>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
            <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                <h3 class="font-semibold text-sm text-on-surface mb-3">Slow-Moving / Idle Asset Alert</h3>
                <div class="space-y-2">
                    <div class="flex items-center justify-between p-3 rounded-lg bg-white border border-outline-variant/20">
                        <div>
                            <p class="text-sm font-medium text-on-surface">AST-3002 - 24" Office Monitor</p>
                            <p class="text-xs text-on-surface-variant">No deployment in 45+ days</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Review</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-white border border-outline-variant/20">
                        <div>
                            <p class="text-sm font-medium text-on-surface">AST-4001 - Wireless Keyboard</p>
                            <p class="text-xs text-on-surface-variant">No deployment in 50+ days</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Review</span>
                    </div>
                </div>
            </div>
        `;
    }
    confirmDashboardAction('Showing utilization overview');
}

function renderIMSAdjustmentWorkflow() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Stock Adjustment Workflow';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderIMSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Item Master</button>
            <button onclick="renderIMSStockLevels()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Stock Levels</button>
            <button onclick="renderIMSUtilizationOverview()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Utilization Overview</button>
            <button onclick="renderIMSAdjustmentWorkflow()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Adjustments</button>
            <div class="w-px h-6 bg-outline-variant/30 mx-1"></div>
            <button onclick="renderIMSReceived()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Received</button>
            <button onclick="renderIMSAvailable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Available</button>
            <button onclick="renderIMSReleased()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Released</button>
            <button onclick="renderIMSRepairable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Repairable</button>
            <button onclick="renderIMSDisposal()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Disposal</button>
        `;
    }
    
    syncSidebarWithView('renderIMSAdjustmentWorkflow');
    
    if (contentEl) {
        const mockAdjustments = [
            { id: 'ADJ-001', assetId: 'AST-9001', type: 'Write-off', quantity: -5, reason: 'Damaged goods', requestedBy: 'John D.', status: 'Pending Approval' },
            { id: 'ADJ-002', assetId: 'AST-1001', type: 'Correction', quantity: +2, reason: 'Count error', requestedBy: 'Maria S.', status: 'Approved' },
            { id: 'ADJ-003', assetId: 'AST-4001', type: 'Write-off', quantity: -3, reason: 'Expired', requestedBy: 'Alex R.', status: 'Rejected' }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex justify-end">
                <button class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium">New Adjustment</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">ID</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Asset ID</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Type</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Quantity</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Reason</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Requested By</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockAdjustments.map(adj => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${adj.id}</td>
                            <td class="px-4 py-3 text-sm text-on-surface whitespace-nowrap">${adj.assetId}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${adj.type}</td>
                            <td class="px-4 py-3 text-sm ${adj.quantity < 0 ? 'text-red-600' : 'text-emerald-600'} whitespace-nowrap">${adj.quantity > 0 ? '+' : ''}${adj.quantity}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${adj.reason}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${adj.requestedBy}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${adj.status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : adj.status === 'Pending Approval' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'}">${adj.status}</span></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing stock adjustment workflow');
}

function renderIMSRecords() {
    const data = getStockMovementHistory('AST-1001');
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    if (titleEl) titleEl.textContent = 'Stock Movement History';
    if (contentEl) {
        contentEl.innerHTML = `
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Date</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Type</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Quantity</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Reason</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Performed By</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Zone/Location</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${data.map(movement => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${movement.date}</td>
                            <td class="px-4 py-3 text-sm text-on-surface">${movement.type}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${movement.quantity}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${movement.reason}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${movement.performedBy}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${movement.zone}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing stock movement history');
}

function exportIMSReport() {
    const alerts = checkReorderAlerts();
    const csv = 'Asset ID,Name,Current Stock,Reorder Threshold,Status\n' + alerts.map(s => `${s.assetId},${s.name},${s.currentStock},${s.reorderThreshold},Below Threshold`).join('\n');
    downloadCSV(csv, 'ims-reorder-alerts.csv');
    confirmDashboardAction('Exported reorder alerts to CSV');
}

// Asset Lifecycle Tabs
function renderIMSReceived() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Received Assets';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderIMSReceived()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Received</button>
            <button onclick="renderIMSAvailable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Available</button>
            <button onclick="renderIMSReleased()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Released</button>
            <button onclick="renderIMSRepairable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Repairable</button>
            <button onclick="renderIMSDisposal()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Disposal</button>
        `;
    }
    
    if (contentEl) {
        const mockReceived = [
            { assetId: 'AST-1001', itemsReceived: 'Dell XPS 15 Laptop', quantity: 12, supplier: 'Dell Direct', condition: 'New', timestamp: '2026-08-01 14:30', personResponsible: 'John D.', position: 'IT Manager', department: 'IT', dateReceived: '2026-08-01' },
            { assetId: 'AST-2001', itemsReceived: 'Dell OptiPlex Desktop', quantity: 15, supplier: 'CDW', condition: 'New', timestamp: '2026-08-03 10:15', personResponsible: 'Maria S.', position: 'Procurement Specialist', department: 'Finance', dateReceived: '2026-08-03' },
            { assetId: 'AST-3001', itemsReceived: '27" 4K Monitor', quantity: 25, supplier: 'LG Electronics', condition: 'New', timestamp: '2026-08-05 09:45', personResponsible: 'Alex R.', position: 'Inventory Coordinator', department: 'Operations', dateReceived: '2026-08-05' }
        ];
        
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-100 text-emerald-700">New (2)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Used (1)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Refurbished (0)</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Asset ID</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Item(s) Received</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Quantity</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Supplier/Source</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Condition</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Timestamp</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Person Responsible</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Position</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Department</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Date Received</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockReceived.map(item => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">
                                <button onclick="renderIMSAssetHistory('${item.assetId}')" class="text-primary hover:underline">${item.assetId}</button>
                            </td>
                            <td class="px-4 py-3 text-sm text-on-surface">${item.itemsReceived}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.quantity}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.supplier}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${item.condition === 'New' ? 'bg-emerald-100 text-emerald-700' : item.condition === 'Used' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'}">${item.condition}</span></td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.timestamp}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.personResponsible}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.position}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.department}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.dateReceived}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing received assets');
}

function renderIMSAvailable() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Available Inventory';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderIMSReceived()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Received</button>
            <button onclick="renderIMSAvailable()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Available</button>
            <button onclick="renderIMSReleased()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Released</button>
            <button onclick="renderIMSRepairable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Repairable</button>
            <button onclick="renderIMSDisposal()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Disposal</button>
        `;
    }
    
    if (contentEl) {
        const mockAvailable = [
            { assetId: 'AST-1001', model: 'Dell XPS 15', category: 'Laptops', brand: 'Dell', specs: 'i7-12700H/16GB/512GB SSD', condition: 'New', zone: 'IT Storage Room', dateAdded: '2026-08-01' },
            { assetId: 'AST-2001', model: 'Dell OptiPlex 7090', category: 'Desktop PCs', brand: 'Dell', specs: 'i5-12400/8GB/256GB SSD', condition: 'New', zone: 'IT Storage Room', dateAdded: '2026-08-03' },
            { assetId: 'AST-3001', model: '27" 4K Monitor', category: 'Monitors', brand: 'LG', specs: '27" IPS/4K/60Hz', condition: 'New', zone: 'IT Storage Room', dateAdded: '2026-08-05' },
            { assetId: 'AST-4001', model: 'MX Keys', category: 'Keyboards', brand: 'Logitech', specs: 'Wireless/Bluetooth/USB-C', condition: 'New', zone: 'IT Storage Room', dateAdded: '2026-08-07' },
            { assetId: 'AST-5001', model: 'Evolve2 40', category: 'Headsets', brand: 'Jabra', specs: 'USB/Noise Cancelling', condition: 'New', zone: '3rd Floor Cage', dateAdded: '2026-08-08' }
        ];
        
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-100 text-emerald-700">Laptops (1)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Desktop PCs (1)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Monitors (1)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Peripherals (2)</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Asset ID</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Model</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Category</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Brand</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Specs Summary</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Condition</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Zone</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Date Added</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockAvailable.map(item => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">
                                <button onclick="renderIMSAssetHistory('${item.assetId}')" class="text-primary hover:underline">${item.assetId}</button>
                            </td>
                            <td class="px-4 py-3 text-sm text-on-surface">${item.model}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.category}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.brand}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${item.specs}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${item.condition === 'New' ? 'bg-emerald-100 text-emerald-700' : item.condition === 'Used' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'}">${item.condition}</span></td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.zone}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.dateAdded}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing available inventory');
}

function renderIMSReleased() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Released Assets';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderIMSReceived()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Received</button>
            <button onclick="renderIMSAvailable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Available</button>
            <button onclick="renderIMSReleased()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Released</button>
            <button onclick="renderIMSRepairable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Repairable</button>
            <button onclick="renderIMSDisposal()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Disposal</button>
        `;
    }
    
    if (contentEl) {
        const mockReleased = [
            { assetId: 'AST-1002', timestamp: '2026-08-06 09:30', personResponsible: 'Sarah L.', position: 'HR Specialist', department: 'HR', assignedArea: 'Remote Office', otherConcern: 'None', dateRequested: '2026-08-05', receiver: 'John Smith', dateReleased: '2026-08-06', conditionAtRelease: 'New', expectedReturnDate: 'N/A', acknowledgementReceiptNumber: 'RCPT-2026-08-06-001' },
            { assetId: 'AST-3002', timestamp: '2026-08-07 14:15', personResponsible: 'Mike T.', position: 'Recruiter', department: 'Recruitment', assignedArea: 'Office - 3rd Floor', otherConcern: 'None', dateRequested: '2026-08-06', receiver: 'Jane Doe', dateReleased: '2026-08-07', conditionAtRelease: 'New', expectedReturnDate: 'N/A', acknowledgementReceiptNumber: 'RCPT-2026-08-07-002' },
            { assetId: 'AST-8001', timestamp: '2026-08-08 11:00', personResponsible: 'David W.', position: 'Sales Manager', department: 'Sales', assignedArea: 'Field Sales', otherConcern: 'Needs Data Plan', dateRequested: '2026-08-07', receiver: 'Robert Johnson', dateReleased: '2026-08-08', conditionAtRelease: 'New', expectedReturnDate: 'N/A', acknowledgementReceiptNumber: 'RCPT-2026-08-08-003' }
        ];
        
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-100 text-blue-700">HR (1)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Recruitment (1)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Sales (1)</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Asset ID</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Timestamp</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Person Responsible</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Position</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Department</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Assigned Area</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Other Concern</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Date Requested</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Receiver</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Date Released</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Condition</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Expected Return</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockReleased.map(item => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">
                                <button onclick="renderIMSAssetHistory('${item.assetId}')" class="text-primary hover:underline">${item.assetId}</button>
                            </td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.timestamp}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${item.personResponsible}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.position}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.department}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.assignedArea}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${item.otherConcern}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.dateRequested}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.receiver}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.dateReleased}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${item.conditionAtRelease === 'New' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">${item.conditionAtRelease}</span></td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.expectedReturnDate}</td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button onclick="generateQRCode('${item.assetId}')" class="text-on-surface-variant hover:text-primary transition-colors" title="Generate QR Code">
                                    <span class="material-symbols-outlined text-lg">qr_code</span>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing released assets');
}

function renderIMSRepairable() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Repairable Assets';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderIMSReceived()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Received</button>
            <button onclick="renderIMSAvailable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Available</button>
            <button onclick="renderIMSReleased()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Released</button>
            <button onclick="renderIMSRepairable()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Repairable</button>
            <button onclick="renderIMSDisposal()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Disposal</button>
        `;
    }
    
    if (contentEl) {
        const mockRepairable = [
            { assetId: 'AST-1003', model: 'Dell XPS 15', serialNumber: 'DX15-2024-001234', issueDescription: 'Battery not charging, needs replacement', status: 'Pending', dateReported: '2026-08-01', assignedTechnician: 'Unassigned' },
            { assetId: 'AST-2002', model: 'Dell OptiPlex 7090', serialNumber: 'DO70-2024-005678', issueDescription: 'Hard drive failure, data recovery needed', status: 'In Repair', dateReported: '2026-08-02', assignedTechnician: 'TechSupport Pro' },
            { assetId: 'AST-3003', model: '27" 4K Monitor', serialNumber: 'LG27-2024-009012', issueDescription: 'Screen flickering, backlight issue', status: 'Fixed', dateReported: '2026-08-03', assignedTechnician: 'LG Warranty Service' }
        ];
        
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-100 text-amber-700">Pending (1)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">In Repair (1)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Fixed (1)</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Asset ID</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Model</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Serial Number</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Issue/Description</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Date Reported</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Assigned Technician/Vendor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockRepairable.map(item => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${item.assetId}</td>
                            <td class="px-4 py-3 text-sm text-on-surface">${item.model}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.serialNumber}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${item.issueDescription}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${item.status === 'Fixed' ? 'bg-emerald-100 text-emerald-700' : item.status === 'In Repair' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'}">${item.status}</span></td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.dateReported}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.assignedTechnician}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing repairable assets');
}

function renderIMSDisposal() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Asset Disposal';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderIMSReceived()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Received</button>
            <button onclick="renderIMSAvailable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Available</button>
            <button onclick="renderIMSReleased()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Released</button>
            <button onclick="renderIMSRepairable()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Repairable</button>
            <button onclick="renderIMSDisposal()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Disposal</button>
        `;
    }
    
    if (contentEl) {
        const mockDisposal = [
            { assetId: 'AST-5002', model: 'USB Headset', computerName: 'N/A', description: 'Old headset with worn ear cushions', reasonForDisposal: 'End of Life', disposalMethod: 'Scrapped', approvedBy: 'John D.', disposalValue: 0, notes: 'Beyond repair, recycling recommended' },
            { assetId: 'AST-6002', model: 'USB-C Docking Station', computerName: 'LPT-001', description: 'Damaged USB ports, intermittent connection', reasonForDisposal: 'Damaged', disposalMethod: 'Scrapped', approvedBy: 'Maria S.', disposalValue: 0, notes: 'Multiple port failures, not cost-effective to repair' },
            { assetId: 'AST-7002', model: 'Ergonomic Office Chair', computerName: 'N/A', description: 'Old office chair with broken armrest', reasonForDisposal: 'Damaged', disposalMethod: 'Donated', approvedBy: 'Alex R.', disposalValue: 50, notes: 'Donated to local charity organization' }
        ];
        
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-100 text-red-700">End of Life (1)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Damaged (2)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Obsolete (0)</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Asset ID</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Model</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Computer Name</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Description</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Reason</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Method</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Approved By</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Disposal Value</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockDisposal.map(item => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${item.assetId}</td>
                            <td class="px-4 py-3 text-sm text-on-surface">${item.model}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.computerName}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${item.description}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${item.reasonForDisposal === 'End of Life' ? 'bg-slate-100 text-slate-700' : 'bg-red-100 text-red-700'}">${item.reasonForDisposal}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${item.disposalMethod === 'Sold' ? 'bg-emerald-100 text-emerald-700' : item.disposalMethod === 'Donated' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700'}">${item.disposalMethod}</span></td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${item.approvedBy}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">$${item.disposalValue}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${item.notes}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing asset disposal records');
}

function renderIMSAssetHistory(assetId) {
    const mockHistory = [
        { stage: 'Received', date: '2026-08-01 14:30', details: 'Received from Dell Direct - New condition - 12 units', performedBy: 'John D.' },
        { stage: 'Available', date: '2026-08-01 15:00', details: 'Added to IT Storage Room inventory', performedBy: 'John D.' },
        { stage: 'Released', date: '2026-08-06 09:30', details: 'Released to Sarah L. (HR Specialist) - Remote Office assignment', performedBy: 'Sarah L.' },
        { stage: 'Repair', date: '2026-08-07 10:15', details: 'Reported battery issue - Pending repair', performedBy: 'TechSupport' },
        { stage: 'Available', date: '2026-08-08 14:00', details: 'Repaired and returned to inventory', performedBy: 'TechSupport Pro' }
    ];
    
    const modalHTML = `
        <div id="asset-history-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200 module-specific-modal" onclick="if(event.target.id==='asset-history-modal') closeDashboardModal('asset-history-modal')">
            <div class="bg-surface rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-outline-variant/30 relative transform scale-95 transition-transform duration-200 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                <button type="button" onclick="closeDashboardModal('asset-history-modal')" class="absolute top-4 right-4 p-1.5 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors" title="Close modal">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-2xl">history</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-headline font-bold text-on-surface">Asset History</h3>
                        <p class="text-xs text-on-surface-variant">Asset ID: ${assetId}</p>
                    </div>
                </div>
                <div class="space-y-4">
                    ${mockHistory.map((event, index) => `
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full ${event.stage === 'Received' ? 'bg-emerald-100 text-emerald-700' : event.stage === 'Available' ? 'bg-blue-100 text-blue-700' : event.stage === 'Released' ? 'bg-purple-100 text-purple-700' : event.stage === 'Repair' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'} flex items-center justify-center text-xs font-bold">
                                    ${index + 1}
                                </div>
                                ${index < mockHistory.length - 1 ? '<div class="w-0.5 h-12 bg-outline-variant/30 my-1"></div>' : ''}
                            </div>
                            <div class="flex-1 pb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-semibold text-on-surface">${event.stage}</span>
                                    <span class="text-xs text-on-surface-variant">${event.date}</span>
                                </div>
                                <p class="text-sm text-on-surface-variant mb-1">${event.details}</p>
                                <p class="text-xs text-on-surface-variant">Performed by: ${event.performedBy}</p>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modal = document.getElementById('asset-history-modal');
    if (modal) {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('div.transform').classList.remove('scale-95');
        }, 10);
    }
}

function renderPSMWorkspace() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Requisition Queue';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderPSMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Requisitions</button>
            <button onclick="renderPSMRFQ()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">RFQs</button>
            <button onclick="renderPSMSourcingPipeline()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Sourcing</button>
            <button onclick="renderPSMSpendAnalytics()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Spend</button>
        `;
    }
    
    syncSidebarWithView('renderPSMWorkspace');
    
    if (contentEl) {
        const mockReqs = [
            { id: 'REQ-2026-016', item: 'Laptop Dell XPS 15', requestor: 'HR Dept', amount: 25000, budgetCheck: 'Pass', status: 'Pending Approval' },
            { id: 'REQ-2026-017', item: 'Office Chair Ergonomic', requestor: 'Facilities', amount: 7500, budgetCheck: 'Pass', status: 'Approved' },
            { id: 'REQ-2026-018', item: 'Monitor 27" 4K', requestor: 'IT Dept', amount: 9000, budgetCheck: 'Pass', status: 'In Review' },
            { id: 'REQ-2026-019', item: 'Welcome Kit Bundle', requestor: 'HR Dept', amount: 15000, budgetCheck: 'Fail', status: 'Budget Exceeded' }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-100 text-amber-700">Pending (2)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Approved (1)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Rejected (0)</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Req #</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Item</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Requestor</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Amount</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Budget</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockReqs.map(req => {
                        const statusColor = req.status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : 
                                            req.status === 'Pending Approval' ? 'bg-amber-100 text-amber-700' : 
                                            req.status === 'Budget Exceeded' ? 'bg-red-100 text-red-700' : 
                                            'bg-blue-100 text-blue-700';
                        return `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${req.id}</td>
                            <td class="px-4 py-3 text-sm text-on-surface">${req.item}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${req.requestor}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">$${req.amount.toLocaleString()}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${req.budgetCheck === 'Pass' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}">${req.budgetCheck}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${statusColor}">${req.status}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button class="text-xs text-primary hover:underline">Review</button>
                            </td>
                        </tr>
                    `}).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing requisition queue');
}

function renderPSMRFQ() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'RFQ Management';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderPSMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Requisitions</button>
            <button onclick="renderPSMRFQ()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">RFQs</button>
            <button onclick="renderPSMSourcingPipeline()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Sourcing</button>
            <button onclick="renderPSMSpendAnalytics()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Spend</button>
        `;
    }
    
    syncSidebarWithView('renderPSMRFQ');
    
    if (contentEl) {
        const mockRFQs = [
            { id: 'RFQ-2026-008', item: 'Laptop Dell XPS 15', vendors: 3, status: 'Quotes Received', deadline: '2026-08-10' },
            { id: 'RFQ-2026-009', item: 'Office Chair Ergonomic', vendors: 2, status: 'In Progress', deadline: '2026-08-12' },
            { id: 'RFQ-2026-010', item: 'Monitor 27" 4K', vendors: 4, status: 'Evaluation', deadline: '2026-08-08' }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex justify-end">
                <button class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium">Create RFQ</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">RFQ #</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Item</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Vendors</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Deadline</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockRFQs.map(rfq => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${rfq.id}</td>
                            <td class="px-4 py-3 text-sm text-on-surface">${rfq.item}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${rfq.vendors}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${rfq.deadline}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${rfq.status === 'Quotes Received' ? 'bg-emerald-100 text-emerald-700' : rfq.status === 'Evaluation' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'}">${rfq.status}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button class="text-xs text-primary hover:underline">Compare Quotes</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing RFQ management');
}

function renderPSMSourcingPipeline() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Sourcing Pipeline';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderPSMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Requisitions</button>
            <button onclick="renderPSMRFQ()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">RFQs</button>
            <button onclick="renderPSMSourcingPipeline()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Sourcing</button>
            <button onclick="renderPSMSpendAnalytics()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Spend</button>
        `;
    }
    
    syncSidebarWithView('renderPSMSourcingPipeline');
    
    if (contentEl) {
        const mockSuppliers = [
            { name: 'TechCorp Solutions', stage: 'Active', category: 'IT Hardware', onboardingDate: '2025-03-15', status: 'Approved' },
            { name: 'Office Supplies Co.', stage: 'Evaluation', category: 'Office Supplies', onboardingDate: '2026-07-20', status: 'In Review' },
            { name: 'Global Hardware Ltd.', stage: 'Qualified', category: 'IT Hardware', onboardingDate: '2026-06-10', status: 'Pending' },
            { name: 'Software Labs Inc.', stage: 'Active', category: 'Software', onboardingDate: '2024-11-01', status: 'Approved' }
        ];
        contentEl.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="border rounded-xl p-4 bg-slate-50 border-slate-200">
                    <p class="text-2xl font-bold text-slate-800">2</p>
                    <p class="text-sm text-slate-600">Active</p>
                </div>
                <div class="border rounded-xl p-4 bg-amber-50 border-amber-200">
                    <p class="text-2xl font-bold text-amber-800">1</p>
                    <p class="text-sm text-amber-600">Evaluation</p>
                </div>
                <div class="border rounded-xl p-4 bg-blue-50 border-blue-200">
                    <p class="text-2xl font-bold text-blue-800">1</p>
                    <p class="text-sm text-blue-600">Qualified</p>
                </div>
                <div class="border rounded-xl p-4 bg-emerald-50 border-emerald-200">
                    <p class="text-2xl font-bold text-emerald-800">4</p>
                    <p class="text-sm text-emerald-600">Total</p>
                </div>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Supplier</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Stage</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Category</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Onboarding Date</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockSuppliers.map(s => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium">${s.name}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${s.stage}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${s.category}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${s.onboardingDate}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${s.status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : s.status === 'In Review' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'}">${s.status}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button class="text-xs text-primary hover:underline">View Details</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing sourcing pipeline');
}

function renderPSMSpendAnalytics() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Spend Analytics';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderPSMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Requisitions</button>
            <button onclick="renderPSMRFQ()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">RFQs</button>
            <button onclick="renderPSMSourcingPipeline()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Sourcing</button>
            <button onclick="renderPSMSpendAnalytics()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Spend</button>
        `;
    }
    
    syncSidebarWithView('renderPSMSpendAnalytics');
    
    if (contentEl) {
        const mockSpend = [
            { category: 'IT Hardware', amount: 145000, percentage: 58, trend: '+12%' },
            { category: 'Office Supplies', amount: 50000, percentage: 20, trend: '+5%' },
            { category: 'Software Licenses', amount: 35000, percentage: 14, trend: '+8%' },
            { category: 'Services', amount: 20000, percentage: 8, trend: '-3%' }
        ];
        contentEl.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                    <h3 class="font-semibold text-sm text-on-surface mb-3">Total Spend (MTD)</h3>
                    <p class="text-3xl font-bold text-on-surface">$250,000</p>
                    <p class="text-xs text-emerald-600 mt-1">↑ 8% vs last month</p>
                </div>
                <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                    <h3 class="font-semibold text-sm text-on-surface mb-3">Budget Utilization</h3>
                    <p class="text-3xl font-bold text-on-surface">67%</p>
                    <p class="text-xs text-on-surface-variant mt-1">$250K / $375K</p>
                </div>
            </div>
            <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                <h3 class="font-semibold text-sm text-on-surface mb-3">Spend by Category</h3>
                <div class="space-y-3">
                    ${mockSpend.map(cat => `
                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-on-surface">${cat.category}</span>
                                    <span class="text-sm text-on-surface-variant">$${cat.amount.toLocaleString()} (${cat.percentage}%)</span>
                                </div>
                                <div class="bg-surface-container-high rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full" style="width: ${cat.percentage}%"></div>
                                </div>
                            </div>
                            <span class="text-xs ${cat.trend.startsWith('+') ? 'text-emerald-600' : 'text-red-600'}">${cat.trend}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    confirmDashboardAction('Showing spend analytics');
}

function renderPSMRecords() {
    const data = compareVendorQuotes('ITEM-001');
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    if (titleEl) titleEl.textContent = 'Vendor Quote Comparison';
    if (contentEl) {
        contentEl.innerHTML = `
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Vendor</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Price</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Lead Time (days)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${data.map(quote => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium">${quote.vendor}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">$${quote.price.toLocaleString()}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${quote.leadTime}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing vendor quote comparison');
}

function exportPSMReport() {
    const mockReqs = [
        { id: 'REQ-2026-016', item: 'Laptop Dell XPS 15', amount: 25000, status: 'Pending' },
        { id: 'REQ-2026-017', item: 'Office Chair Ergonomic', amount: 7500, status: 'Approved' }
    ];
    const csv = 'Req#,Item,Amount,Status\n' + mockReqs.map(r => `${r.id},${r.item},${r.amount},${r.status}`).join('\n');
    downloadCSV(csv, 'psm-requisitions.csv');
    confirmDashboardAction('Exported requisitions to CSV');
}

function renderSVMWorkspace() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Supplier Directory';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderSVMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Directory</button>
            <button onclick="renderSVMScorecards()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Scorecards</button>
            <button onclick="renderSVMCompliance()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Compliance</button>
            <button onclick="renderSVMOnboarding()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Onboarding</button>
        `;
    }
    
    if (contentEl) {
        const mockVendors = [
            { id: 'V-001', name: 'TechCorp Solutions', category: 'IT Hardware', contact: 'john@techcorp.com', status: 'Active', rating: 4.5 },
            { id: 'V-002', name: 'Office Supplies Co.', category: 'Office Supplies', contact: 'orders@officesupplies.com', status: 'Active', rating: 4.2 },
            { id: 'V-003', name: 'Software Labs Inc.', category: 'Software Licenses', contact: 'sales@softwarelabs.com', status: 'Active', rating: 4.8 },
            { id: 'V-004', name: 'Global Hardware Ltd.', category: 'IT Hardware', contact: 'info@globalhardware.com', status: 'Under Review', rating: 3.9 },
            { id: 'V-005', name: 'Metro Logistics', category: 'Courier Services', contact: 'dispatch@metrologistics.com', status: 'Blacklisted', rating: 2.1 }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <input type="text" placeholder="Search by name or category..." class="flex-1 px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary text-sm">
                <select class="px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary text-sm">
                    <option>All Status</option>
                    <option>Active</option>
                    <option>Under Review</option>
                    <option>Blacklisted</option>
                </select>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Vendor ID</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Name</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Category</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Contact</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Rating</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockVendors.map(v => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${v.id}</td>
                            <td class="px-4 py-3 text-sm text-on-surface">${v.name}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${v.category}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${v.contact}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${v.rating} ★</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${v.status === 'Active' ? 'bg-emerald-100 text-emerald-700' : v.status === 'Under Review' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'}">${v.status}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button class="text-xs text-primary hover:underline">View Profile</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing supplier directory');
}

function renderSVMScorecards() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Vendor Scorecards';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderSVMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Directory</button>
            <button onclick="renderSVMScorecards()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Scorecards</button>
            <button onclick="renderSVMCompliance()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Compliance</button>
            <button onclick="renderSVMOnboarding()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Onboarding</button>
        `;
    }
    
    if (contentEl) {
        const mockScorecards = [
            { vendor: 'TechCorp Solutions', onTimeRate: 94, defectRate: 0.5, priceCompetitiveness: 8.5, overallScore: 87, trend: '+2' },
            { vendor: 'Office Supplies Co.', onTimeRate: 91, defectRate: 1.2, priceCompetitiveness: 7.8, overallScore: 82, trend: '+1' },
            { vendor: 'Software Labs Inc.', onTimeRate: 98, defectRate: 0.1, priceCompetitiveness: 7.2, overallScore: 92, trend: '+3' },
            { vendor: 'Metro Logistics', onTimeRate: 75, defectRate: 3.5, priceCompetitiveness: 6.5, overallScore: 65, trend: '-5' }
        ];
        contentEl.innerHTML = `
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Vendor</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">On-Time %</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Defect Rate</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Price Comp.</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Overall Score</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Trend</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockScorecards.map(s => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium">${s.vendor}</td>
                            <td class="px-4 py-3 text-sm ${s.onTimeRate >= 90 ? 'text-emerald-600' : s.onTimeRate >= 80 ? 'text-amber-600' : 'text-red-600'} whitespace-nowrap">${s.onTimeRate}%</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${s.defectRate}%</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${s.priceCompetitiveness}/10</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-sm font-bold ${s.overallScore >= 85 ? 'text-emerald-600' : s.overallScore >= 75 ? 'text-amber-600' : 'text-red-600'}">${s.overallScore}/100</span></td>
                            <td class="px-4 py-3 text-sm ${s.trend.startsWith('+') ? 'text-emerald-600' : 'text-red-600'} whitespace-nowrap">${s.trend}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing vendor scorecards');
}

function renderSVMCompliance() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Compliance Documents';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderSVMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Directory</button>
            <button onclick="renderSVMScorecards()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Scorecards</button>
            <button onclick="renderSVMCompliance()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Compliance</button>
            <button onclick="renderSVMOnboarding()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Onboarding</button>
        `;
    }
    
    if (contentEl) {
        const mockDocs = [
            { vendor: 'TechCorp Solutions', docType: 'Service Agreement', expiry: '2026-12-31', status: 'Active' },
            { vendor: 'TechCorp Solutions', docType: 'Tax Document', expiry: null, status: 'On File' },
            { vendor: 'Office Supplies Co.', docType: 'Service Agreement', expiry: '2026-10-15', status: 'Expiring Soon' },
            { vendor: 'Software Labs Inc.', docType: 'Software License', expiry: '2026-09-01', status: 'Expiring Soon' },
            { vendor: 'Metro Logistics', docType: 'Insurance Certificate', expiry: '2026-07-01', status: 'Expired' }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-100 text-red-700">Expired (1)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-100 text-amber-700">Expiring Soon (2)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Active (2)</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Vendor</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Document Type</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Expiry Date</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockDocs.map(d => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium">${d.vendor}</td>
                            <td class="px-4 py-3 text-sm text-on-surface">${d.docType}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${d.expiry || 'N/A'}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${d.status === 'Active' ? 'bg-emerald-100 text-emerald-700' : d.status === 'Expiring Soon' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'}">${d.status}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button class="text-xs text-primary hover:underline">View Document</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing compliance documents');
}

function renderSVMOnboarding() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Supplier Onboarding';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderSVMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Directory</button>
            <button onclick="renderSVMScorecards()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Scorecards</button>
            <button onclick="renderSVMCompliance()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Compliance</button>
            <button onclick="renderSVMOnboarding()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Onboarding</button>
        `;
    }
    
    if (contentEl) {
        const mockOnboarding = [
            { vendor: 'Global Hardware Ltd', startDate: '2026-06-10', stage: 'Background Check', progress: 60 },
            { vendor: 'New Supplier Inc', startDate: '2026-08-01', stage: 'Document Collection', progress: 30 },
            { vendor: 'Regional Logistics Co', startDate: '2026-07-25', stage: 'Contract Review', progress: 80 }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex justify-end">
                <button class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium">Start New Onboarding</button>
            </div>
            <div class="space-y-4">
                ${mockOnboarding.map(o => `
                    <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="font-semibold text-sm text-on-surface">${o.vendor}</p>
                                <p class="text-xs text-on-surface-variant">Started: ${o.startDate}</p>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">${o.stage}</span>
                        </div>
                        <div class="mb-3">
                            <div class="flex justify-between mb-1">
                                <span class="text-xs text-on-surface-variant">Progress</span>
                                <span class="text-xs text-on-surface-variant">${o.progress}%</span>
                            </div>
                            <div class="bg-surface-container-high rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full" style="width: ${o.progress}%"></div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button class="text-xs text-primary hover:underline">View Checklist</button>
                            <button class="text-xs text-primary hover:underline">Send Reminder</button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    confirmDashboardAction('Showing supplier onboarding');
}

function renderSVMRecords() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    if (titleEl) titleEl.textContent = 'Vendor Scorecards';
    if (contentEl) {
        const mockScorecards = [
            { vendor: 'TechCorp Solutions', deliveryLeadTime: 8.5, defectRate: 0.02, overallScore: 87 },
            { vendor: 'Office Supplies Co.', deliveryLeadTime: 7.2, defectRate: 0.01, overallScore: 92 }
        ];
        contentEl.innerHTML = `
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Vendor</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Lead Time</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Defect Rate</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Overall Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockScorecards.map(s => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium">${s.vendor}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${s.deliveryLeadTime} days</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${(s.defectRate * 100).toFixed(1)}%</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${s.overallScore}/100</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing vendor scorecards');
}

function exportSVMReport() {
    const mockVendors = [
        { id: 'V-001', name: 'TechCorp Solutions', category: 'IT Hardware', rating: 4.5 },
        { id: 'V-002', name: 'Office Supplies Co.', category: 'Office Supplies', rating: 4.2 }
    ];
    const csv = 'Vendor ID,Name,Category,Rating\n' + mockVendors.map(v => `${v.id},${v.name},${v.category},${v.rating}`).join('\n');
    downloadCSV(csv, 'svm-vendors.csv');
    confirmDashboardAction('Exported vendor KPIs to CSV');
}

function renderPOMWorkspace() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'PO Status Pipeline';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderPOMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Pipeline</button>
            <button onclick="renderPOMCreation()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Create PO</button>
            <button onclick="renderPOMGRN()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">GRN</button>
            <button onclick="renderPOMOverdue()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Overdue</button>
        `;
    }
    
    if (contentEl) {
        const data = getPOStatusPipeline();
        contentEl.innerHTML = `
            <div class="space-y-4">
                ${Object.entries(data).map(([status, pos]) => {
                    const statusColor = status === 'draft' ? 'bg-slate-100 text-slate-700' : 
                                        status === 'approved' ? 'bg-blue-100 text-blue-700' : 
                                        status === 'sent' ? 'bg-amber-100 text-amber-700' : 
                                        status === 'partiallyReceived' ? 'bg-purple-100 text-purple-700' : 
                                        'bg-emerald-100 text-emerald-700';
                    return `
                    <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-sm text-on-surface capitalize">${status}</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full ${statusColor}">${pos.length} POs</span>
                        </div>
                        <div class="space-y-2">
                            ${pos.map(po => `
                                <div class="flex justify-between items-center text-sm p-2 rounded-lg bg-white border border-outline-variant/20">
                                    <span class="text-on-surface font-medium">${po.id}</span>
                                    <span class="text-on-surface-variant">$${po.amount.toLocaleString()}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `}).join('')}
            </div>
        `;
    }
    confirmDashboardAction('Showing PO status pipeline');
}

function renderPOMCreation() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Create Purchase Order';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderPOMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Pipeline</button>
            <button onclick="renderPOMCreation()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Create PO</button>
            <button onclick="renderPOMGRN()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">GRN</button>
            <button onclick="renderPOMOverdue()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Overdue</button>
        `;
    }
    
    if (contentEl) {
        contentEl.innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-1">Requisition Reference</label>
                        <select class="w-full px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary text-sm">
                            <option>Select requisition...</option>
                            <option>REQ-2026-016 - Laptop Dell XPS 15 ($25,000)</option>
                            <option>REQ-2026-017 - Office Chair Ergonomic ($7,500)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-1">Supplier</label>
                        <select class="w-full px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary text-sm">
                            <option>Select supplier...</option>
                            <option>TechCorp Solutions</option>
                            <option>Office Supplies Co.</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-on-surface mb-1">Line Items</label>
                    <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-xs text-on-surface-variant">
                                    <th class="pb-2">Item</th>
                                    <th class="pb-2">Qty</th>
                                    <th class="pb-2">Unit Price</th>
                                    <th class="pb-2">Total</th>
                                    <th class="pb-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="py-2 text-sm">Laptop Dell XPS 15</td>
                                    <td class="py-2"><input type="number" value="5" class="w-16 px-2 py-1 rounded border border-outline-variant/50 text-sm"></td>
                                    <td class="py-2"><input type="number" value="5000" class="w-24 px-2 py-1 rounded border border-outline-variant/50 text-sm"></td>
                                    <td class="py-2 text-sm">$25,000</td>
                                    <td class="py-2"><button class="text-red-600 text-xs">Remove</button></td>
                                </tr>
                            </tbody>
                        </table>
                        <button class="mt-3 text-xs text-primary hover:underline">+ Add Line Item</button>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button class="px-4 py-2 rounded-lg border border-outline-variant/50 text-on-surface text-sm font-medium">Save as Draft</button>
                    <button class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium">Submit for Approval</button>
                </div>
            </div>
        `;
    }
    confirmDashboardAction('Showing PO creation form');
}

function renderPOMGRN() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Goods Receipt Notes (GRN)';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderPOMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Pipeline</button>
            <button onclick="renderPOMCreation()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Create PO</button>
            <button onclick="renderPOMGRN()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">GRN</button>
            <button onclick="renderPOMOverdue()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Overdue</button>
        `;
    }
    
    if (contentEl) {
        const mockGRNs = [
            { id: 'GRN-2026-045', po: 'PO-2026-003', supplier: 'TechCorp Solutions', receivedDate: '2026-08-08', status: 'Matched' },
            { id: 'GRN-2026-046', po: 'PO-2026-004', supplier: 'Office Supplies Co.', receivedDate: '2026-08-07', status: 'Pending Match' },
            { id: 'GRN-2026-047', po: 'PO-2026-005', supplier: 'Software Labs Inc.', receivedDate: '2026-08-06', status: 'Discrepancy' }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex justify-end">
                <button class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium">Create GRN</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">GRN #</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">PO #</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Supplier</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Received Date</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockGRNs.map(grn => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${grn.id}</td>
                            <td class="px-4 py-3 text-sm text-on-surface whitespace-nowrap">${grn.po}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${grn.supplier}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${grn.receivedDate}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${grn.status === 'Matched' ? 'bg-emerald-100 text-emerald-700' : grn.status === 'Pending Match' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'}">${grn.status}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button class="text-xs text-primary hover:underline">View Details</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing GRN management');
}

function renderPOMOverdue() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Overdue PO Alerts';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderPOMWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Pipeline</button>
            <button onclick="renderPOMCreation()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Create PO</button>
            <button onclick="renderPOMGRN()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">GRN</button>
            <button onclick="renderPOMOverdue()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Overdue</button>
        `;
    }
    
    if (contentEl) {
        const overdue = flagOverduePOs();
        contentEl.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="border border-red-200 rounded-xl p-4 bg-red-50">
                    <p class="text-2xl font-bold text-red-800">${overdue.length}</p>
                    <p class="text-sm text-red-600">Overdue POs</p>
                </div>
                <div class="border border-amber-200 rounded-xl p-4 bg-amber-50">
                    <p class="text-2xl font-bold text-amber-800">${overdue.reduce((sum, po) => sum + po.daysOverdue, 0)}</p>
                    <p class="text-sm text-amber-600">Total Days Overdue</p>
                </div>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">PO #</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Expected Delivery</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Days Overdue</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Supplier</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${overdue.map(po => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${po.id}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${po.expectedDelivery}</td>
                            <td class="px-4 py-3 text-sm text-red-600 font-medium whitespace-nowrap">${po.daysOverdue} days</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">TechCorp Solutions</td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button class="text-xs text-primary hover:underline">Follow Up</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing overdue PO alerts');
}

function renderPOMRecords() {
    const data = matchThreeWay('PO-2026-001');
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    if (titleEl) titleEl.textContent = 'Three-Way Match';
    if (contentEl) {
        contentEl.innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                        <h3 class="font-semibold text-sm text-on-surface mb-2">PO Amount</h3>
                        <p class="text-lg font-bold text-on-surface">$${data.poAmount.toLocaleString()}</p>
                    </div>
                    <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                        <h3 class="font-semibold text-sm text-on-surface mb-2">Goods Receipt</h3>
                        <p class="text-lg font-bold text-on-surface">$${data.goodsReceiptAmount.toLocaleString()}</p>
                    </div>
                    <div class="border border-outline-variant/30 rounded-xl p-4 bg-surface-container-low/40">
                        <h3 class="font-semibold text-sm text-on-surface mb-2">Invoice</h3>
                        <p class="text-lg font-bold text-on-surface">$${data.invoiceAmount.toLocaleString()}</p>
                    </div>
                </div>
                <div class="p-4 rounded-xl ${data.discrepancy > 0 ? 'bg-amber-50 border border-amber-200' : 'bg-emerald-50 border border-emerald-200'}">
                    <p class="font-semibold text-sm ${data.discrepancy > 0 ? 'text-amber-800' : 'text-emerald-800'}">${data.status}</p>
                    ${data.discrepancy > 0 ? `<p class="text-sm text-amber-700 mt-1">Discrepancy: $${data.discrepancy}</p>` : ''}
                </div>
            </div>
        `;
    }
    confirmDashboardAction('Showing three-way match view');
}

function exportPOMReport() {
    const pipeline = getPOStatusPipeline();
    const allPOs = Object.values(pipeline).flat();
    const overdue = flagOverduePOs();
    const csv = 'PO ID,Amount,Status,Days Overdue\n' + allPOs.map(po => {
        const overdueInfo = overdue.find(o => o.id === po.id);
        return `${po.id},${po.amount},Open,${overdueInfo ? overdueInfo.daysOverdue : 0}`;
    }).join('\n');
    downloadCSV(csv, 'pom-purchase-orders.csv');
    confirmDashboardAction(`Exported ${allPOs.length} POs to CSV (${overdue.length} overdue)`);
}

function renderDTRSWorkspace() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Shipment Tracking';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderDTRSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Tracking</button>
            <button onclick="renderDTRSDocuments()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Documents</button>
            <button onclick="renderDTRSDelivery()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Delivery</button>
            <button onclick="renderDTRSExceptions()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Exceptions</button>
        `;
    }
    
    if (contentEl) {
        const mockShipments = [
            { waybill: 'WB-001234', carrier: 'FedEx', trackingNumber: '123456789012', destination: 'Remote Site A', status: 'In Transit', eta: '2026-08-10' },
            { waybill: 'WB-001235', carrier: 'DHL', trackingNumber: '987654321098', destination: 'Remote Site B', status: 'Delivered', eta: '2026-08-08' },
            { waybill: 'WB-001236', carrier: 'UPS', trackingNumber: '567890123456', destination: 'Remote Site C', status: 'Pending Pickup', eta: '2026-08-12' },
            { waybill: 'WB-001237', carrier: 'FedEx', trackingNumber: '234567890123', destination: 'Remote Site D', status: 'Exception', eta: '2026-08-09' }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <input type="text" placeholder="Search by waybill or tracking number..." class="flex-1 px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary text-sm">
                <button class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium">Track</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="border rounded-xl p-4 bg-blue-50 border-blue-200">
                    <p class="text-2xl font-bold text-blue-800">1</p>
                    <p class="text-sm text-blue-600">In Transit</p>
                </div>
                <div class="border rounded-xl p-4 bg-emerald-50 border-emerald-200">
                    <p class="text-2xl font-bold text-emerald-800">1</p>
                    <p class="text-sm text-emerald-600">Delivered</p>
                </div>
                <div class="border rounded-xl p-4 bg-amber-50 border-amber-200">
                    <p class="text-2xl font-bold text-amber-800">1</p>
                    <p class="text-sm text-amber-600">Pending Pickup</p>
                </div>
                <div class="border rounded-xl p-4 bg-red-50 border-red-200">
                    <p class="text-2xl font-bold text-red-800">1</p>
                    <p class="text-sm text-red-600">Exception</p>
                </div>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Waybill</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Carrier</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Tracking #</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Destination</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">ETA</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockShipments.map(s => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${s.waybill}</td>
                            <td class="px-4 py-3 text-sm text-on-surface whitespace-nowrap">${s.carrier}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${s.trackingNumber}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${s.destination}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${s.eta}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${s.status === 'Delivered' ? 'bg-emerald-100 text-emerald-700' : s.status === 'In Transit' ? 'bg-blue-100 text-blue-700' : s.status === 'Exception' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'}">${s.status}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button class="text-xs text-primary hover:underline">View Details</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing shipment tracking');
}

function renderDTRSDocuments() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Document Storage';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderDTRSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Tracking</button>
            <button onclick="renderDTRSDocuments()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Documents</button>
            <button onclick="renderDTRSDelivery()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Delivery</button>
            <button onclick="renderDTRSExceptions()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Exceptions</button>
        `;
    }
    
    if (contentEl) {
        const mockDocs = [
            { waybill: 'WB-001234', docType: 'Bill of Lading', uploaded: '2026-08-08 10:30', status: 'On File' },
            { waybill: 'WB-001234', docType: 'Packing List', uploaded: '2026-08-08 10:32', status: 'On File' },
            { waybill: 'WB-001234', docType: 'Commercial Invoice', uploaded: '2026-08-08 10:35', status: 'On File' },
            { waybill: 'WB-001235', docType: 'Bill of Lading', uploaded: '2026-08-07 14:20', status: 'On File' },
            { waybill: 'WB-001235', docType: 'Customs Declaration', uploaded: '2026-08-07 14:25', status: 'Pending' }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex justify-end">
                <button class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium">Upload Document</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Waybill</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Document Type</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Uploaded</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockDocs.map(d => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${d.waybill}</td>
                            <td class="px-4 py-3 text-sm text-on-surface">${d.docType}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${d.uploaded}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${d.status === 'On File' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">${d.status}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button class="text-xs text-primary hover:underline">Download</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing document storage');
}

function renderDTRSDelivery() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Delivery Confirmation';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderDTRSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Tracking</button>
            <button onclick="renderDTRSDocuments()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Documents</button>
            <button onclick="renderDTRSDelivery()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Delivery</button>
            <button onclick="renderDTRSExceptions()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Exceptions</button>
        `;
    }
    
    if (contentEl) {
        const mockDeliveries = [
            { waybill: 'WB-001235', recipient: 'John Smith', deliveredDate: '2026-08-08 14:30', signature: 'On File', proofOfDelivery: 'Uploaded' },
            { waybill: 'WB-001233', recipient: 'Maria Garcia', deliveredDate: '2026-08-07 11:15', signature: 'On File', proofOfDelivery: 'Uploaded' },
            { waybill: 'WB-001230', recipient: 'Alex Johnson', deliveredDate: '2026-08-06 16:45', signature: 'Pending', proofOfDelivery: 'Pending' }
        ];
        contentEl.innerHTML = `
            <div class="mb-4 flex gap-2">
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-100 text-emerald-700">Confirmed (2)</button>
                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Pending (1)</button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Waybill</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Recipient</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Delivered Date</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Signature</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Proof of Delivery</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockDeliveries.map(d => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${d.waybill}</td>
                            <td class="px-4 py-3 text-sm text-on-surface">${d.recipient}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${d.deliveredDate}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${d.signature === 'On File' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">${d.signature}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${d.proofOfDelivery === 'Uploaded' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">${d.proofOfDelivery}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button class="text-xs text-primary hover:underline">View Details</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing delivery confirmation');
}

function renderDTRSExceptions() {
    const titleEl = document.getElementById('data-view-title');
    const contentEl = document.getElementById('data-view-content');
    const filtersEl = document.getElementById('data-view-filters');
    
    if (titleEl) titleEl.textContent = 'Exception Log';
    if (filtersEl) {
        filtersEl.innerHTML = `
            <button onclick="renderDTRSWorkspace()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Tracking</button>
            <button onclick="renderDTRSDocuments()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Documents</button>
            <button onclick="renderDTRSDelivery()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low">Delivery</button>
            <button onclick="renderDTRSExceptions()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary text-white">Exceptions</button>
        `;
    }
    
    if (contentEl) {
        const mockExceptions = [
            { waybill: 'WB-001237', exceptionType: 'Damaged', description: 'Package arrived with visible damage', reportedDate: '2026-08-08 09:15', status: 'Under Investigation', carrier: 'FedEx' },
            { waybill: 'WB-001236', exceptionType: 'Short-Shipped', description: '2 items missing from delivery', reportedDate: '2026-08-07 16:30', status: 'Resolved', carrier: 'UPS' },
            { waybill: 'WB-001232', exceptionType: 'Delayed', description: 'Carrier delay due to weather', reportedDate: '2026-08-06 11:00', status: 'Resolved', carrier: 'DHL' }
        ];
        contentEl.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="border border-red-200 rounded-xl p-4 bg-red-50">
                    <p class="text-2xl font-bold text-red-800">1</p>
                    <p class="text-sm text-red-600">Open Exceptions</p>
                </div>
                <div class="border border-emerald-200 rounded-xl p-4 bg-emerald-50">
                    <p class="text-2xl font-bold text-emerald-800">2</p>
                    <p class="text-sm text-emerald-600">Resolved</p>
                </div>
                <div class="border border-amber-200 rounded-xl p-4 bg-amber-50">
                    <p class="text-2xl font-bold text-amber-800">3</p>
                    <p class="text-sm text-amber-600">Total (MTD)</p>
                </div>
            </div>
            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Waybill</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Exception Type</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30">Description</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Reported Date</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Carrier</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 whitespace-nowrap sticky right-0 bg-surface-container-low/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    ${mockExceptions.map(e => `
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-4 py-3 text-sm text-on-surface font-medium whitespace-nowrap">${e.waybill}</td>
                            <td class="px-4 py-3 text-sm text-on-surface">${e.exceptionType}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant">${e.description}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${e.reportedDate}</td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant whitespace-nowrap">${e.carrier}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs px-2 py-0.5 rounded-full ${e.status === 'Resolved' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}">${e.status}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white">
                                <button class="text-xs text-primary hover:underline">View Details</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
        `;
    }
    confirmDashboardAction('Showing exception log');
}

function exportDTRSReport() {
    const mockShipments = [
        { id: 'TRK-778210', carrier: 'Batangas Hub Logistics', status: 'In Transit' },
        { id: 'MMS-556231', carrier: 'Metro Manila Supply Co.', status: 'Out for Delivery' }
    ];
    const csv = 'Tracking #,Carrier,Status\n' + mockShipments.map(s => `${s.id},${s.carrier},${s.status}`).join('\n');
    downloadCSV(csv, 'dtrs-shipments.csv');
    confirmDashboardAction('Exported shipments to CSV');
}

// CSV download utility
function downloadCSV(csvContent, filename) {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Add modals for SWS and DTRS
function addModuleModals(moduleId) {
    const existingModals = document.querySelectorAll('.module-specific-modal');
    existingModals.forEach(m => m.remove());

    if (moduleId === 'sws') {
        const modalHTML = `
            <div id="sws-scan-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200 module-specific-modal" onclick="if(event.target.id==='sws-scan-modal') closeDashboardModal('sws-scan-modal')">
                <div class="bg-surface rounded-3xl max-w-md w-full p-6 shadow-2xl border border-outline-variant/30 relative transform scale-95 transition-transform duration-200" onclick="event.stopPropagation()">
                    <button type="button" onclick="closeDashboardModal('sws-scan-modal')" class="absolute top-4 right-4 p-1.5 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors" title="Close modal">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                            <span class="material-symbols-outlined text-2xl">qr_code_scanner</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-headline font-bold text-on-surface">Scan Asset QR</h3>
                            <p class="text-xs text-on-surface-variant">Scan QR code to look up asset</p>
                        </div>
                    </div>
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-on-surface mb-1">QR Code / Barcode</label>
                            <input type="text" id="sws-qr-input" class="w-full px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary" placeholder="Enter or scan QR code">
                        </div>
                        <div id="qr-reader" class="hidden"></div>
                        <button type="button" id="start-camera-btn" onclick="startQRScanner()" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-outline-variant/50 text-on-surface font-medium text-sm hover:bg-surface-container-low transition-colors cursor-pointer">
                            <span class="material-symbols-outlined text-lg">photo_camera</span>
                            <span>Open Camera Scanner</span>
                        </button>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/20">
                        <button type="button" onclick="closeDashboardModal('sws-scan-modal')" class="px-4 py-2 rounded-xl border border-outline-variant/50 text-on-surface font-medium text-sm hover:bg-surface-container-low transition-colors cursor-pointer">Cancel</button>
                        <button type="button" onclick="submitSWSScan()" class="flex items-center gap-1.5 px-5 py-2 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm shadow-md shadow-primary/20 transition-all cursor-pointer">
                            <span class="material-symbols-outlined text-base">check</span>
                            <span>Scan</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    } else if (moduleId === 'dtrs') {
        const modalHTML = `
            <div id="dtrs-waybill-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200 module-specific-modal" onclick="if(event.target.id==='dtrs-waybill-modal') closeDashboardModal('dtrs-waybill-modal')">
                <div class="bg-surface rounded-3xl max-w-md w-full p-6 shadow-2xl border border-outline-variant/30 relative transform scale-95 transition-transform duration-200" onclick="event.stopPropagation()">
                    <button type="button" onclick="closeDashboardModal('dtrs-waybill-modal')" class="absolute top-4 right-4 p-1.5 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors" title="Close modal">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                            <span class="material-symbols-outlined text-2xl">local_shipping</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-headline font-bold text-on-surface">Generate Waybill</h3>
                            <p class="text-xs text-on-surface-variant">Generate QR-coded waybill for shipment</p>
                        </div>
                    </div>
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-on-surface mb-1">Shipment ID</label>
                            <input type="text" id="dtrs-shipment-input" class="w-full px-3 py-2 rounded-lg border border-outline-variant/50 focus:outline-none focus:border-primary" placeholder="Enter shipment ID">
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/20">
                        <button type="button" onclick="closeDashboardModal('dtrs-waybill-modal')" class="px-4 py-2 rounded-xl border border-outline-variant/50 text-on-surface font-medium text-sm hover:bg-surface-container-low transition-colors cursor-pointer">Cancel</button>
                        <button type="button" onclick="submitDTRSWaybill()" class="flex items-center gap-1.5 px-5 py-2 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm shadow-md shadow-primary/20 transition-all cursor-pointer">
                            <span class="material-symbols-outlined text-base">download</span>
                            <span>Generate</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
}

function submitSWSScan() {
    const input = document.getElementById('sws-qr-input');
    if (input) {
        const result = scanAssetQR(input.value);
        closeDashboardModal('sws-scan-modal');
        if (result) {
            renderSWSRecords();
        }
    }
}

function submitDTRSWaybill() {
    const input = document.getElementById('dtrs-shipment-input');
    if (input) {
        generateWaybillQR(input.value);
        closeDashboardModal('dtrs-waybill-modal');
    }
}

// SWS Functions
function generateAssetQR(assetId) {
    const qrData = `ASSET:${assetId}`;
    confirmDashboardAction(`Generated QR code for asset ${assetId}`);
    return { assetId, qrData, generatedAt: new Date().toISOString() };
}

function scanAssetQR(scannedValue) {
    const mockAssets = [
        { id: 'AST-001', name: 'Laptop Dell XPS 15', zone: 'Ready for Dispatch', location: 'Zone A-12-03' },
        { id: 'AST-002', name: 'Monitor 27" 4K', zone: 'Pending IT Configuration', location: 'Zone B-08-01' },
        { id: 'AST-003', name: 'Keyboard Wireless', zone: 'Unassigned', location: 'Zone C-05-02' }
    ];
    const asset = mockAssets.find(a => scannedValue.includes(a.id));
    if (asset) {
        confirmDashboardAction(`Found asset: ${asset.name} in ${asset.zone} at ${asset.location}`);
        return asset;
    }
    confirmDashboardAction('Asset not found');
    return null;
}

function updateAssetZone(assetId, newZone) {
    const zones = ['Unassigned', 'Pending IT Configuration', 'Ready for Dispatch', 'Returned/Inspection', 'Decommissioned/E-Waste'];
    if (!zones.includes(newZone)) {
        confirmDashboardAction('Invalid zone');
        return false;
    }
    confirmDashboardAction(`Asset ${assetId} moved to ${newZone}`);
    return { assetId, newZone, timestamp: new Date().toISOString() };
}

function getWarehouseCapacity() {
    return [
        { zone: 'Zone A', capacity: 500, used: 320, utilization: 64 },
        { zone: 'Zone B', capacity: 300, used: 180, utilization: 60 },
        { zone: 'Zone C', capacity: 250, used: 40, utilization: 16 },
        { zone: 'Zone D', capacity: 150, used: 0, utilization: 0 }
    ];
}

// IMS Functions
function checkReorderAlerts() {
    const mockAssets = [
        { assetId: 'AST-1001', name: 'Dell XPS 15 Laptop', currentStock: 5, reorderThreshold: 20 },
        { assetId: 'AST-2001', name: 'Dell OptiPlex Desktop', currentStock: 8, reorderThreshold: 15 },
        { assetId: 'AST-7001', name: 'Ergonomic Office Chair', currentStock: 0, reorderThreshold: 50 }
    ];
    return mockAssets.filter(asset => asset.currentStock < asset.reorderThreshold);
}

function adjustStockCount(sku, newCount, reason) {
    confirmDashboardAction(`Stock adjusted for ${sku}: ${newCount} units (${reason})`);
    return { sku, newCount, reason, timestamp: new Date().toISOString() };
}

function trackLicenseRenewals() {
    const today = new Date();
    return [
        { license: 'Adobe Creative Cloud', renewalDate: '2026-08-15', daysUntilRenewal: 7 },
        { license: 'Microsoft Office 365', renewalDate: '2026-09-01', daysUntilRenewal: 24 },
        { license: 'Autodesk AutoCAD', renewalDate: '2026-10-15', daysUntilRenewal: 68 }
    ].sort((a, b) => a.daysUntilRenewal - b.daysUntilRenewal);
}

function getStockMovementHistory(sku) {
    return [
        { date: '2026-08-01', type: 'in', quantity: 50, reason: 'Purchase Receipt', performedBy: 'John D.', zone: 'IT Storage Room' },
        { date: '2026-08-05', type: 'out', quantity: 10, reason: 'Internal Transfer', performedBy: 'Maria S.', zone: '3rd Floor Cage' },
        { date: '2026-08-07', type: 'adjustment', quantity: -2, reason: 'Damaged Goods', performedBy: 'Alex R.', zone: 'IT Storage Room' },
        { date: '2026-08-08', type: 'in', quantity: 25, reason: 'New Stock Arrival', performedBy: 'John D.', zone: 'IT Storage Room' },
        { date: '2026-08-09', type: 'out', quantity: 5, reason: 'Staff Assignment', performedBy: 'Sarah L.', zone: '3rd Floor Cage' }
    ];
}

// PSM Functions
function checkBudgetAllocation(requestId) {
    const mockBudgets = {
        'REQ-2026-016': { requested: 25000, available: 50000, status: 'pass' },
        'REQ-2026-017': { requested: 75000, available: 50000, status: 'fail' }
    };
    return mockBudgets[requestId] || { requested: 0, available: 0, status: 'unknown' };
}

function compareVendorQuotes(itemId) {
    return [
        { vendor: 'TechCorp Solutions', price: 24000, leadTime: 5 },
        { vendor: 'Global Hardware Ltd.', price: 26000, leadTime: 7 },
        { vendor: 'DataHub Systems', price: 23500, leadTime: 10 }
    ];
}

function routeApprovalWorkflow(requestId, amount) {
    if (amount < 1000) return { nextApprover: 'Manager' };
    if (amount < 10000) return { nextApprover: 'Director' };
    return { nextApprover: 'VP' };
}

function createRequisition(formData) {
    const reqId = `REQ-2026-${Math.floor(Math.random() * 900) + 100}`;
    confirmDashboardAction(`Requisition ${reqId} created with status Pending`);
    return { reqId, status: 'Pending', ...formData, createdAt: new Date().toISOString() };
}

// SVM Functions
function calculateVendorScorecard(vendorId) {
    return {
        vendorId,
        deliveryLeadTime: 8.5,
        defectRate: 0.02,
        warrantyRating: 4.5,
        overallScore: 87
    };
}

function getVendorContracts(vendorId) {
    return [
        { type: 'Service Agreement', expiry: '2026-12-31', status: 'Active' },
        { type: 'Tax Document', expiry: null, status: 'On File' }
    ];
}

function rateCourierPerformance(courierId) {
    const mockRatings = {
        'COUR-001': { name: 'Batangas Hub Logistics', onTimeRate: 94, damageRate: 0.5, rating: 4.2 },
        'COUR-002': { name: 'Metro Manila Supply Co.', onTimeRate: 91, damageRate: 1.2, rating: 3.9 }
    };
    return mockRatings[courierId] || { name: 'Unknown', onTimeRate: 0, damageRate: 0, rating: 0 };
}

// POM Functions
function createPurchaseOrder(formData) {
    const poId = `PO-2026-${Math.floor(Math.random() * 900) + 100}`;
    confirmDashboardAction(`Purchase Order ${poId} created with status Draft`);
    return { poId, status: 'Draft', ...formData, createdAt: new Date().toISOString() };
}

function getPOStatusPipeline() {
    return {
        draft: [{ id: 'PO-2026-001', amount: 25000 }],
        approved: [{ id: 'PO-2026-002', amount: 75000 }],
        sent: [{ id: 'PO-2026-003', amount: 15000 }],
        partiallyReceived: [{ id: 'PO-2026-004', amount: 50000 }],
        closed: [{ id: 'PO-2026-005', amount: 10000 }]
    };
}

function matchThreeWay(poId) {
    return {
        poId,
        poAmount: 25000,
        goodsReceiptAmount: 25000,
        invoiceAmount: 24800,
        discrepancy: 200,
        status: 'Discrepancy Found'
    };
}

function flagOverduePOs() {
    return [
        { id: 'PO-2026-006', expectedDelivery: '2026-08-01', daysOverdue: 7 },
        { id: 'PO-2026-007', expectedDelivery: '2026-08-03', daysOverdue: 5 }
    ];
}

// DTRS Functions
function generateWaybillQR(shipmentId) {
    const waybillData = `WAYBILL:${shipmentId}`;
    confirmDashboardAction(`Generated waybill QR for shipment ${shipmentId}`);
    return { shipmentId, waybillData, generatedAt: new Date().toISOString() };
}

function logChainOfCustody(assetId, event) {
    return {
        assetId,
        event,
        timestamp: new Date().toISOString(),
        actor: 'Admin User'
    };
}

function recordAccountabilitySignature(formId, signatureData) {
    confirmDashboardAction(`Accountability signature recorded for ${formId}`);
    return { formId, signatureData, recordedAt: new Date().toISOString() };
}

function trackAssetReturn(assetId) {
    confirmDashboardAction(`Return shipment logged for asset ${assetId}`);
    return { assetId, status: 'Returned', timestamp: new Date().toISOString() };
}

function getModuleKPIs(moduleId) {
    const kpiMap = {
        'sws': [
            { label: 'Total Assets', value: '1,247', icon: 'inventory_2', tone: 'neutral', trend: '+12', trendDirection: 'up' },
            { label: 'Zone Utilization', value: '68%', icon: 'warehouse', tone: 'positive', trend: '+5%', trendDirection: 'up' },
            { label: 'Pending Tasks', value: '23', icon: 'task_alt', tone: 'caution', trend: '-3', trendDirection: 'down' },
            { label: 'Cycle Count Accuracy', value: '99.2%', icon: 'check_circle', tone: 'positive', trend: '+0.3%', trendDirection: 'up' }
        ],
        'ims': [
            { label: 'Total SKUs', value: '3,892', icon: 'inventory_2', tone: 'neutral', trend: '+45', trendDirection: 'up' },
            { label: 'Low Stock Alerts', value: '12', icon: 'warning', tone: 'caution', trend: '+2', trendDirection: 'up' },
            { label: 'Stock Accuracy', value: '98.5%', icon: 'check_circle', tone: 'positive', trend: '+0.2%', trendDirection: 'up' },
            { label: 'Pending Adjustments', value: '8', icon: 'edit', tone: 'neutral', trend: '-1', trendDirection: 'down' }
        ],
        'psm': [
            { label: 'Open Requisitions', value: '34', icon: 'description', tone: 'neutral', trend: '+5', trendDirection: 'up' },
            { label: 'Pending Approval', value: '7', icon: 'pending', tone: 'caution', trend: '-2', trendDirection: 'down' },
            { label: 'Avg Approval Time', value: '2.3 days', icon: 'schedule', tone: 'positive', trend: '-0.5d', trendDirection: 'down' },
            { label: 'Active RFQs', value: '15', icon: 'request_quote', tone: 'neutral', trend: '+3', trendDirection: 'up' }
        ],
        'svm': [
            { label: 'Active Vendors', value: '48', icon: 'business', tone: 'neutral', trend: '+2', trendDirection: 'up' },
            { label: 'Avg Score', value: '4.6/5', icon: 'star', tone: 'positive', trend: '+0.1', trendDirection: 'up' },
            { label: 'Expiring Contracts', value: '5', icon: 'event', tone: 'caution', trend: '+1', trendDirection: 'up' },
            { label: 'On-Time Delivery', value: '94%', icon: 'local_shipping', tone: 'positive', trend: '+2%', trendDirection: 'up' }
        ],
        'pom': [
            { label: 'Open POs', value: '72', icon: 'receipt_long', tone: 'neutral', trend: '+8', trendDirection: 'up' },
            { label: 'Overdue POs', value: '6', icon: 'warning', tone: 'caution', trend: '+1', trendDirection: 'up' },
            { label: 'PO Value (MTD)', value: '$245K', icon: 'attach_money', tone: 'positive', trend: '+$32K', trendDirection: 'up' },
            { label: '3-Way Match Rate', value: '97%', icon: 'check_circle', tone: 'positive', trend: '+1%', trendDirection: 'up' }
        ],
        'dtrs': [
            { label: 'Active Shipments', value: '28', icon: 'local_shipping', tone: 'neutral', trend: '+4', trendDirection: 'up' },
            { label: 'In Transit', value: '19', icon: 'flight', tone: 'positive', trend: '+3', trendDirection: 'up' },
            { label: 'Exceptions', value: '3', icon: 'error', tone: 'caution', trend: '-1', trendDirection: 'down' },
            { label: 'Avg Delivery Time', value: '2.1 days', icon: 'schedule', tone: 'positive', trend: '-0.2d', trendDirection: 'down' }
        ]
    };
    return kpiMap[moduleId] || [];
}

function getModuleIcon(moduleName) {
    const moduleIconMap = {
        'Smart Warehousing System (SWS)': 'warehouse',
        'Inventory Management System (IMS)': 'inventory_2',
        'Procurement & Sourcing Management (PSM)': 'shopping_cart',
        'Supplier Vendor Management (SVM)': 'business',
        'Purchase Order Management (POM)': 'receipt_long',
        'Distribution & Transportation Routing System (DTRS)': 'local_shipping'
    };
    return moduleIconMap[moduleName] || 'dashboard';
}

// QR Scanner Functions
let html5QrCode = null;

function startQRScanner() {
    const qrReader = document.getElementById('qr-reader');
    const startBtn = document.getElementById('start-camera-btn');
    
    if (!qrReader) return;
    
    qrReader.classList.remove('hidden');
    startBtn.classList.add('hidden');
    
    html5QrCode = new Html5Qrcode("qr-reader");
    const config = { fps: 10, qrbox: { width: 250, height: 250 } };
    
    html5QrCode.start({ facingMode: "environment" }, config, onQRCodeScanned)
        .catch(err => {
            console.error("Error starting QR scanner:", err);
            alert("Unable to access camera. Please ensure camera permissions are granted.");
            stopQRScanner();
        });
}

function onQRCodeScanned(decodedText, decodedResult) {
    const qrInput = document.getElementById('sws-qr-input');
    if (qrInput) {
        qrInput.value = decodedText;
    }
    stopQRScanner();
}

function stopQRScanner() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            html5QrCode.clear();
            html5QrCode = null;
        }).catch(err => {
            console.error("Error stopping QR scanner:", err);
        });
    }
    
    const qrReader = document.getElementById('qr-reader');
    const startBtn = document.getElementById('start-camera-btn');
    
    if (qrReader) qrReader.classList.add('hidden');
    if (startBtn) startBtn.classList.remove('hidden');
}

// Hardware Barcode Scanner Support
let barcodeBuffer = '';
let barcodeTimeout = null;

document.addEventListener('keydown', function(event) {
    // Check if input is from barcode scanner (rapid keypresses ending with Enter)
    if (event.key === 'Enter' && barcodeBuffer.length > 0) {
        // Barcode scan complete
        const activeElement = document.activeElement;
        if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
            activeElement.value = barcodeBuffer;
            activeElement.dispatchEvent(new Event('input', { bubbles: true }));
            activeElement.dispatchEvent(new Event('change', { bubbles: true }));
        }
        barcodeBuffer = '';
        event.preventDefault();
    } else if (event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
        // Build barcode buffer
        clearTimeout(barcodeTimeout);
        barcodeBuffer += event.key;
        barcodeTimeout = setTimeout(() => {
            barcodeBuffer = '';
        }, 100); // Reset if no input for 100ms (not a scanner)
    }
});

// Generate QR Code Function
function generateQRCode(assetId) {
    // Using a simple QR code generation via API
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(assetId)}`;
    
    const modalHTML = `
        <div id="qr-code-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200 module-specific-modal" onclick="if(event.target.id==='qr-code-modal') closeDashboardModal('qr-code-modal')">
            <div class="bg-surface rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-outline-variant/30 relative transform scale-95 transition-transform duration-200" onclick="event.stopPropagation()">
                <button type="button" onclick="closeDashboardModal('qr-code-modal')" class="absolute top-4 right-4 p-1.5 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors" title="Close modal">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-2xl">qr_code</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-headline font-bold text-on-surface">Generate QR Code</h3>
                        <p class="text-xs text-on-surface-variant">Asset ID: ${assetId}</p>
                    </div>
                </div>
                <div class="flex justify-center mb-5">
                    <img src="${qrUrl}" alt="QR Code" class="w-48 h-48 border border-outline-variant/30 rounded-lg">
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/20">
                    <button type="button" onclick="downloadQRCode('${qrUrl}', '${assetId}')" class="flex items-center gap-1.5 px-5 py-2 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm shadow-md shadow-primary/20 transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-base">download</span>
                        <span>Download</span>
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modal = document.getElementById('qr-code-modal');
    if (modal) {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('div.transform').classList.remove('scale-95');
        }, 10);
    }
}

function downloadQRCode(qrUrl, assetId) {
    const link = document.createElement('a');
    link.href = qrUrl;
    link.download = `qr-${assetId}.png`;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Warehouse Capacity Data for Zone Map
function getWarehouseCapacity() {
    return [
        { zone: 'Zone A', used: 12, capacity: 20, utilization: 60, category: 'Laptops' },
        { zone: 'Zone B', used: 15, capacity: 20, utilization: 75, category: 'Peripherals' },
        { zone: 'Zone C', used: 18, capacity: 20, utilization: 90, category: 'Monitors' },
        { zone: 'Zone D', used: 19, capacity: 20, utilization: 95, category: 'Accessories' }
    ];
}

// Get utilization color class based on percentage
function getUtilizationColorClass(utilization) {
    if (utilization >= 95) return 'bg-red-50 border-red-200 text-red-800';
    if (utilization >= 80) return 'bg-orange-50 border-orange-200 text-orange-800';
    if (utilization >= 50) return 'bg-amber-50 border-amber-200 text-amber-800';
    return 'bg-emerald-50 border-emerald-200 text-emerald-800';
}

// Get progress bar color based on percentage
function getProgressBarColor(utilization) {
    if (utilization >= 95) return 'bg-red-500';
    if (utilization >= 80) return 'bg-orange-500';
    if (utilization >= 50) return 'bg-amber-500';
    return 'bg-emerald-500';
}
