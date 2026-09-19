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
        'Smart Warehousing (SWS)': 'warehouse',
        'Inventory Management (IMS)': 'inventory_2',
        'Procurement & Sourcing (PSM)': 'shopping_bag',
        'Supplier / Vendor (SVM)': 'handshake',
        'Purchase Order (POM)': 'receipt_long',
        'Logistics & Records (DTRS)': 'local_shipping',
        'Administration & Security': 'admin_panel_settings'
    };

    const getModuleIcon = moduleName => {
        return moduleIconMap[moduleName] || 'apps';
    };

    const actionIconMap = {
        'Approve purchase orders': 'task_alt',
        'Review supplier ratings': 'star',
        'Update inventory counts': 'inventory_2',
        'Track outstanding shipments': 'local_shipping'
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
        return `
            <div class="donut-chart" style="background: conic-gradient(${segments.map(segment => `${segment.color}${segment.value}`).join(', ')});"></div>
        `;
    };

    document.title = `${subsystem.title} — Dashboard`;
    dashboardHeading.textContent = 'Welcome back, Admin';
    dashboardCopy.textContent = `Here's what's happening in ${subsystem.title} today.`;
    if (breadcrumbCategory) breadcrumbCategory.textContent = subsystem.title;
    sidebarBrandTitle.textContent = 'Supply Chain';
    sidebarBrandCategory.textContent = 'Management Console';

    // Build sidebar using the exact matching styles as sidebar.php
    sidebarSubsystemModulesNav.innerHTML = subsystem.modules.map((module) => {
        const mod = normalizeModule(module);
        return `
            <div class="sidebar-module-group" data-module-id="${mod.id}">
                <button type="button" class="sidebar-module-toggle w-full flex items-center justify-between px-3 py-2 rounded-xl border border-transparent text-slate-700 hover:bg-slate-100 font-medium text-xs transition">
                    <span class="flex items-center gap-2.5 truncate">
                        <span class="material-symbols-outlined text-[18px] text-indigo-600">${mod.icon || getModuleIcon(mod.name)}</span>
                        <span class="truncate">${mod.name}</span>
                    </span>
                    <span class="material-symbols-outlined sidebar-chevron text-[16px] text-slate-400 transition-transform duration-200">expand_more</span>
                </button>
                <div class="sidebar-submenu pl-4 pr-1 py-1 space-y-1" style="max-height: 0px; display: none;">
                    ${mod.subnav.map(sub => `
                        <a href="${sub.href}" class="sidebar-submenu-link flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">
                            <span class="material-symbols-outlined sidebar-submenu-icon text-[16px] text-slate-400">${sub.icon}</span>
                            <span class="truncate">${sub.label}</span>
                        </a>
                    `).join('')}
                </div>
            </div>
        `;
    }).join('');
    sidebarSubsystemNavPanel.classList.remove('hidden');

    // Render Critical Alerts
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
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    // Render Stats Grid
    dashboardStatsGrid.innerHTML = subsystem.stats.map(stat => {
        const delta = stat.tone === 'positive'
            ? { text: '+12% vs last month', isPositive: true }
            : stat.tone === 'caution'
            ? { text: '-2.4% vs last month', isPositive: false }
            : { text: '+1.8% vs last month', isPositive: true };

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
            return `
                <svg class="w-16 h-8" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <polyline fill="none" stroke="${delta.isPositive ? '#10b981' : '#ef4444'}" stroke-width="2" points="${points}" />
                </svg>
            `;
        };

        return `
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 flex flex-col justify-between gap-4 overflow-hidden relative metric-card cursor-pointer hover:shadow-md transition-all" data-metric="${stat.label}">
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

    // Quick Actions
    dashboardQuickActionsList.innerHTML = subsystem.quickActions.map(action => `
        <button type="button" class="quick-action-button" data-action="${action}">
            <span class="flex items-center gap-3">
                <span class="material-symbols-outlined text-lg">${actionIconMap[action] || 'bolt'}</span>
                <span>${action}</span>
            </span>
            <span class="material-symbols-outlined text-sm">arrow_forward</span>
        </button>
    `).join('');

    // Analytics Overview
    const renderAnalytics = analytics => {
        const overviewTitle = analytics?.overviewTitle || 'Performance overview';
        const overviewMetric = analytics?.overviewMetric || subsystem.stats[0]?.value || 'Overview';
        const overviewSubtitle = analytics?.overviewSubtitle || subsystem.description || '';
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

    // Recent Activity Table
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

    // Activity Filter Buttons
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
            document.querySelectorAll('.activity-row').forEach(row => {
                row.style.display = (filter === 'all' || row.dataset.category === filter) ? '' : 'none';
            });
        });
    });

    // Quick Action Trigger
    document.querySelectorAll('.quick-action-button').forEach(btn => {
        btn.addEventListener('click', () => {
            openQuickActionModal(btn.dataset.action);
        });
    });

    // Sidebar Accordion Handler (Pure Toggle — No automatic navigation to firstHref)
    initSidebarSubmenus();

    // Responsive Desktop Collapse Toggle
    const sidebarToggle = document.getElementById('desktop-sidebar-toggle');
    const sidebar = document.getElementById('app-sidebar');
    const sidebarToggleIcon = document.getElementById('sidebar-toggle-icon');
    let isCollapsed = false;

    if (sidebarToggle && sidebar && sidebarToggleIcon) {
        sidebarToggle.addEventListener('click', () => {
            isCollapsed = !isCollapsed;
            sidebar.classList.toggle('w-20', isCollapsed);
            sidebar.classList.toggle('w-72', !isCollapsed);
            sidebarToggleIcon.textContent = isCollapsed ? 'menu' : 'menu_open';
            document.querySelectorAll('.sidebar-module-toggle span:not(.material-symbols-outlined), .sidebar-chevron, .sidebar-submenu').forEach(el => {
                el.classList.toggle('hidden', isCollapsed);
            });
        });
    }
});

function initSidebarSubmenus() {
    document.querySelectorAll('.sidebar-module-toggle').forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            const parentGroup = btn.closest('.sidebar-module-group');
            if (!parentGroup) return;

            const isOpen = parentGroup.classList.toggle('open');
            const chevron = btn.querySelector('.sidebar-chevron');
            const submenu = parentGroup.querySelector('.sidebar-submenu');

            if (submenu) {
                submenu.style.display = isOpen ? 'block' : 'none';
                submenu.style.maxHeight = isOpen ? '500px' : '0px';
                if (chevron) {
                    chevron.classList.toggle('rotate-180', isOpen);
                }
            }
        };
    });
}