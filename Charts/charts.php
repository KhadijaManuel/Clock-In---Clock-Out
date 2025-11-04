<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reports & Analytics</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
        font-family: Arial, sans-serif;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .light-mode {
        --primary: #2eb28a;
        --card-bg: #d1fae5;
        --row-alt: #f0fdf4;
        background-color: #fff;
        color: #000;
    }

    .dark-mode {
        --primary: #2eb28a;
        --card-bg: #333;
        --row-alt: #444;
        background-color: #1e1e1e;
        color: #fff;
    }

    .toggle-switch {
        position: fixed;
        top: 15px;
        right: 20px;
        z-index: 1000;
    }

    .switch-label {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 30px;
        background-color: #cfcfcf;
        border-radius: 30px;
        cursor: pointer;
        transition: background-color 0.25s ease;
    }

    .switch-label.is-on {
        background-color: #444;
    }

    .switch-ball {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 24px;
        height: 24px;
        background-color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        color: #f59e0b;
        transition: transform 0.25s ease, color 0.25s ease;
    }

    .switch-label.is-on .switch-ball {
        transform: translateX(30px);
        color: #60a5fa;
    }

    .reports-header {
        text-align: center;
        background-color: var(--primary);
        color: white;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .filter-section {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    input[type="text"] {
        padding: 6px;
        border-radius: 6px;
        border: 1px solid #ccc;
        width: 250px;
    }

    .attendance-table {
        width: 90%;
        margin: 0 auto;
        border-collapse: collapse;
        background-color: var(--card-bg);
    }

    th, td {
        padding: 10px;
        border: 1px solid #ccc;
        text-align: center;
    }

    .attendance-table tbody tr:nth-child(even) {
        background-color: var(--row-alt);
    }

    .charts-grid {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 30px;
        margin: 30px 0;
    }

    .chart-card {
        flex: 1 1 400px;
        max-width: 400px;
        background-color: var(--card-bg);
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    }

    .chart-card h4 {
        background-color: var(--primary);
        color: white;
        padding: 8px;
        border-radius: 6px;
        text-align: center;
        margin-bottom: 10px;
    }

    .export-section {
        text-align: center;
        margin-top: 20px;
    }

    .export-btn {
        border: 1px solid var(--primary);
        background: none;
        color: var(--primary);
        padding: 8px 14px;
        border-radius: 6px;
        cursor: pointer;
    }
  </style>
</head>
<body class="light-mode">

  <!-- Toggle Switch -->
  <div class="toggle-switch">
    <input id="modeToggle" type="checkbox" hidden>
    <label class="switch-label" for="modeToggle">
      <span class="switch-ball"><span class="icon">☀️</span></span>
    </label>
  </div>

  <header class="reports-header">
    <h2>Reports & Analytics</h2>
  </header>

  <!-- Search -->
  <section class="filter-section">
    <input type="text" id="search" placeholder="Search by employee or department...">
  </section>

  <!-- Table -->
  <section class="table-section">
    <h3 style="text-align:center;">Employee Attendance Data</h3>
    <table class="attendance-table" id="attendanceTable">
      <thead>
        <tr>
          <th>Employee</th>
          <th>Department</th>
          <th>Hours Worked</th>
          <th>Hours Owed</th>
        </tr>
      </thead>
      <tbody>
        <?php
        include_once 'db.php';
        
        $query = "
            SELECT 
                CONCAT(e.first_name, ' ', e.last_name) as name,
                ec.department,
                hm.total_worked_hours as hoursWorked,
                hm.hours_owed as hoursOwed
            FROM hours_management hm
            JOIN employees e ON hm.employee_id = e.employee_id
            JOIN emp_classification ec ON e.classification_id = ec.classification_id
            WHERE hm.week_start = '2025-10-27' AND hm.week_end = '2025-10-31'
            ORDER BY e.first_name, e.last_name
        ";
        
        $result = $conn->query($query);
        $attendanceData = array();
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $attendanceData[] = $row;
                echo "<tr>
                    <td>{$row['name']}</td>
                    <td>{$row['department']}</td>
                    <td>{$row['hoursWorked']}</td>
                    <td>{$row['hoursOwed']}</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No data found for current week</td></tr>";
        }
        
        $json_data = json_encode($attendanceData);
        ?>
      </tbody>
    </table>
  </section>

  <!-- Charts -->
  <section class="charts-grid">
    <div class="chart-card">
      <h4>Total Hours by Employee</h4>
      <canvas id="barChart"></canvas>
    </div>
    <div class="chart-card">
      <h4>Weekly Hours Status</h4>
      <canvas id="pieChart"></canvas>
    </div>
  </section>

  <div class="export-section">
    <button class="export-btn" id="exportBtn">Export to CSV</button>
  </div>

  <script>
    let attendanceData = <?php echo $json_data; ?> || [];

    let barChartInstance, pieChartInstance;

    // --- Update charts ---
    function updateCharts(data) {
      const barCtx = document.getElementById('barChart').getContext('2d');
      const pieCtx = document.getElementById('pieChart').getContext('2d');

      if (barChartInstance) barChartInstance.destroy();
      if (pieChartInstance) pieChartInstance.destroy();

      // Determine text color based on current mode
      const isDarkMode = document.body.classList.contains('dark-mode');
      const textColor = isDarkMode ? '#fff' : '#000';

      barChartInstance = new Chart(barCtx, {
        type: 'bar',
        data: {
          labels: data.map(d => d.name),
          datasets: [{
            label: 'Hours Worked',
            data: data.map(d => parseInt(d.hoursWorked)),
            backgroundColor: '#22c55e'
          }]
        },
        options: { 
          responsive: true, 
          plugins: { 
            legend: { 
              labels: { 
                color: textColor 
              } 
            } 
          },
          scales: {
            x: {
              ticks: {
                color: textColor
              }
            },
            y: {
              ticks: {
                color: textColor
              }
            }
          }
        }
      });

      const totalWorked = data.reduce((sum, e) => sum + parseInt(e.hoursWorked), 0);
      const totalOwed = data.reduce((sum, e) => sum + parseInt(e.hoursOwed), 0);
      pieChartInstance = new Chart(pieCtx, {
        type: 'pie',
        data: {
          labels: ['Hours Worked', 'Hours Owed'],
          datasets: [{ 
            backgroundColor: ['#22c55e', '#f97316'], 
            data: [totalWorked, totalOwed] 
          }]
        },
        options: { 
          responsive: true,
          plugins: {
            legend: {
              labels: {
                color: textColor
              }
            }
          }
        }
      });
    }

    // --- Search filter ---
    const searchInput = document.getElementById('search');
    searchInput.addEventListener('keyup', function () {
      const filter = this.value.toLowerCase();
      const filteredData = attendanceData.filter(row => {
        return row.name.toLowerCase().includes(filter) || row.department.toLowerCase().includes(filter);
      });
      
      // Update table
      const tbody = document.querySelector('#attendanceTable tbody');
      tbody.innerHTML = '';
      filteredData.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${row.name}</td>
          <td>${row.department}</td>
          <td>${row.hoursWorked}</td>
          <td>${row.hoursOwed}</td>
        `;
        tbody.appendChild(tr);
      });
      
      updateCharts(filteredData);
    });

    // --- CSV export ---
    document.getElementById('exportBtn').addEventListener('click', () => {
      const rows = [
        ['Employee', 'Department', 'Hours Worked', 'Hours Owed'],
        ...attendanceData.map(d => [d.name, d.department, d.hoursWorked, d.hoursOwed])
      ];
      const csv = rows.map(r => r.join(',')).join('\n');
      const blob = new Blob([csv], { type: 'text/csv' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'attendance_report.csv';
      link.click();
    });

    // --- Dark/Light toggle ---
    const toggle = document.getElementById('modeToggle');
    const label = document.querySelector('.switch-label');
    const icon = document.querySelector('.icon');
    
    toggle.addEventListener('change', () => {
      document.body.classList.toggle('dark-mode', toggle.checked);
      document.body.classList.toggle('light-mode', !toggle.checked);
      label.classList.toggle('is-on', toggle.checked);
      icon.textContent = toggle.checked ? '🌙' : '☀️';
      
      // Update charts with new colors
      updateCharts(attendanceData);
    });

    updateCharts(attendanceData);

  </script>
</body>
</html>