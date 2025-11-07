<?php
// --- PHP Configuration and Dummy Data ---
// General Info
$user_info = [
    'name' => 'John Doe',
    'admin_id' => 'ADM007',
    'avatar_url' => 'https://placehold.co/40x40/10B981/ffffff?text=JD',
];
$report_date = date('l, d F Y');

// 1. Key Metrics
$key_metrics = [
    'total_employees' => 24,
    'total_clock_ins' => 215,
    'total_clock_outs' => 199,
    'avg_hours_worked' => '36.5',
];

// 2. Weekly Activity Trends (Bar Chart Data)
$weekly_trends = [
    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
    'clock_ins' => [35, 40, 38, 30, 42],
    'clock_outs' => [30, 36, 32, 28, 38],
];

// 3. Monthly Attendance Trend (Line Chart Data)
$monthly_trend = [
    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    'work_hours' => [30, 35, 25, 28, 32, 30, 35, 38, 40, 42, 45, 43],
];

// 4. Peak Hours Analysis (Line Chart Data)
$peak_hours = [
    'labels' => ['08:30', '10:30', '12:30', '14:30', '16:30', '18:30'],
    'activity' => [20, 25, 32, 38, 28, 22],
];

// 5. Top Employees (Horizontal Bar Chart Data)
$top_employees = [
    'names' => ['Willem de Beer', 'Joe Brough', 'Emily Williams', 'Amy Wilson', 'John Doe'],
    'work_hours' => [58, 55, 48, 45, 42],
];

// Color palette
$primary_color = '#10B981';
$secondary_color = '#059669';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics Dashboard</title>

    
<style>
        /* Define CSS Variables for easy theme switching and exact color matching */
        :root {
            /* Light Mode Colors */
            --color-bg-body: #F4F5F7;
            --color-bg-header: #FFFFFF;
            --color-bg-panel: #FFFFFF;
            --color-text-base: #1F2937;
            --color-text-subtle: #6B7280;
            --color-input-bg: #F9FAFB;
            --color-border: #E5E7EB;
            --shadow-panel: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.03);
            --shadow-header: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        body.dark {
            /* Dark Mode Colors */
            --color-bg-body: #1A202C;
            --color-bg-header: #2D3748;
            --color-bg-panel: #2D3748;
            --color-text-base: #F9FAFB;
            --color-text-subtle: #A0AEC0;
            --color-input-bg: #4A5568;
            --color-border: #4A5568;
            --shadow-panel: none;
            --shadow-header: none;
        }

        /* 1. Base Setup & Transitions */
        html {
            transition: background-color 0.3s, color 0.3s;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-bg-body);
            color: var(--color-text-base);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            transition: background-color 0.3s, color 0.3s;
        }
        
        /* 2. Layout & Container */
        .container-wrapper {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .main-content-padding {
            padding-bottom: 2rem;
        }
        @media (min-width: 640px) {
            .container-wrapper { padding: 0 1.5rem; }
        }
        @media (min-width: 1024px) {
            .container-wrapper { padding: 0 2rem; }
        }

        /* 3. Header */
        .header {
            background-color: var(--color-bg-header);
            box-shadow: var(--shadow-header);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .nav-link {
            color: var(--color-text-subtle);
            text-decoration: none;
            transition: color 0.15s;
        }
        .nav-link:hover { color: <?php echo $primary_color; ?>; }
        .nav-link.active { color: <?php echo $primary_color; ?>; font-weight: 600; }
        
        /* Theme Toggle Button */
        .theme-toggle {
            padding: 0.5rem;
            border-radius: 9999px;
            cursor: pointer;
            transition: background-color 0.15s;
            color: var(--color-text-subtle);
        }
        .theme-toggle:hover { background-color: rgba(0, 0, 0, 0.05); }
        body.dark .theme-toggle:hover { background-color: rgba(255, 255, 255, 0.05); }
        
        /* 4. Panels (General Card Styles) */
        .panel {
            background-color: var(--color-bg-panel);
            border-radius: 0.75rem;
            box-shadow: var(--shadow-panel);
            border: 1px solid var(--color-border);
            transition: background-color 0.3s, border-color 0.3s, box-shadow 0.2s;
        }
        
        /* 5. Dashboard Header/User Info */
        .dashboard-header {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            padding-top: 2rem;
        }
        .dashboard-title {
            font-size: 1.875rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }
        .dashboard-date {
            color: var(--color-text-subtle);
        }

        @media (min-width: 640px) {
            .dashboard-header {
                flex-direction: row;
                align-items: center;
            }
        }
        .user-info {
            background-color: var(--color-bg-panel);
            border-radius: 0.75rem;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
            box-shadow: var(--shadow-panel);
            border: 1px solid var(--color-border);
        }
        @media (min-width: 640px) {
            .user-info { margin-top: 0; }
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 9999px;
            border: 2px solid <?php echo $primary_color; ?>;
        }
        .user-name-text {
            color: inherit;
        }
        .user-id-text {
            font-size: 0.875rem;
            color: var(--color-text-subtle);
        }


        /* 6. Filters Section */
        .filters-panel {
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 2.5rem; /* Wider gap for better spacing */
        }
        @media (min-width: 768px) {
            .filters-panel {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .filter-input {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--color-input-bg);
            color: var(--color-text-base);
            border-radius: 0.5rem;
            border: 1px solid var(--color-border);
            outline: none;
            appearance: none;
            transition: box-shadow 0.2s, border-color 0.2s;
            cursor: pointer; /* ADDED: Cursor pointer for all filter inputs */
        }
        .filter-input::placeholder { color: var(--color-text-subtle); opacity: 0.8; }
        .filter-input:hover { /* ADDED: Hover effect */
            border-color: <?php echo $primary_color; ?>;
        }
        .filter-input:focus {
            box-shadow: 0 0 0 2px <?php echo $primary_color; ?>;
            border-color: <?php echo $primary_color; ?>;
        }

        .btn-primary {
            padding: 0.75rem;
            background-color: <?php echo $primary_color; ?>;
            color: white;
            font-weight: bold;
            border-radius: 0.5rem;
            transition: background-color 0.2s, transform 0.1s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            border: none;
        }
        .btn-primary:hover {
            background-color: <?php echo $secondary_color; ?>;
            transform: translateY(-1px);
        }

        /* 7. Metric Cards Grid */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        @media (min-width: 1024px) {
            .metric-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        .metric-card {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: box-shadow 0.2s;
            cursor: default;
        }
        .metric-card:hover {
            box-shadow: 0 0 0 2px <?php echo $primary_color; ?> inset, var(--shadow-panel);
        }
        .metric-label-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .metric-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--color-text-subtle);
        }
        .metric-value {
            font-size: 2.25rem;
            font-weight: 700;
        }

        /* 8. Charts Grid */
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.5rem;
        }
        @media (min-width: 1024px) {
            .chart-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        .chart-container {
            padding: 1.5rem;
        }
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .chart-area {
            height: 320px;
        }
        
        /* 9. Utility Classes & Icons */
        .text-primary { color: <?php echo $primary_color; ?>; }
        .font-bold { font-weight: bold; }
        .font-semibold { font-weight: 600; }
        .icon-base {
            width: 24px;
            height: 24px;
        }
        .hidden { display: none; }
        
        /* 10. Custom Scrollbar (Dark Mode Only) */
        body.dark ::-webkit-scrollbar { width: 8px; }
        body.dark ::-webkit-scrollbar-thumb { background: <?php echo $primary_color; ?>; border-radius: 4px; }
        body.dark ::-webkit-scrollbar-track { background: var(--color-input-bg); }
    </style>

    
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
</head>
<body id="body">
    
<header class="header">
        <div class="container-wrapper header-content">
            <div class="text-xl font-bold">LOGO</div>
            <nav class="nav-links">
                <a href="#" class="nav-link active font-semibold">Analytics</a>
                <a href="#" class="nav-link">Logout</a>
                
<button id="themeToggle" class="theme-toggle">
                    <svg id="sunIcon" class="icon-base" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <svg id="moonIcon" class="icon-base hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 11.001 11.001 0 0015.354 20.354z"></path>
                    </svg>
                </button>
            </nav>
        </div>
    </header>

    
<main class="container-wrapper main-content-padding">
        
<div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Reports & Analytics</h1>
                <p class="dashboard-date"><?php echo $report_date; ?></p>
            </div>
            <div class="user-info">
                <img src="<?php echo $user_info['avatar_url']; ?>" alt="Avatar" class="user-avatar">
                <div>
                    <p class="font-semibold user-name-text"><?php echo $user_info['name']; ?></p>
                    <p class="user-id-text">Admin ID: <?php echo $user_info['admin_id']; ?></p>
                </div>
            </div>
        </div>

        
<div class="panel filters-panel">
            <input type="text" placeholder="Date Range" class="filter-input" />
            <select class="filter-input">
                <option>All Departments</option>
                <option>Sales</option>
                <option>Marketing</option>
                <option>Engineering</option>
            </select>
            <input type="text" placeholder="Search Employees..." class="filter-input" />
            <button class="btn-primary">
                Apply Filters
            </button>
        </div>

        
<div class="metric-grid">
            <?php
            $metric_details = [
                ['label' => 'Total Employees', 'value' => $key_metrics['total_employees'], 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon-base text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
                ['label' => 'Total Clock-In\'s', 'value' => $key_metrics['total_clock_ins'], 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon-base text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="12 6 12 12 16 14"/></svg>'],
                ['label' => 'Total Clock-Out\'s', 'value' => $key_metrics['total_clock_outs'], 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon-base text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 9a2.5 2.5 0 0 0-2.5-2.5H16l-3.5-4L9 6.5H4a2.5 2.5 0 0 0-2.5 2.5v5A2.5 2.5 0 0 0 4 16h5l3.5 4 3.5-4h3a2.5 2.5 0 0 0 2.5-2.5z"/><path d="M12 2v6h6"/></svg>'],
                ['label' => 'Avg. Hours Worked', 'value' => $key_metrics['avg_hours_worked'], 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon-base text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'],
            ];
            ?>

            <?php foreach ($metric_details as $metric): ?>
                <div class="panel metric-card">
                    <div class="flex-row items-center metric-label-group">
                        <?php echo $metric['icon']; ?>
                        <p class="metric-label"><?php echo $metric['label']; ?></p>
                    </div>
                    <p class="metric-value"><?php echo $metric['value']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        
<div class="chart-grid">
            <div class="panel chart-container">
                <h2 class="chart-title">Weekly Activity Trends</h2>
                <div class="chart-area">
                    <canvas id="weeklyActivityChart"></canvas>
                </div>
            </div>

            <div class="panel chart-container">
                <h2 class="chart-title">Monthly Attendance Trend</h2>
                <div class="chart-area">
                    <canvas id="monthlyAttendanceChart"></canvas>
                </div>
            </div>

            <div class="panel chart-container">
                <h2 class="chart-title">Peak Hours Analysis</h2>
                <div class="chart-area">
                    <canvas id="peakHoursChart"></canvas>
                </div>
            </div>

            <div class="panel chart-container">
                <h2 class="chart-title">Top 5 Most Active Employees</h2>
                <div class="chart-area">
                    <canvas id="topEmployeesChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    
<script>
        const PRIMARY_GREEN = '<?php echo $primary_color; ?>';
        const SECONDARY_GREEN = '<?php echo $secondary_color; ?>';
        const bodyElement = document.getElementById('body');
        const themeToggle = document.getElementById('themeToggle');
        const sunIcon = document.getElementById('sunIcon');
        const moonIcon = document.getElementById('moonIcon');

        // --- Theme Toggle Logic ---
        function setIcon(isDark) {
            if (isDark) {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            } else {
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
            }
        }

        function toggleTheme() {
            const isDark = bodyElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            setIcon(isDark);
            
            // Re-render charts to update colors
            Object.values(window.charts).forEach(chart => {
                updateChartColors(chart, isDark);
                chart.update();
            });
        }

        function loadTheme() {
            const savedTheme = localStorage.getItem('theme');
            let isDark = savedTheme === 'dark'; 

            if (isDark) {
                bodyElement.classList.add('dark');
            } else {
                bodyElement.classList.remove('dark');
            }
            setIcon(isDark);
        }

        themeToggle.addEventListener('click', toggleTheme);
        loadTheme();

        // --- Chart Configuration and Initialization ---
        // ChartDataLabels plugin is no longer registered as we are not using it.
        window.charts = {}; 

        function getChartStyle(isDark) {
            return {
                TEXT_COLOR: isDark ? '#F9FAFB' : '#1F2937', 
                GRID_COLOR: isDark ? '#4A5568' : '#E5E7EB',
                TOOLTIP_BG: isDark ? 'rgba(45, 55, 72, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                TOOLTIP_TEXT: isDark ? '#F9FAFB' : '#1F2937',
            };
        }

        function updateChartColors(chart, isDark) {
            isDark = isDark !== undefined ? isDark : bodyElement.classList.contains('dark');
            const styles = getChartStyle(isDark);

            const scales = chart.options.scales;
            if (scales.y) {
                scales.y.grid.color = styles.GRID_COLOR;
                scales.y.ticks.color = styles.TEXT_COLOR;
                if (scales.y.title) scales.y.title.color = styles.TEXT_COLOR;
            }
            if (scales.x) {
                scales.x.grid.color = styles.GRID_COLOR;
                scales.x.ticks.color = styles.TEXT_COLOR;
                if (scales.x.title) scales.x.title.color = styles.TEXT_COLOR;
            }

            const plugins = chart.options.plugins;
            if (plugins.tooltip) {
                plugins.tooltip.backgroundColor = styles.TOOLTIP_BG;
                plugins.tooltip.titleColor = styles.TOOLTIP_TEXT;
                plugins.tooltip.bodyColor = styles.TOOLTIP_TEXT;
            }
            if (plugins.legend) {
                plugins.legend.labels.color = styles.TEXT_COLOR;
            }
        }

        function createChart(id, config) {
            const chart = new Chart(document.getElementById(id), config);
            updateChartColors(chart, bodyElement.classList.contains('dark'));
            window.charts[id] = chart;
            return chart;
        }


        // --- Chart 1: Weekly Activity Trends (Bar Chart) ---
        createChart('weeklyActivityChart', {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($weekly_trends['labels']); ?>,
                datasets: [
                    {
                        label: 'Clock-In\'s',
                        data: <?php echo json_encode($weekly_trends['clock_ins']); ?>,
                        backgroundColor: PRIMARY_GREEN,
                        borderRadius: 4,
                    },
                    {
                        label: 'Clock-Out\'s',
                        data: <?php echo json_encode($weekly_trends['clock_outs']); ?>,
                        backgroundColor: SECONDARY_GREEN,
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { font: { family: 'Inter' } } },
                    tooltip: {
                        borderColor: PRIMARY_GREEN,
                        borderWidth: 1,
                        cornerRadius: 6,
                        titleFont: { family: 'Inter', weight: 'bold' },
                        bodyFont: { family: 'Inter' }
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Count' }
                    },
                    x: {
                        stacked: false,
                        title: { display: true, text: 'Day' }
                    }
                }
            }
        });

        // --- Chart 2: Monthly Attendance Trend (Line Chart) ---
        createChart('monthlyAttendanceChart', {
            type: 'line',
            data: {
                labels: <?php echo json_encode($monthly_trend['labels']); ?>,
                datasets: [{
                    label: 'Work hours',
                    data: <?php echo json_encode($monthly_trend['work_hours']); ?>,
                    borderColor: PRIMARY_GREEN,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: PRIMARY_GREEN,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { font: { family: 'Inter' } } },
                    tooltip: {
                        borderColor: PRIMARY_GREEN,
                        borderWidth: 1,
                        cornerRadius: 6,
                        titleFont: { family: 'Inter', weight: 'bold' },
                        bodyFont: { family: 'Inter' }
                    },
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Hours' } },
                    x: { title: { display: true, text: 'Month' } }
                }
            }
        });

        // --- Chart 3: Peak Hours Analysis (Line Chart) ---
        createChart('peakHoursChart', {
            type: 'line',
            data: {
                labels: <?php echo json_encode($peak_hours['labels']); ?>,
                datasets: [{
                    label: 'Activity Count',
                    data: <?php echo json_encode($peak_hours['activity']); ?>,
                    borderColor: PRIMARY_GREEN,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: PRIMARY_GREEN,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { font: { family: 'Inter' } } },
                    tooltip: {
                        borderColor: PRIMARY_GREEN,
                        borderWidth: 1,
                        cornerRadius: 6,
                        titleFont: { family: 'Inter', weight: 'bold' },
                        bodyFont: { family: 'Inter' }
                    },
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Activity' } },
                    x: { title: { display: true, text: 'Time' } }
                }
            }
        });

        // --- Chart 4: Top 5 Most Active Employees (Horizontal Bar Chart) ---
        createChart('topEmployeesChart', {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($top_employees['names']); ?>,
                datasets: [{
                    label: 'Work hours',
                    data: <?php echo json_encode($top_employees['work_hours']); ?>,
                    backgroundColor: PRIMARY_GREEN,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        borderColor: PRIMARY_GREEN,
                        borderWidth: 1,
                        cornerRadius: 6,
                        titleFont: { family: 'Inter', weight: 'bold' },
                        bodyFont: { family: 'Inter' }
                    },
                },
                scales: {
                    y: {
                        grid: { display: false },
                        title: { display: false }
                    },
                    x: {
                        beginAtZero: true,
                        title: { display: true, text: 'Work hours' }
                    }
                }
            }
        });
        
        // Update charts immediately after load to ensure correct initial colors
        window.onload = () => {
             Object.values(window.charts).forEach(chart => {
                updateChartColors(chart, bodyElement.classList.contains('dark'));
                chart.update('none');
            });
        };
    </script>
</body>
</html>
