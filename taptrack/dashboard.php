<?php
// Start session to access user role
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userRole = $_SESSION['user_role'] ?? '';

include('db_connection.php');

// Count pending students
$pending_students_count = $conn->query("SELECT COUNT(*) as total FROM pending_students")->fetch_assoc()['total'];

// Count students with unassigned RFID
$unassigned_rfid_count = $conn->query("SELECT COUNT(*) as total FROM students WHERE rfid IS NULL OR rfid = ''")->fetch_assoc()['total'];

// Count teachers
$teacher_count = $conn->query("SELECT COUNT(*) as total FROM faculty")->fetch_assoc()['total'];

// Count students
$student_count = $conn->query("SELECT COUNT(*) as total FROM students")->fetch_assoc()['total'];

// Fetch ALL recent activity
$recent_query = $conn->query("
    SELECT f.name as teacher, s.subject_name
    FROM teacher_subjects ts 
    JOIN faculty f ON ts.teacher_id = f.id 
    JOIN subjects s ON ts.subject_id = s.id 
    ORDER BY ts.id DESC
");

// Fetch principal's name
$principal_query = $conn->query("SELECT principal_name FROM principal LIMIT 1");
$principal_name = $principal_query && $principal_query->num_rows > 0 
    ? $principal_query->fetch_assoc()['principal_name'] 
    : 'Not Set';

// ------------------ Attendance Week Selector ------------------

// Determine selected week (default: this week)
if (isset($_GET['week_start'])) {
    $current_week_start = $_GET['week_start'];
    $current_week_end   = date('Y-m-d', strtotime($current_week_start . ' +4 days'));
} else {
    $current_week_start = date('Y-m-d', strtotime('monday this week'));
    $current_week_end   = date('Y-m-d', strtotime('friday this week'));
}

// Generate last 5 weeks for dropdown
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

// Calculate attendance rate for the selected week
$attendance_data = [];
$week_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

foreach ($week_days as $index => $day) {
    $current_date = date('Y-m-d', strtotime($current_week_start . " +$index days"));
    
    // Get total possible attendance for the day (all students)
    $total_query = $conn->query("SELECT COUNT(*) as total_students FROM students");
    $total_students = $total_query->fetch_assoc()['total_students'];
    
    if ($total_students > 0) {
        // Get present count for the day from attendance table
        $present_query = $conn->query("
            SELECT COUNT(DISTINCT student_id) as present_count 
            FROM attendance 
            WHERE attendance_date = '$current_date' 
            AND status = 'Present'
        ");
        $present_count = $present_query->fetch_assoc()['present_count'];
        
        // Calculate attendance rate
        $attendance_rate = round(($present_count / $total_students) * 100, 2);
    } else {
        $attendance_rate = 0;
    }
    
    $attendance_data[] = $attendance_rate;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Isiera Dashboard</title>
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
        .attendance-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .attendance-date-range {
            font-size: 14px;
            color: #666;
            background: #f5f5f5;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
            transition: 0.3s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .card ion-icon {
            font-size: 32px;
        }
        .card h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .card p {
            margin: 2px 0 0;
            font-size: 22px;
            font-weight: bold;
            color: #2a2a2a;
        }
        .recent-activity {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
            max-height: 400px;
            overflow-y: auto;
        }
        .recent-activity h2 {
            margin-bottom: 15px;
            font-size: 20px;
            color: #222;
        }
        .recent-activity ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .recent-activity li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 16px;
        }
        .recent-activity li:last-child {
            border-bottom: none;
        }
        /* Modal Background */
.modal {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0; top: 0;
  width: 100%; height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.4);
  justify-content: center;
  align-items: center;
}

/* Modal Box */
.modal-content {
  background-color: #fff;
  margin: auto;
  padding: 30px;
  border-radius: 12px;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

/* Close Button */
.close {
  color: #aaa;
  float: right;
  font-size: 24px;
  font-weight: bold;
  cursor: pointer;
}

.close:hover {
    color: red;
}

/* Form Styling - Consistent for both inputs */
.modal-content form input[type="text"],
.modal-content form input[type="file"] {
  width: 100%;
  padding: 8px;
  margin: 12px 0;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 16px;
  background: white;
}

/* File input specific styling to match text input */
.modal-content form input[type="file"] {
  box-sizing: border-box;
  cursor: pointer;
}

/* Submit Button */
.modal-content form button {
  background-color: #28a745;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.2s ease-in-out;
  width: 100%;
  margin-top: 8px;
}

/* Label styling to match the principal name label */
.modal-content form label {
  display: block;
  margin-top: 15px;
  font-weight: bold;
  color: #555;
  text-align: left;
}
    </style>
</head>
<body>
<?php include('sidebar.php'); ?>
<div class="dashboard-container">
    <div style="text-align: right; margin-bottom: 20px;">
        <div onclick="openPrincipalModal()" style="display: inline-flex; align-items: center; gap: 3px; font-size: 17px; font-weight: bold; color: #28a745; cursor: pointer;">
            <ion-icon name="person-circle-outline" style="font-size: 20px;"></ion-icon>
            School Principal: <?= htmlspecialchars($principal_name) ?>
        </div>
    </div>

    <div class="dashboard-cards">
        <a href="student_verification.php" style="text-decoration: none;">
            <div class="card" style="background-color: #fff3cd; color: #856404; cursor: pointer;">
                <ion-icon name="person-add-outline"></ion-icon>
                <div>
                    <h3>Pending Students</h3>
                    <p><?= htmlspecialchars($pending_students_count) ?></p>
                </div>
            </div>
        </a>
        <a href="id_generation.php" style="text-decoration: none;">
            <div class="card" style="background-color: #f8d7da; color: #721c24; cursor: pointer;">
                <ion-icon name="card-outline"></ion-icon>
                <div>
                    <h3>Unassigned RFID</h3>
                    <p><?= htmlspecialchars($unassigned_rfid_count) ?></p>
                </div>
            </div>
        </a>

        <a href="student_details.php" style="text-decoration: none;">
            <div class="card" style="background-color: #e2f0cb; color: #2e7d32; cursor: pointer;">
                <ion-icon name="school-outline"></ion-icon>
                <div>
                    <h3>Total Students</h3>
                    <p><?= htmlspecialchars($student_count) ?></p>
                </div>
            </div>
        </a>

                <a href="faculty_registration.php" style="text-decoration: none;">
            <div class="card" style="background-color: #d8e2dc; color: #1b3a4b; cursor: pointer;">
                <ion-icon name="people-outline"></ion-icon>
                <div>
                    <h3>Total Teachers</h3>
                    <p><?= htmlspecialchars($teacher_count) ?></p>
                </div>
            </div>
        </a>
    </div>

    <div style="background: #fff; padding: 20px; margin-bottom: 30px; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.06);">
        <div class="attendance-info">
            <h2 style="margin: 0; color: #444;">📊 Attendance Rate</h2>
            <form method="get" style="display:inline;">
                <select name="week_start" onchange="this.form.submit()">
                    <?php foreach ($weeks as $w): ?>
                        <option value="<?= $w['start'] ?>" 
                            <?= ($w['start'] == $current_week_start) ? 'selected' : '' ?>>
                            <?= $w['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <canvas id="attendanceChart" height="100"></canvas>
    </div>

    <div class="recent-activity">
        <h2>📌 Recent Activity</h2>
        <ul>
            <?php if ($recent_query->num_rows > 0): ?>
                <?php while ($activity = $recent_query->fetch_assoc()): ?>
<li>👨‍🏫 <?= htmlspecialchars($activity['teacher']) ?> assigned to <strong><?= htmlspecialchars($activity['subject_name']) ?></strong></li>
                <?php endwhile; ?>
            <?php else: ?>
                <li>No recent activity available.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div id="principalModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closePrincipalModal()">&times;</span>
    <h2>School Principal</h2>
    <form method="POST" action="update_principal.php" enctype="multipart/form-data">
      <label for="principalNameInput">Principal Name:</label>
      <input type="text" name="principal_name" id="principalNameInput" placeholder="Enter principal name" required>
      
      <label for="principalSignature">Signature Upload:</label>
      <input type="file" name="principal_signature" id="principalSignature" accept="image/png, image/jpeg" required>
      
      <button type="submit">Update Principal</button>
    </form>
  </div>
</div>

<script>
function openPrincipalModal() {
  document.getElementById('principalModal').style.display = 'flex';
  document.getElementById('principalNameInput').focus();
}

function closePrincipalModal() {
  document.getElementById('principalModal').style.display = 'none';
}

// Optional: Close modal if user clicks outside
window.onclick = function(event) {
  const modal = document.getElementById('principalModal');
  if (event.target === modal) {
    closePrincipalModal();
  }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('attendanceChart').getContext('2d');
const attendanceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        datasets: [{
            label: 'Attendance Rate (%)',
            data: <?= json_encode($attendance_data) ?>,
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
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    stepSize: 10,
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + '% attendance';
                    }
                }
            }
        }
    }
});
</script>
</body>
</html>