<?php
include('db_connection.php');
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

$teacherId = $_SESSION['teacher_id'];
$teacherName = $_SESSION['teacher_name'];

// Get adviser’s assigned sections
$assigned_sections = [];
$secRes = $conn->query("
    SELECT section_id 
    FROM section_advisers 
    WHERE teacher_id = '$teacherId'
");
while ($row = $secRes->fetch_assoc()) {
    $assigned_sections[] = $row['section_id'];
}
$assigned_section_ids = !empty($assigned_sections) ? implode(",", $assigned_sections) : "0";

// Count pending students (only adviser's sections)
$pending_students_count = 0;
if (!empty($assigned_sections)) {
    $pending_students_sql = "
        SELECT COUNT(*) as total 
        FROM pending_students p
        JOIN sections s ON p.section = s.section_name
        WHERE s.id IN ($assigned_section_ids)
    ";
    $pending_students_count = $conn->query($pending_students_sql)->fetch_assoc()['total'];
}

// Count students (only adviser's sections)
$student_count = 0;
if (!empty($assigned_sections)) {
    $student_sql = "
        SELECT COUNT(*) as total 
        FROM students st
        JOIN sections s ON st.section = s.section_name
        WHERE s.id IN ($assigned_section_ids)
    ";
    $student_count = $conn->query($student_sql)->fetch_assoc()['total'];
}

// Count RFID records (global)
$rfid_count = $conn->query("SELECT COUNT(*) as total FROM attendance")->fetch_assoc()['total'];

// Attendance data for this week (only adviser's students)
$current_week_start = $_GET['week_start'] ?? date('Y-m-d', strtotime("monday this week"));
$attendance_data = [];
$week_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

foreach ($week_days as $index => $day) {
    $current_date = date('Y-m-d', strtotime($current_week_start . " +$index days"));

    // Total students (adviser’s sections only)
    $total_students = 0;
    if (!empty($assigned_sections)) {
        $total_sql = "
            SELECT COUNT(*) as total_students 
            FROM students st
            JOIN sections s ON st.section = s.section_name
            WHERE s.id IN ($assigned_section_ids)
        ";
        $total_students = $conn->query($total_sql)->fetch_assoc()['total_students'];
    }

    if ($total_students > 0) {
        // Present count (only adviser’s students)
        $present_sql = "
            SELECT COUNT(DISTINCT a.student_id) as present_count 
            FROM attendance a
            JOIN students st ON a.student_id = st.id
            JOIN sections s ON st.section = s.section_name
            WHERE a.attendance_date = '$current_date'
            AND a.status = 'Present'
            AND s.id IN ($assigned_section_ids)
        ";
        $present_count = $conn->query($present_sql)->fetch_assoc()['present_count'];

        $attendance_rate = round(($present_count / $total_students) * 100, 2);
    } else {
        $attendance_rate = 0;
    }

    $attendance_data[] = $attendance_rate;
}

// Fetch principal's name
$principal_name = "Not Set";
$principal_query = $conn->query("SELECT principal_name FROM principal LIMIT 1");
if ($principal_query && $principal_query->num_rows > 0) {
    $row = $principal_query->fetch_assoc();
    $principal_name = $row['principal_name'];
}

// Generate last 5 weeks dropdown
$weeks = [];
for ($i = 0; $i < 5; $i++) {
    $start = date('Y-m-d', strtotime("monday -$i week"));
    $end   = date('Y-m-d', strtotime($start . " +4 days"));
    $weeks[] = [
        'start' => $start,
        'end'   => $end,
        'label' => date('M j', strtotime($start)) . " - " . date('M j', strtotime($end))
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Adviser Dashboard | Isiera</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .dashboard-container {
            margin-left: 320px;
            padding: 20px;
            font-family: 'Segoe UI', sans-serif;
            background-color: #fdfdfd;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card { display:flex; align-items:center; gap:15px; padding:20px; border-radius:12px; box-shadow:0 4px 8px rgba(0,0,0,0.06); transition:0.3s; }
        .card ion-icon { font-size:32px; flex-shrink:0; }
        .card h3 { margin:0; font-size:16px; font-weight:600; color:#333; }
        .card p { margin:2px 0 0; font-size:22px; font-weight:bold; color:#2a2a2a; }
        .attendance-info { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
        .attendance-date-range { font-size:14px; color:#666; background:#f5f5f5; padding:5px 10px; border-radius:5px; }
        .principal-info { display:inline-flex; align-items:center; gap:3px; font-size:17px; font-weight:bold; color:#28a745; }
    </style>
</head>
<body>
<?php include('adviser_sidebar.php'); ?>
<div class="dashboard-container">
    <div style="text-align: right; margin-bottom: 20px;">
        <div class="principal-info">
            <ion-icon name="person-circle-outline" style="font-size:20px;"></ion-icon>
            School Principal: <?= htmlspecialchars($principal_name) ?>
        </div>
    </div>

    <div class="dashboard-cards">
        <a href="adviser_student_verification.php" style="text-decoration:none;">
            <div class="card" style="background-color:#fff3cd; color:#856404; cursor:pointer;">
                <ion-icon name="person-add-outline"></ion-icon>
                <div>
                    <h3>Pending Students</h3>
                    <p><?= htmlspecialchars($pending_students_count ?? 0) ?></p>
                </div>
            </div>
        </a>

        <a href="adviser_enrollment.php" style="text-decoration:none;">
            <div class="card" style="background-color:#e2f0cb; color:#2e7d32; cursor:pointer;">
                <ion-icon name="school-outline"></ion-icon>
                <div>
                    <h3>Total Students</h3>
                    <p><?= htmlspecialchars($student_count ?? 0) ?></p>
                </div>
            </div>
        </a>
    </div>

    <div style="background:#fff; padding:20px; margin-bottom:30px; border-radius:12px; box-shadow:0 4px 8px rgba(0,0,0,0.06);">
        <div class="attendance-info">
            <h2 style="margin:0; color:#444;">📊 Attendance Rate</h2>
            <form method="get" style="display:inline;">
                <select name="week_start" onchange="this.form.submit()">
                    <?php foreach ($weeks as $w): ?>
                        <option value="<?= $w['start'] ?>" <?= ($w['start'] == $current_week_start) ? 'selected' : '' ?>>
                            <?= $w['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <canvas id="attendanceChart" height="100"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('attendanceChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Monday','Tuesday','Wednesday','Thursday','Friday'],
        datasets: [{
            label: 'Attendance Rate (%)',
            data: <?= json_encode($attendance_data ?? [0,0,0,0,0]) ?>,
            fill: true,
            backgroundColor: 'rgba(173, 216, 230, 0.2)',
            borderColor: 'rgba(100, 149, 237, 1)',
            borderWidth: 2,
            tension: 0.3,
            pointBackgroundColor: '#6495ED'
        }]
    },
    options: {
        scales: {
            y: { beginAtZero:true, max:100, ticks: { stepSize:10, callback: (v)=> v + '%' } }
        },
        plugins: {
            legend: { display:false },
            tooltip: { callbacks: { label: (context) => context.parsed.y + '% attendance' } }
        }
    }
});
</script>
</body>
</html>
