<?php
require_once 'RiskPredictor.php';
$pageTitle = "Early Warning";
include 'includes/header.php';
$predictor = new RiskPredictor();

$predictions    = $predictor->getPredictions();
$atRiskStudents = $predictor->getAtRiskStudents();
$summary        = $predictor->getRiskSummary();

// Calculate statistics
$totalStudents = count($atRiskStudents);
$highRisk      = array_filter($atRiskStudents, function($s) { return $s['risk_level'] == 'High';   });
$mediumRisk    = array_filter($atRiskStudents, function($s) { return $s['risk_level'] == 'Medium'; });
$criticalRisk  = array_filter($atRiskStudents, function($s) { return $s['risk_score'] > 0.8;       });

// FIX: $mediumCount was defined inside the HTML below (inside a PHP block
// within an echo), making it unavailable to the Chart.js data block later
// in the same file. The original code produced an "Undefined variable"
// notice and the chart's medium segment always showed 0. Define it here.
$mediumCount = count($mediumRisk);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Early Warning System - <?php echo SCHOOL_NAME; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        .header {
            background: #227172;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .stat-card.high-risk   { border-left: 4px solid #e74c3c; }
        .stat-card.medium-risk { border-left: 4px solid #f39c12; }
        .stat-card.low-risk    { border-left: 4px solid #2ecc71; }

        .stat-number { font-size: 2.5em; font-weight: bold; margin: 10px 0; }

        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .students-table, .chart-container, .intervention-panel {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2d8b81; color: white; }

        .risk-badge {
            padding: 5px 10px;
            border-radius: 3px;
            color: white;
            font-size: 0.9em;
        }
        .risk-high   { background: #e74c3c; }
        .risk-medium { background: #f39c12; }
        .risk-low    { background: #2ecc71; }

        .action-btn {
            padding: 5px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            color: white;
            margin: 2px;
        }
        .btn-notify  { background: #3498db; }
        .btn-counsel { background: #9b59b6; }
        .btn-parent  { background: #e67e22; }

        .filters {
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 5px;
        }
        .filters select, .filters input {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        .alert-box {
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            margin-bottom: 15px;
            color: #856404;
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Header -->
    <div class="header">
        <h1>&#128202; Student Academic Performance Early-Warning System</h1>
        <p><?php echo SCHOOL_NAME; ?> &mdash; <?php echo date('F j, Y'); ?></p>
    </div>

    <?php if ($predictions === false): ?>
    <!-- FIX: Show a user-friendly error when the ML API is unreachable
         instead of silently showing 0 students. -->
    <div class="alert-box">
        &#9888; Could not reach the prediction API. Make sure <strong>api_server.py</strong>
        is running (<code>python api_server.py</code>) and the model has been trained
        (<code>python initial_training.py</code>).
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card high-risk">
            <h3>High Risk Students</h3>
            <div class="stat-number"><?php echo count($highRisk); ?></div>
            <p>Immediate intervention required</p>
        </div>

        <div class="stat-card medium-risk">
            <h3>Medium Risk</h3>
            <!-- FIX: $mediumCount is now defined at the top of the file,
                 so it renders correctly here AND in the chart below. -->
            <div class="stat-number"><?php echo $mediumCount; ?></div>
            <p>Monitoring needed</p>
        </div>

        <div class="stat-card low-risk">
            <h3>Critical Cases</h3>
            <div class="stat-number"><?php echo count($criticalRisk); ?></div>
            <p>Risk score above 80%</p>
        </div>

        <div class="stat-card">
            <h3>Total Monitored</h3>
            <div class="stat-number"><?php echo $totalStudents; ?></div>
            <p>Students in early warning system</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <label>Filter by Risk Level:</label>
        <select id="riskFilter" onchange="filterTable()">
            <option value="all">All Levels</option>
            <option value="High">High Risk</option>
            <option value="Medium">Medium Risk</option>
            <option value="Low">Low Risk</option>
        </select>

        <label>Search Student:</label>
        <input type="text" id="studentSearch" placeholder="Enter name or ID..." onkeyup="filterTable()">

        <label>Class:</label>
        <select id="classFilter" onchange="filterTable()">
            <option value="all">All Classes</option>
            <option value="1">Form 1</option>
            <option value="2">Form 2</option>
            <option value="3">Form 3</option>
            <option value="4">Form 4</option>
        </select>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Students Table -->
        <div class="students-table">
            <h2>At-Risk Students</h2>
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Risk Level</th>
                        <th>Risk Score</th>
                        <th>Risk Factors</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($atRiskStudents as $student):
                        $factors = json_decode($student['factors_json'], true);
                    ?>
                    <!-- FIX: Added data-class attribute to each row so the
                         JavaScript classFilter can read the actual class_id
                         value. The original filterTable() function read
                         cells[2].textContent which was "Form 1", "Form 2" etc.
                         but compared against option values "Form 1", "Form 2"
                         — this worked, but the <option> values in the original
                         HTML were "Form 1" (with prefix) while class_id in DB
                         is just "1". Using data attributes avoids the mismatch. -->
                    <tr data-class="<?php echo htmlspecialchars($student['class_id']); ?>">
                        <td><?php echo htmlspecialchars($student['registration_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td>Form <?php echo htmlspecialchars($student['class_id']); ?></td>
                        <td>
                            <span class="risk-badge risk-<?php echo strtolower($student['risk_level']); ?>">
                                <?php echo $student['risk_level']; ?>
                            </span>
                        </td>
                        <td><?php echo number_format($student['risk_score'] * 100, 1); ?>%</td>
                        <td>
                            <?php if (!empty($factors)): ?>
                                <ul style="font-size:0.9em; padding-left:15px;">
                                    <?php foreach ($factors as $factor => $value): ?>
                                        <li><?php echo ucwords(str_replace('_', ' ', $factor)); ?>: <?php echo htmlspecialchars($value); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                No specific factors identified
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="action-btn btn-notify"
                                    onclick="intervene(<?php echo (int)$student['student_id']; ?>, 'Notified')">
                                Notify
                            </button>
                            <button class="action-btn btn-counsel"
                                    onclick="intervene(<?php echo (int)$student['student_id']; ?>, 'Counselling')">
                                Counsel
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Right Panel -->
        <div>
            <div class="chart-container">
                <h3>Risk Distribution</h3>
                <canvas id="riskChart"></canvas>
            </div>

            <div class="intervention-panel" style="margin-top:20px;">
                <h3>Quick Actions</h3>
                <button onclick="retrainModel()" class="action-btn btn-notify" style="width:100%;margin:5px 0;">
                    &#128260; Retrain Prediction Model
                </button>
                <button onclick="exportReport()" class="action-btn btn-counsel" style="width:100%;margin:5px 0;">
                    &#128202; Export Report (CSV)
                </button>
                <button onclick="sendAlerts()" class="action-btn btn-parent" style="width:100%;margin:5px 0;">
                    &#128231; Send Parent Alerts
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Risk Distribution Chart
    // FIX: $mediumCount is now properly defined in PHP above,
    // so this value will be correct instead of always rendering as 0.
    const ctx = document.getElementById('riskChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['High Risk', 'Medium Risk', 'Low Risk'],
            datasets: [{
                data: [
                    <?php echo count($highRisk); ?>,
                    <?php echo $mediumCount; ?>,
                    <?php echo $totalStudents - count($highRisk) - $mediumCount; ?>
                ],
                backgroundColor: ['#e74c3c', '#f39c12', '#2ecc71']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Filter function
    // FIX: Class filter now reads data-class attribute (numeric class_id)
    // and compares it against the <option> values which are also numeric.
    // The original compared "Form 1" (cell text) against "Form 1" (option value)
    // which seemed fine, but broke because options were "Form 1", "Form 2" etc.
    // while the original HTML had option values as "Form 1" too — a fragile match.
    function filterTable() {
        const riskFilter   = document.getElementById('riskFilter').value;
        const searchTerm   = document.getElementById('studentSearch').value.toLowerCase();
        const classFilter  = document.getElementById('classFilter').value;
        const table        = document.getElementById('studentsTable');
        const rows         = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const row        = rows[i];
            const riskLevel  = row.cells[3].textContent.trim();
            const studentInfo= row.cells[1].textContent.toLowerCase();
            const classId    = row.getAttribute('data-class');

            let show = true;

            if (riskFilter !== 'all' && riskLevel !== riskFilter) show = false;
            if (searchTerm && !studentInfo.includes(searchTerm))   show = false;
            if (classFilter !== 'all' && classId !== classFilter)  show = false;

            row.style.display = show ? '' : 'none';
        }
    }

    // Intervention function
    function intervene(studentId, status) {
        fetch('update_intervention.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ student_id: studentId, status: status })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Intervention updated successfully');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Could not update intervention'));
            }
        })
        .catch(() => alert('Could not reach update_intervention.php'));
    }

    // Retrain model
    function retrainModel() {
        if (confirm('This will retrain the prediction model with current data. Continue?')) {
            fetch('<?php echo ML_TRAIN_URL; ?>', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    alert(data.message || 'Model retrained successfully!');
                    location.reload();
                })
                .catch(() => alert('Could not reach the Python API. Is api_server.py running?'));
        }
    }

    function exportReport()  { window.location.href = 'export_report.php'; }

    function sendAlerts() {
        if (confirm('Send alerts to parents of high-risk students?')) {
            fetch('send_alerts.php', { method: 'POST' })
                .then(r => r.json())
                .then(data => alert(data.message || 'Alerts sent!'))
                .catch(() => alert('Could not reach send_alerts.php'));
        }
    }
</script>
<?php include 'includes/footer.php'; ?>