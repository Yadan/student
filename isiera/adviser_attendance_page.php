<?php
include('db_connection.php');

// Check if this is an AJAX request for attendance data
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_attendance') {
    header('Content-Type: application/json');
    
    $subject = isset($_GET['subject']) ? $_GET['subject'] : '';
    $lrn = isset($_GET['lrn']) ? $_GET['lrn'] : '';
    $monthYear = isset($_GET['monthYear']) ? $_GET['monthYear'] : '';
    
    if (empty($subject) || empty($lrn)) {
        echo json_encode([]);
        exit;
    }
    
    $data = [];
    
    try {
        // Show daily attendance records with optional month filter
        if (!empty($monthYear)) {
            // Convert YYYY-MM format to year and month
            $date = DateTime::createFromFormat('Y-m', $monthYear);
            $year = $date->format('Y');
            $monthNum = $date->format('m');
            
            // Get the first and last day of the month
            $firstDayOfMonth = date('Y-m-01', strtotime($monthYear . '-01'));
            $lastDayOfMonth = date('Y-m-t', strtotime($monthYear . '-01'));
            
            $query = "SELECT 
                        DATE_FORMAT(a.attendance_date, '%M %d, %Y') as date,
                        DATE_FORMAT(a.time_in, '%h:%i %p') as time_in,
                        a.status as remarks,
                        f.name as teacher_name
                      FROM attendance a
                      JOIN students s ON a.student_id = s.id
                      JOIN subjects sub ON a.subject_id = sub.id
                      JOIN faculty f ON a.teacher_id = f.id
                      WHERE s.lrn = ? 
                      AND sub.subject_name = ?
                      AND a.attendance_date BETWEEN ? AND ?
                      ORDER BY a.attendance_date DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssss', $lrn, $subject, $firstDayOfMonth, $lastDayOfMonth);
        } else {
            // Show all attendance records
            $query = "SELECT 
                        DATE_FORMAT(a.attendance_date, '%M %d, %Y') as date,
                        DATE_FORMAT(a.time_in, '%h:%i %p') as time_in,
                        a.status as remarks,
                        f.name as teacher_name
                      FROM attendance a
                      JOIN students s ON a.student_id = s.id
                      JOIN subjects sub ON a.subject_id = sub.id
                      JOIN faculty f ON a.teacher_id = f.id
                      WHERE s.lrn = ? 
                      AND sub.subject_name = ?
                      ORDER BY a.attendance_date DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $lrn, $subject);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        } else {
            $data[] = [
                'date' => 'No attendance records found',
                'time_in' => '',
                'remarks' => '',
                'teacher_name' => 'No records'
            ];
        }

        echo json_encode($data);
    } catch (Exception $e) {
        error_log("Error fetching attendance data: " . $e->getMessage());
        echo json_encode([]);
    }
    exit;
}

// Regular page rendering
$lrn = isset($_GET['lrn']) ? $_GET['lrn'] : '';
$section = isset($_GET['section']) ? $_GET['section'] : '';

if (empty($lrn)) {
    die("LRN parameter is required");
}

// Get student info including section_id
$studentInfo = [];
$query = "SELECT s.lrn, CONCAT(s.first_name, ' ', s.last_name) as name, s.section
          FROM students s
          WHERE s.lrn = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $lrn);
$stmt->execute();
$result = $stmt->get_result();
$studentInfo = $result->fetch_assoc();

if (!$studentInfo) {
    die("Student not found");
}

// Fetch subjects with current teacher assignment from teacher_subjects
$subjectsWithTeachers = [];
$query = "SELECT 
            s.id as subject_id,
            s.subject_name as subject,
            f.id as teacher_id,
            f.name as teacher_name
          FROM student_enrollments se
          JOIN subjects s ON se.subject_id = s.id
          JOIN teacher_subjects ts ON ts.subject_id = s.id
          JOIN faculty f ON ts.teacher_id = f.id
          WHERE se.student_lrn = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $lrn);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $subjectsWithTeachers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Attendance - <?php echo htmlspecialchars($studentInfo['name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/attendance.css">
</head>
<body>
<?php include('adviser_sidebar.php'); ?>

<div class="main-content">
    <div class="header-row">
        <h2>Attendance Records</h2>
        <div class="spacer"></div>
        <div class="student-info-row">
            <div class="student-info-item">
                <span class="info-label">LRN:</span>
                <span class="info-value"><?php echo htmlspecialchars($studentInfo['lrn'] ?? ''); ?></span>
            </div>
            <div class="student-info-item">
                <span class="info-label">Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($studentInfo['name'] ?? ''); ?></span>
            </div>
        </div>
    </div>
        
    <div class="filter-single-row">
        <!-- Subject -->
        <div class="filter-group">
            <label for="subjectSelect">Subject:</label>
            <select id="subjectSelect" class="form-input" onchange="updateTeacherField()">
                <option value="select">Select Subject</option>
<?php foreach ($subjectsWithTeachers as $subject): ?>
    <option 
        value="<?php echo htmlspecialchars($subject['subject']); ?>" 
        data-teacher-name="<?php echo htmlspecialchars($subject['teacher_name']); ?>"
    >
        <?php echo htmlspecialchars($subject['subject']); ?>
    </option>
<?php endforeach; ?>
            </select>
        </div>
        
        <!-- Teacher Display -->
        <div class="filter-group">
            <label for="teacherInput">Teacher:</label>
            <input type="text" id="teacherInput" class="form-input" readonly>
        </div>

        <!-- Month & Year Selector -->
        <div class="filter-group">
            <label for="monthYearInput">Month & Year:</label>
            <input type="month" id="monthYearInput" class="form-input" onchange="loadAttendance()">                  
        </div>
    </div>

    <!-- Attendance Summary Table -->
    <div id="attendanceList" class="attendance-list">
        <table id="attendanceTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <tr class="no-data">
                    <td colspan="3">Please select a subject and month to view attendance.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const lrn = "<?php echo htmlspecialchars($lrn); ?>";
    const section = "<?php echo htmlspecialchars($section); ?>";
    let attendanceData = []; // Store attendance data for export
    
    function updateTeacherField() {
        const subjectSelect = document.getElementById("subjectSelect");
        const teacherInput = document.getElementById("teacherInput");
        const selectedOption = subjectSelect.options[subjectSelect.selectedIndex];
        
        if (selectedOption.value !== "select") {
            teacherInput.value = selectedOption.getAttribute('data-teacher-name');
            loadAttendance();
        } else {
            teacherInput.value = "";
        }
    }

    async function loadAttendance() {
        const subject = document.getElementById("subjectSelect").value;
        const monthYearInput = document.getElementById("monthYearInput").value;
        const tableBody = document.querySelector("#attendanceTable tbody");

        if (subject === 'select') {
            tableBody.innerHTML = `
                <tr class="no-data">
                    <td colspan="3">Please select a subject and month to view attendance.</td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = `
            <tr>
                <td colspan="3">Loading attendance data...</td>
            </tr>
        `;
        
        try {
            const params = new URLSearchParams();
            params.append('ajax', 'get_attendance');
            params.append('subject', subject);
            params.append('lrn', lrn);
            
            // Add monthYear parameter if it's set
            if (monthYearInput) {
                params.append('monthYear', monthYearInput);
            }
            
            const response = await fetch(`?${params.toString()}`);
            if (!response.ok) throw new Error('Network error');
            const data = await response.json();
            
            // Store data for export
            attendanceData = data;
            
            if (!data || data.length === 0) {
                tableBody.innerHTML = `
                    <tr class="no-data">
                    <td colspan="3">No attendance records found.</td>
                    </tr>
                `;
                return;
            }

            // Render the table
            let html = '';
            
            for (const record of data) {
                if (record.date === 'No attendance records found') {
                    html += `
                        <tr class="data-row">
                            <td colspan="3">${record.date}</td>
                        </tr>
                    `;
                    continue;
                }
                
                // Determine CSS class based on remarks
                let remarkClass = '';
                if (record.remarks === 'Present') {
                    remarkClass = 'present';
                } else if (record.remarks === 'Absent') {
                    remarkClass = 'absent';
                } else if (record.remarks === 'Late') {
                    remarkClass = 'late';
                }
                
                html += `
                    <tr class="data-row">
                        <td>${record.date}</td>
                        <td>${record.time_in || 'N/A'}</td>
                        <td class="${remarkClass}">${record.remarks}</td>
                    </tr>
                `;
            }

          // Add export button row - positioned to the right
            html += `
                <tr>
                    <td colspan="2"></td>
                    <td style="text-align: right; padding: 10px;">
                        <button class="export-btn" onclick="exportToCSV()">
                            <ion-icon name="download-outline"></ion-icon>
                        </button>
                    </td>
                </tr>
            `;

            tableBody.innerHTML = html;
            
        } catch (error) {
            tableBody.innerHTML = `
                <tr class="no-data">
                    <td colspan="3">Error loading data. Please try again.</td>
                </tr>
            `;
            console.error(error);
        }
    }
    
function exportToCSV() {
    if (attendanceData.length === 0) {
        alert('No data to export');
        return;
    }
    
    const subject = document.getElementById("subjectSelect").value;
    const monthYearInput = document.getElementById("monthYearInput").value;
    const teacher = document.getElementById("teacherInput").value;
    const studentName = "<?php echo htmlspecialchars($studentInfo['name'] ?? ''); ?>";
    const studentLRN = "<?php echo htmlspecialchars($studentInfo['lrn'] ?? ''); ?>";
    
    // Create CSV content
    let csvContent = "Attendance Report\r\n";
    csvContent += `Student: ${studentName}\r\n`;
    csvContent += `LRN: ${studentLRN}\r\n`;
    csvContent += `Subject: ${subject}\r\n`;
    csvContent += `Teacher: ${teacher}\r\n`;
    
    if (monthYearInput) {
        // Format the month for display (e.g., "2023-07" becomes "July 2023")
        const date = new Date(monthYearInput + '-01');
        const monthName = date.toLocaleString('default', { month: 'long' });
        const year = date.getFullYear();
        csvContent += `Month: ${monthName} ${year}\r\n`;
    }
    
    csvContent += "\r\nDate,Time In,Remarks\r\n";
    
    // Add data rows
    attendanceData.forEach(record => {
        if (record.date !== 'No attendance records found') {
            csvContent += `"${record.date}",${record.time_in || 'N/A'},"${record.remarks}"\r\n`;
        }
    });
    
    // Create download link
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    
    let fileName = `attendance_${subject.replace(/\s+/g, '_')}_${studentLRN}`;
    if (monthYearInput) {
        fileName += `_${monthYearInput.replace('-', '_')}`;
    }
    fileName += '.csv';
    
    link.setAttribute("download", fileName);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    
    // Trigger download
    link.click();
    document.body.removeChild(link);
}

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        // Set up event listeners
        document.getElementById('subjectSelect').addEventListener('change', updateTeacherField);
        
        // Set current month as default for the month picker
        const today = new Date();
        const currentMonth = today.toISOString().slice(0, 7); // YYYY-MM format
        document.getElementById("monthYearInput").value = currentMonth;
        
        // Load attendance data initially
        loadAttendance();
    });
</script>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>