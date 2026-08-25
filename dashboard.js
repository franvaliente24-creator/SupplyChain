document.addEventListener('DOMContentLoaded', () => {
    const subsystemId = getSubsystemFromUrl() || 'supply-chain';
    const subsystem = getSubsystemById(subsystemId);
    const activeModuleId = getModuleFromUrl();

    const dashboardHeading = document.getElementById('dashboard-heading');
    const dashboardCopy = document.getElementById('dashboard-copy');
    const dashboardStatsGrid = document.getElementById('dashboard-stats-grid');
    const dashboardCharts = document.getElementById('dashboard-charts');
    const dashboardChartOverview = document.getElementById('dashboard-chart-overview');
    const dashboardChartBreakdown = document.getElementById('dashboard-chart-breakdown');
    const dashboardQuickActionsList = document.getElementById('dashboard-quick-actions-list');
    const dashboardActivityBody = document.getElementById('dashboard-activity-tbody');
    const breadcrumbCategory = document.getElementById('breadcrumb-category');
    const sidebarBrandTitle = document.getElementById('sidebar-brand-title');
    const sidebarBrandCategory = document.getElementById('sidebar-brand-category');
    const sidebarSubsystemNavPanel = document.getElementById('sidebar-subsystem-nav-panel');
    const sidebarSubsystemModulesNav = document.getElementById('sidebar-subsystem-modules-nav');
    const sidebarDashboardLink = document.getElementById('sidebar-dashboard-link');

    if (!dashboardHeading || !dashboardCopy || !dashboardStatsGrid || !dashboardCharts || !dashboardChartOverview || !dashboardChartBreakdown || !dashboardQuickActionsList || !dashboardActivityBody || !sidebarBrandTitle || !sidebarBrandCategory || !sidebarSubsystemNavPanel || !sidebarSubsystemModulesNav) return;

    if (sidebarDashboardLink) {
        sidebarDashboardLink.href = getDashboardHref(subsystemId);
        sidebarDashboardLink.classList.toggle('active', !activeModuleId);
    }

    const normalizeStatValue = value => {
        if (typeof value === 'number') return Math.min(100, Math.max(5, Math.round(value)));
        if (!value) return 60;
        const parsed = parseFloat(String(value).replace(/[^0-9.-]+/g, ''));
        return Number.isFinite(parsed) ? Math.min(100, Math.max(5, Math.round(parsed))) : 60;
    };

    const moduleIconMap = {
        'Client Management Subsystem': 'groups',
        'Applicant Registration and Profiling System': 'person_add',
        'Recruitment and Selection Subsystem': 'search',
        'Job Order Management Subsystem': 'assignment',
        'Deployment and Assignment Subsystem': 'engineering',
        'Employee Information Management System (HRIS)': 'badge',
        'Timekeeping and Attendance System': 'schedule',
        'Leave and Absence Management System': 'beach_access',
        'Payroll and Compensation System': 'attach_money',
        'Performance Management Subsystem': 'star',
        'Training and Development Subsystem': 'school',
        'Document and Contract Management System': 'description',
        'Government Contribution & Compliance Subsystem': 'gavel',
        'Benefits and Loans Management System': 'health_and_safety',
        'Separation and Exit Clearance Subsystem': 'verified_user',
        'Health, Safety, and Welfare Subsystem': 'safety_check',
        'Legal and Compliance Subsystem': 'gavel',
        'System Administration and Security Subsystem': 'admin_panel_settings',
        'Reports, Analytics, and Dashboards System': 'insights',
        'Asset and Equipment Issuance Tracker': 'inventory',
        'General Ledger': 'account_balance_wallet',
        'Accounts Payable (AP)': 'receipt_long',
        'Accounts Receivable (AR)': 'payments',
        'Disbursement Management': 'account_balance',
        'Collection Management': 'currency_exchange',
        'Budget Management': 'account_balance',
        'Cash Management': 'account_balance_wallet',
        'Financial Reporting & Analytics': 'insights',
        'Tax Management': 'request_quote',
        'Smart Warehousing System (SWS)': 'warehouse',
        'Inventory Management System (IMS)': 'inventory_2',
        'Procurement & Sourcing Management (PSM)': 'shopping_bag',
        'Supplier / Vendor Management (SVM)': 'handshake',
        'Purchase Order Management (POM)': 'receipt_long',
        'Document Tracking & Logistics Records System (DTRS)': 'local_shipping',
        'Fleet & Vehicle Management (FVM)': 'directions_car',
        'Vehicle Reservation & Dispatch System (VRDS)': 'directions_bus',
        'Driver and Trip Performance Monitoring': 'timeline',
        'Fuel Management System': 'local_gas_station',
        'Transport Cost Analysis & Optimization (TCAO)': 'analytics',
        'Route Planning & Optimization': 'map',
        'Mobile Fleet Command App': 'emoji_transportation',
        'Facilities Reservation System': 'meeting_room',
        'Visitor Management System': 'badge',
        'Document Management (Archiving System)': 'folder',
        'Records Retention & Compliance': 'folder_shared',
        'Legal Management System': 'gavel',
        'Contract Management': 'description',
        'Dashboard & Data Visualization System': 'dashboard',
        'KPI Monitoring & Performance Tracking System': 'trending_up',
        'Predictive Analytics System': 'insights',
        'Custom Report Generation System': 'insert_chart',
        'Data Aggregation & Integration System': 'storage',
        'Exportable Reports & Decision Support System': 'file_download',
        'Lead and Client Tracking System': 'track_changes',
        'Communication History Management': 'forum',
        'Client Satisfaction and Survey System': 'emoji_events',
        'Follow-up Reminder System': 'notifications',
        'Opportunity Pipeline Visualization': 'timeline'
    };

    const getModuleIcon = moduleName => {
        if (!moduleName) return 'apps';
        return moduleIconMap[moduleName] || 'apps';
    };

    const getStatusBadge = status => {
        const normalized = String(status || '').toLowerCase();
        const statusMap = {
            completed: 'status-pill status-pill-success',
            ready: 'status-pill status-pill-success',
            updated: 'status-pill status-pill-info',
            pending: 'status-pill status-pill-warning',
            scheduled: 'status-pill status-pill-info',
            new: 'status-pill status-pill-accent'
        };
        return statusMap[normalized] || 'status-pill status-pill-neutral';
    };

    const actionIconMap = {
        'New Transaction': 'add',
        'Upload Invoice': 'upload_file',
        'Generate Report': 'insert_chart',
        'Budget Planning': 'query_stats',
        'Tax Filing': 'receipt_long',
        'Approve invoices': 'task_alt',
        'Create budget plan': 'account_balance',
        'Review cash forecast': 'analytics',
        'Export financial statements': 'file_download'
    };

    const renderLineChart = dataPoints => {
        const points = dataPoints.map((point, index) => {
            const value = Math.min(100, Math.max(20, Number(point.value) || 20));
            return {
                x: 20 + index * 60,
                y: 150 - value
            };
        });

        const linePath = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
        const fillPath = `${linePath} L ${points[points.length - 1].x} 150 L ${points[0].x} 150 Z`;

        return `
            <div class="dashboard-line-chart">
                <svg viewBox="0 0 320 180" aria-hidden="true">
                    <defs>
                        <linearGradient id="lineGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#4f46e5" stop-opacity="0.9" />
                            <stop offset="100%" stop-color="#c7d2fe" stop-opacity="0.08" />
                        </linearGradient>
                    </defs>
                    <path d="${fillPath}" fill="url(#lineGradient)" />
                    <path d="${linePath}" fill="none" stroke="#4338ca" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                    ${points.map(point => `
                        <circle cx="${point.x}" cy="${point.y}" r="4" fill="#4338ca" stroke="#ffffff" stroke-width="2" />
                    `).join('')}
                </svg>
            </div>
        `;
    };

    const renderDonutChart = segments => {
        const gradient = segments.map((segment, index) => `${segment.color} ${index === 0 ? '0%' : ''} ${segments.slice(0, index + 1).reduce((acc, item) => acc + parseInt(item.value, 10), 0)}%`).join(', ');
        return `
            <div class="donut-chart" style="background: conic-gradient(${segments.map(segment => `${segment.color} ${segment.value}`).join(', ')});"></div>
        `;
    };

    if (!subsystem) {
        dashboardHeading.textContent = 'Welcome back, Admin';
        dashboardCopy.textContent = 'Open the module selector and choose a subsystem to view its dedicated dashboard.';
        if (breadcrumbCategory) breadcrumbCategory.textContent = 'No subsystem selected';
        dashboardStatsGrid.innerHTML = '';
        dashboardCharts.innerHTML = '';
        dashboardQuickActionsList.innerHTML = '<p class="text-sm text-on-surface-variant">Select a subsystem from the module selector to display statistics, charts, and activity.</p>';
        dashboardActivityBody.innerHTML = '<tr><td class="px-6 py-4 text-on-surface-variant" colspan="4">No activity available. Select a subsystem to view activity logs.</td></tr>';
        sidebarBrandTitle.textContent = 'No subsystem selected';
        sidebarBrandCategory.textContent = 'Choose a subsystem from the selector.';
        sidebarSubsystemModulesNav.innerHTML = '';
        sidebarSubsystemNavPanel.classList.add('hidden');
        return;
    }

    document.title = `${subsystem.title} — Dashboard`;
    dashboardHeading.textContent = 'Welcome back, Admin';
    dashboardCopy.textContent = `Here's what's happening in ${subsystem.title} today.`;
    if (breadcrumbCategory) breadcrumbCategory.textContent = subsystem.title;
    sidebarBrandTitle.textContent = subsystem.title;
    sidebarBrandCategory.textContent = subsystem.category;
    sidebarSubsystemModulesNav.innerHTML = subsystem.modules.map((module) => {
        const mod = normalizeModule(module);
        const isActive = activeModuleId === mod.id;
        const hasSubnav = mod.subnav && mod.subnav.length > 0;
        
        if (hasSubnav) {
            // Module with submenu
            const isModuleOpen = isActive;
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
                            const isSubActive = sub.id === defaultViewId;
                            // Real PHP-backed pages (SWS, IMS, PSM, SVM, POM, DTRS) declare
                            // an `href` in subsystems.js — navigate straight there.
                            // Mock/demo subsystems declare a `render` function name instead
                            // and get rendered client-side inside module.html.
                            if (sub.href) {
                                return `
                                    <a href="${sub.href}"
                                       class="sidebar-submenu-link ${isSubActive ? 'active' : ''}"
                                       data-view="${sub.id}">
                                        <span class="material-symbols-outlined sidebar-submenu-icon">${sub.icon}</span>
                                        <span class="truncate">${sub.label}</span>
                                    </a>
                                `;
                            }
                            return `
                                <a href="#"
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
    sidebarSubsystemNavPanel.classList.remove('hidden');

    const criticalAlertsContainer = document.getElementById('dashboard-critical-alerts');
    if (criticalAlertsContainer && subsystem.criticalAlerts && subsystem.criticalAlerts.length > 0) {
        criticalAlertsContainer.classList.remove('hidden');
        criticalAlertsContainer.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                ${subsystem.criticalAlerts.map(alert => `
                    <div class="rounded-2xl p-4 border ${alert.severity === 'high' ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200'}">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl ${alert.severity === 'high' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'} flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-xl">${alert.type === 'low-stock' ? 'inventory_2' : 'local_shipping'}</span>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-sm ${alert.severity === 'high' ? 'text-red-900' : 'text-amber-900'}">${alert.title}</h4>
                                <p class="text-xs ${alert.severity === 'high' ? 'text-red-700' : 'text-amber-700'} mt-1">${alert.message}</p>
                                <div class="mt-2 space-y-1">
                                    ${alert.items.slice(0, 2).map(item => `
                                        <p class="text-xs ${alert.severity === 'high' ? 'text-red-600' : 'text-amber-600'} truncate">${item}</p>
                                    `).join('')}
                                    ${alert.items.length > 2 ? `<p class="text-xs ${alert.severity === 'high' ? 'text-red-600' : 'text-amber-600'}">+${alert.items.length - 2} more</p>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    } else if (criticalAlertsContainer) {
        criticalAlertsContainer.classList.add('hidden');
    }

    dashboardStatsGrid.innerHTML = subsystem.stats.map(stat => {
        const deltaMap = {
            'Pipeline Value': { text: '+12.4% vs last month', isPositive: true },
            'Open Requests': { text: '+8.6% vs last week', isPositive: true },
            'Active Clients': { text: '+3.2% vs last quarter', isPositive: true },
            'Fill Rate': { text: '+4.5% vs target', isPositive: true }
        };
        const defaultDelta = stat.tone === 'positive'
            ? { text: '+12% vs last month', isPositive: true }
            : stat.tone === 'caution'
            ? { text: '-2.4% vs last month', isPositive: false }
            : { text: '+1.8% vs last month', isPositive: true };
        const delta = stat.delta || deltaMap[stat.label] || defaultDelta;

        const renderSparkline = (trend) => {
            if (!trend || !Array.isArray(trend) || trend.length < 2) return '';
            const max = Math.max(...trend);
            const min = Math.min(...trend);
            const range = max - min || 1;
            const points = trend.map((val, i) => {
                const x = (i / (trend.length - 1)) * 100;
                const y = 100 - ((val - min) / range) * 80;
                return `${x},${y}`;
            }).join(' ');
            const isPositive = trend[trend.length - 1] >= trend[0];
            return `
                <svg class="w-16 h-8" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <polyline
                        fill="none"
                        stroke="${isPositive ? '#10b981' : '#ef4444'}"
                        stroke-width="2"
                        points="${points}"
                    />
                </svg>
            `;
        };

        return `
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 flex flex-col justify-between gap-4 overflow-hidden relative cursor-pointer hover:shadow-md hover:border-slate-300 transition-all metric-card" data-metric="${stat.label}">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">${stat.label}</p>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[20px]">${stat.icon}</span>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-headline font-bold text-slate-900 leading-none">${stat.value}</h3>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold ${delta.isPositive ? 'text-emerald-700 bg-emerald-50' : 'text-rose-700 bg-rose-50'} text-xs">
                        <span class="material-symbols-outlined text-[15px]">${delta.isPositive ? 'trending_up' : 'trending_down'}</span>
                        <span>${delta.text}</span>
                    </span>
                    ${renderSparkline(stat.trend)}
                </div>
            </div>
        `;
    }).join('');

    dashboardQuickActionsList.innerHTML = subsystem.quickActions.map(action => `
        <button type="button" class="quick-action-button" data-action="${action}">
            <span class="flex items-center gap-3">
                <span class="material-symbols-outlined text-lg">${actionIconMap[action] || 'bolt'}</span>
                <span>${action}</span>
            </span>
            <span class="material-symbols-outlined text-sm">arrow_forward</span>
        </button>
    `).join('');

    const renderAnalytics = analytics => {
        const overviewTitle = analytics?.overviewTitle || 'Performance overview';
        const overviewMetric = analytics?.overviewMetric || subsystem.stats[0]?.value || 'Overview';
        const overviewSubtitle = analytics?.overviewSubtitle || subsystem.description || `Track ${subsystem.title} performance.`;
        const overviewTrend = analytics?.overviewTrend || 'Updated now';
        const overviewData = Array.isArray(analytics?.overviewData) && analytics.overviewData.length
            ? analytics.overviewData
            : subsystem.stats.map(stat => ({ label: stat.label, value: normalizeStatValue(stat.value) }));
        const overviewHighlights = Array.isArray(analytics?.overviewHighlights) && analytics.overviewHighlights.length
            ? analytics.overviewHighlights
            : overviewData.slice(0, 2).map(item => ({ label: item.label, value: `${item.value}%` }));
        const breakdownTitle = analytics?.breakdownTitle || 'Detailed breakdown';
        const breakdownTotal = analytics?.breakdownTotal || 'Key metrics';
        const breakdownSegments = Array.isArray(analytics?.breakdownSegments) && analytics.breakdownSegments.length
            ? analytics.breakdownSegments
            : subsystem.stats.map(stat => ({ label: stat.label, value: stat.value, color: '#c7c4d8' }));

        dashboardChartOverview.innerHTML = `
            <div class="dashboard-chart-header">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-on-surface-variant mb-2">${overviewTitle}</p>
                    <h3 class="text-2xl font-headline font-bold text-on-surface">${overviewMetric}</h3>
                    <p class="text-sm text-on-surface-variant mt-2">${overviewSubtitle}</p>
                </div>
                <button class="dashboard-chart-filter-button inline-flex items-center gap-1.5 hover:border-slate-300 transition-colors">
                    <span>${overviewTrend}</span>
                    <span class="material-symbols-outlined text-[18px]">keyboard_arrow_down</span>
                </button>
            </div>
            ${renderLineChart(overviewData)}
            <div class="grid gap-3 sm:grid-cols-2 mt-5">
                ${overviewHighlights.map(item => `
                    <div class="rounded-2xl bg-surface-container-high p-4 border border-outline-variant/20">
                        <p class="text-xs text-on-surface-variant">${item.label}</p>
                        <p class="mt-2 text-sm font-semibold text-on-surface">${item.value}</p>
                    </div>
                `).join('')}
            </div>
        `;

        dashboardChartBreakdown.innerHTML = `
            <div class="dashboard-chart-header">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-on-surface-variant mb-2">${breakdownTitle}</p>
                    <h3 class="text-2xl font-headline font-bold text-on-surface">${breakdownTotal}</h3>
                </div>
            </div>
            <div class="grid gap-5 lg:grid-cols-[1fr_0.95fr] items-center">
                <div class="donut-chart-wrapper">
                    ${renderDonutChart(breakdownSegments)}
                    <div class="donut-center-text">
                        <p>Total</p>
                        <p>${breakdownTotal}</p>
                    </div>
                </div>
                <div class="space-y-3">
                    ${breakdownSegments.map(segment => `
                        <div class="donut-list-item">
                            <span class="flex items-center gap-3">
                                <span class="donut-list-color" style="background: ${segment.color};"></span>
                                <span>${segment.label}</span>
                            </span>
                            <span class="text-sm font-semibold text-on-surface">${segment.value}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    };

    renderAnalytics(subsystem.analytics);

    dashboardActivityBody.innerHTML = subsystem.activity.map(item => `
        <tr class="hover:bg-surface-container-lowest transition-colors activity-row" data-category="${item.category || 'All'}">
            <td class="px-6 py-4 text-sm text-on-surface">${item.label}</td>
            <td class="px-6 py-4 text-sm text-on-surface-variant">${item.status}</td>
            <td class="px-6 py-4 text-sm text-on-surface-variant">${item.time}</td>
            <td class="px-6 py-4">
                <button class="text-on-surface-variant hover:text-primary transition-colors p-1.5 rounded-md hover:bg-surface-container-low cursor-pointer" title="View Details">
                    <span class="material-symbols-outlined text-sm">visibility</span>
                </button>
            </td>
        </tr>
    `).join('');

    // Activity filter functionality
    const filterButtons = document.querySelectorAll('.activity-filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => {
                b.classList.remove('active', 'bg-surface', 'text-on-surface', 'shadow-sm');
                b.classList.add('text-on-surface-variant');
            });
            btn.classList.add('active', 'bg-surface', 'text-on-surface', 'shadow-sm');
            btn.classList.remove('text-on-surface-variant');

            const filter = btn.dataset.filter;
            const rows = document.querySelectorAll('.activity-row');
            rows.forEach(row => {
                if (filter === 'all' || row.dataset.category === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Initialize first filter button as active
    if (filterButtons.length > 0) {
        filterButtons[0].classList.add('bg-surface', 'text-on-surface', 'shadow-sm');
        filterButtons[0].classList.remove('text-on-surface-variant');
    }

    // Quick action modal functionality
    const quickActionButtons = document.querySelectorAll('.quick-action-button');
    quickActionButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const action = btn.dataset.action;
            openQuickActionModal(action);
        });
    });

    // Metric card click functionality
    const metricCards = document.querySelectorAll('.metric-card');
    metricCards.forEach(card => {
        card.addEventListener('click', () => {
            const metric = card.dataset.metric;
            console.log('Drill down into metric:', metric);
            confirmDashboardAction(null, `Opening detailed report for ${metric}...`);
        });
    });

    // Time range selector functionality
    const timeRangeSelector = document.getElementById('time-range-selector');
    if (timeRangeSelector) {
        timeRangeSelector.addEventListener('change', () => {
            const range = timeRangeSelector.value;
            console.log('Time range changed to:', range);
            confirmDashboardAction(null, `Updated dashboard to show last ${range} days`);
        });
    }

    // Sidebar navigation handling
    initSidebarNavigation();
    initSidebarSubmenus();

    // Sidebar collapse functionality
    const sidebarToggle = document.getElementById('desktop-sidebar-toggle');
    const sidebar = document.getElementById('app-sidebar');
    const sidebarToggleIcon = document.getElementById('sidebar-toggle-icon');
    let isCollapsed = false;

    if (sidebarToggle && sidebar && sidebarToggleIcon) {
        sidebarToggle.addEventListener('click', () => {
            isCollapsed = !isCollapsed;
            if (isCollapsed) {
                sidebar.classList.add('w-20');
                sidebar.classList.remove('w-72');
                sidebar.style.width = '';
                sidebarToggleIcon.textContent = 'menu';
                document.querySelectorAll('.sidebar-subsystem-link span:not(.material-symbols-outlined)').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('#sidebar-brand-title, #sidebar-brand-category').forEach(el => el.classList.add('hidden'));
            } else {
                sidebar.classList.remove('w-20');
                sidebar.classList.add('w-72');
                sidebar.style.width = '';
                sidebarToggleIcon.textContent = 'menu_open';
                document.querySelectorAll('.sidebar-subsystem-link span:not(.material-symbols-outlined)').forEach(el => el.classList.remove('hidden'));
                document.querySelectorAll('#sidebar-brand-title, #sidebar-brand-category').forEach(el => el.classList.remove('hidden'));
            }
        });
    }

    // Sidebar resize functionality
    const resizeHandle = document.getElementById('sidebar-resize-handle');
    if (resizeHandle && sidebar) {
        let isResizing = false;
        let startX, startWidth;

        resizeHandle.addEventListener('mousedown', (e) => {
            isResizing = true;
            startX = e.clientX;
            startWidth = sidebar.offsetWidth;
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        });

        document.addEventListener('mousemove', (e) => {
            if (!isResizing) return;
            const diff = e.clientX - startX;
            const newWidth = Math.max(200, Math.min(500, startWidth + diff));
            sidebar.style.width = newWidth + 'px';
            
            // Update collapsed state
            if (newWidth <= 100) {
                isCollapsed = true;
                sidebar.classList.add('w-20');
                sidebar.classList.remove('w-72');
                sidebarToggleIcon.textContent = 'menu';
                document.querySelectorAll('.sidebar-subsystem-link span:not(.material-symbols-outlined)').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('#sidebar-brand-title, #sidebar-brand-category').forEach(el => el.classList.add('hidden'));
            } else {
                isCollapsed = false;
                sidebar.classList.remove('w-20');
                sidebar.classList.remove('w-72');
                sidebarToggleIcon.textContent = 'menu_open';
                document.querySelectorAll('.sidebar-subsystem-link span:not(.material-symbols-outlined)').forEach(el => el.classList.remove('hidden'));
                document.querySelectorAll('#sidebar-brand-title, #sidebar-brand-category').forEach(el => el.classList.remove('hidden'));
            }
        });

        document.addEventListener('mouseup', () => {
            isResizing = false;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        });
    }
});

function initSidebarSubmenus() {
    // Handle module toggle clicks (accordion behavior + navigate to module)
    document.querySelectorAll('.sidebar-module-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const group = toggle.closest('.sidebar-module-group');
            const moduleId = toggle.dataset.module;
            
            // Toggle accordion
            if (group) {
                group.classList.toggle('open');
            }

            const firstSubmenuLink = group ? group.querySelector('.sidebar-submenu-link') : null;
            const firstHref = firstSubmenuLink ? firstSubmenuLink.getAttribute('href') : null;

            // Real PHP-backed module (SWS, IMS, PSM, SVM, POM, DTRS): the first
            // submenu item points straight at its real page — go there directly.
            if (firstHref && firstHref !== '#') {
                window.location.href = firstHref;
                return;
            }

            // Mock/demo subsystem: fall back to the client-rendered module.html shell.
            const subsystemId = getSubsystemFromUrl() || 'supply-chain';
            const defaultViewId = firstSubmenuLink ? firstSubmenuLink.dataset.view : null;
            const moduleHref = `module.html?subsystem=${encodeURIComponent(subsystemId)}&module=${encodeURIComponent(moduleId)}`;
            window.location.href = moduleHref + (defaultViewId ? `&view=${defaultViewId}` : '');
        });
    });

    // Handle submenu link clicks
    document.querySelectorAll('.sidebar-submenu-link').forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');

            // Real page link — let the browser navigate normally.
            if (href && href !== '#') {
                return;
            }

            e.preventDefault();
            e.stopPropagation();
            
            // Update active state for all submenu items in the same group
            const submenu = link.closest('.sidebar-submenu');
            if (submenu) {
                submenu.querySelectorAll('.sidebar-submenu-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            }

            // Get render function from data attribute and call it
            const renderFunc = link.dataset.render;
            if (renderFunc && window[renderFunc]) {
                // Update URL without full page reload
                const url = new URL(window.location);
                url.searchParams.set('view', link.dataset.view);
                window.history.pushState({}, '', url);
                
                // Call the render function
                window[renderFunc]();
            }
        });
    });
}

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
        
        // For submenu links, let the submenu click handler in initSidebarSubmenus handle this
        if (isSubmenuLink) {
            return;
        }

        // For all other links, let the default anchor behavior handle navigation
        // This ensures proper module switching and dashboard navigation
        return;
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
        location.reload();
    });
}