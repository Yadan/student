<?php
include('db_connection.php');

// Check if this is an AJAX request for attendance data
if (isset($_GET['ajax'])) {
    if ($_GET['ajax'] == 'get_attendance') {
        header('Content-Type: application/json');
        
        $subject = isset($_GET['subject']) ? $_GET['subject'] : '';
        $period = isset($_GET['period']) ? $_GET['period'] : '';
        $section = isset($_GET['section']) ? $_GET['section'] : '';
        $monthYear = isset($_GET['monthYear']) ? $_GET['monthYear'] : '';
        
        if (empty($subject) || empty($period) || empty($section)) {
            echo json_encode([]);
            exit;
        }
        
        $data = [];
        
        try {
            if ($period === 'monthly') {
                // Monthly view - count by month for the entire section
                $query = "SELECT 
                            DATE_FORMAT(a.attendance_date, '%M %Y') as label,
                            YEAR(a.attendance_date) as year,
                            MONTH(a.attendance_date) as month,
                            COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present,
                            COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent,
                            f.name as teacher_name
                          FROM attendance a
                          JOIN students s ON a.student_id = s.id
                          JOIN subjects sub ON a.subject_id = sub.id
                          JOIN faculty f ON a.teacher_id = f.id
                          WHERE s.section = ? 
                          AND sub.subject_name = ?
                          GROUP BY YEAR(a.attendance_date), MONTH(a.attendance_date), f.name
                          ORDER BY YEAR(a.attendance_date), MONTH(a.attendance_date)";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ss', $section, $subject);
                
            } elseif ($period === 'weekly') {
                // Weekly view - count by week according to division calendar (Sunday to Saturday)
                if (!empty($monthYear)) {
                    // Convert YYYY-MM format to year and month
                    $date = DateTime::createFromFormat('Y-m', $monthYear);
                    $year = $date->format('Y');
                    $monthNum = $date->format('m');
                    
                    // Get the first and last day of the month
                    $firstDayOfMonth = date('Y-m-01', strtotime($monthYear . '-01'));
                    $lastDayOfMonth = date('Y-m-t', strtotime($monthYear . '-01'));
                    
                    // Create weeks according to division calendar (Sunday to Saturday)
                    $weeks = [];
                    $currentDate = $firstDayOfMonth;
                    
                    // Find the Sunday of the week that contains the first day of the month
                    $weekStart = date('Y-m-d', strtotime('last sunday', strtotime($currentDate)));
                    if (strtotime($weekStart) < strtotime($firstDayOfMonth)) {
                        $weekStart = $firstDayOfMonth;
                    }
                    
                    while ($weekStart <= $lastDayOfMonth) {
                        $weekEnd = date('Y-m-d', strtotime('saturday', strtotime($weekStart)));
                        
                        // Ensure week doesn't extend beyond the month
                        if ($weekEnd > $lastDayOfMonth) {
                            $weekEnd = $lastDayOfMonth;
                        }
                        
                        $weekNumber = count($weeks) + 1;
                        $weekLabel = "Week $weekNumber (" . date('M j', strtotime($weekStart)) . " - " . 
                                    date('M j', strtotime($weekEnd)) . ")";
                        
                        $weeks[] = [
                            'number' => $weekNumber,
                            'start' => $weekStart,
                            'end' => $weekEnd,
                            'label' => $weekLabel
                        ];
                        
                        // Move to next week
                        $weekStart = date('Y-m-d', strtotime($weekEnd . ' +1 day'));
                    }
                    
                    // Query for each week
                    foreach ($weeks as $week) {
                        $query = "SELECT 
                                    COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present,
                                    COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent,
                                    f.name as teacher_name
                                  FROM attendance a
                                  JOIN students s ON a.student_id = s.id
                                  JOIN subjects sub ON a.subject_id = sub.id
                                  JOIN faculty f ON a.teacher_id = f.id
                                  WHERE s.section = ? 
                                  AND sub.subject_name = ?
                                  AND a.attendance_date BETWEEN ? AND ?";
                        
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param('ssss', $section, $subject, $week['start'], $week['end']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $data[] = [
                                'label' => $week['label'],
                                'present' => $row['present'],
                                'absent' => $row['absent'],
                                'teacher_name' => $row['teacher_name']
                            ];
                        } else {
                            $data[] = [
                                'label' => $week['label'],
                                'present' => 0,
                                'absent' => 0,
                                'teacher_name' => 'No records'
                            ];
                        }
                    }
                    
                    // We've manually built the data, so we can skip the rest
                    echo json_encode($data);
                    exit;
                } else {
                    // All weekly data (not filtered by month)
                    $query = "SELECT 
                                CONCAT('Week ', WEEK(a.attendance_date, 1), ' (', 
                                       DATE_FORMAT(DATE_SUB(a.attendance_date, INTERVAL WEEKDAY(a.attendance_date) DAY), '%M %d'), ' - ',
                                       DATE_FORMAT(DATE_ADD(a.attendance_date, INTERVAL (6 - WEEKDAY(a.attendance_date)) DAY), '%M %d'), ')') as label,
                                YEAR(a.attendance_date) as year,
                                MONTH(a.attendance_date) as month,
                                WEEK(a.attendance_date, 1) as week_number,
                                COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present,
                                COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent,
                                f.name as teacher_name
                              FROM attendance a
                              JOIN students s ON a.student_id = s.id
                              JOIN subjects sub ON a.subject_id = sub.id
                              JOIN faculty f ON a.teacher_id = f.id
                              WHERE s.section = ? 
                              AND sub.subject_name = ?
                              GROUP BY YEAR(a.attendance_date), WEEK(a.attendance_date, 1), f.name
                              ORDER BY YEAR(a.attendance_date), WEEK(a.attendance_date, 1)";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('ss', $section, $subject);
                }
            }
            
            // Execute the query for non-weekly-monthly cases
            if ($period !== 'weekly' || empty($monthYear)) {
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $data[] = $row;
                    }
                } else {
                    $data[] = [
                        'label' => 'No attendance records found',
                        'present' => 0,
                        'absent' => 0,
                        'teacher_name' => 'No records'
                    ];
                }
            }

            echo json_encode($data);
        } catch (Exception $e) {
            error_log("Error fetching attendance data: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }
    elseif ($_GET['ajax'] == 'get_detailed_attendance') {
        header('Content-Type: application/json');
        
        $subject = isset($_GET['subject']) ? $_GET['subject'] : '';
        $section = isset($_GET['section']) ? $_GET['section'] : '';
        $monthYear = isset($_GET['monthYear']) ? $_GET['monthYear'] : '';
        
        if (empty($subject) || empty($section)) {
            echo json_encode([]);
            exit;
        }
        
        $data = [];
        
        try {
            if (!empty($monthYear)) {
                // Convert YYYY-MM format to year and month
                $date = DateTime::createFromFormat('Y-m', $monthYear);
                $year = $date->format('Y');
                $monthNum = $date->format('m');
                
                // Get the first and last day of the month
                $firstDayOfMonth = date('Y-m-01', strtotime($monthYear . '-01'));
                $lastDayOfMonth = date('Y-m-t', strtotime($monthYear . '-01'));
                
                $query = "SELECT 
                            s.lrn,
                            CONCAT(s.first_name, ' ', s.last_name) as student_name,
                            DATE_FORMAT(a.attendance_date, '%M %d, %Y') as date,
                            DATE_FORMAT(a.time_in, '%h:%i %p') as time_in,
                            a.status as remarks,
                            f.name as teacher_name
                          FROM attendance a
                          JOIN students s ON a.student_id = s.id
                          JOIN subjects sub ON a.subject_id = sub.id
                          JOIN faculty f ON a.teacher_id = f.id
                          WHERE s.section = ? 
                          AND sub.subject_name = ?
                          AND a.attendance_date BETWEEN ? AND ?
                          ORDER BY s.lrn, a.attendance_date ASC";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssss', $section, $subject, $firstDayOfMonth, $lastDayOfMonth);
            } else {
                // Show all attendance records
                $query = "SELECT 
                            s.lrn,
                            CONCAT(s.first_name, ' ', s.last_name) as student_name,
                            DATE_FORMAT(a.attendance_date, '%M %d, %Y') as date,
                            DATE_FORMAT(a.time_in, '%h:%i %p') as time_in,
                            a.status as remarks,
                            f.name as teacher_name
                          FROM attendance a
                          JOIN students s ON a.student_id = s.id
                          JOIN subjects sub ON a.subject_id = sub.id
                          JOIN faculty f ON a.teacher_id = f.id
                          WHERE s.section = ? 
                          AND sub.subject_name = ?
                          ORDER BY s.lrn, a.attendance_date ASC";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ss', $section, $subject);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            } else {
                $data[] = [
                    'lrn' => '',
                    'student_name' => 'No attendance records found',
                    'date' => '',
                    'time_in' => '',
                    'remarks' => '',
                    'teacher_name' => 'No records'
                ];
            }

            echo json_encode($data);
        } catch (Exception $e) {
            error_log("Error fetching detailed attendance data: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }
}

// Regular page rendering
$section = isset($_GET['section']) ? $_GET['section'] : '';

if (empty($section)) {
    die("Section parameter is required");
}

// Get section info
$sectionInfo = [];
$query = "SELECT DISTINCT section FROM students WHERE section = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $section);
$stmt->execute();
$result = $stmt->get_result();
$sectionInfo = $result->fetch_assoc();

if (!$sectionInfo) {
    die("Section not found");
}

// Fetch subjects with the currently assigned teachers from teacher_subjects
$subjectsWithTeachers = [];
$query = "SELECT DISTINCT
            sub.id AS subject_id,
            sub.subject_name AS subject,
            f.id AS teacher_id,
            f.name AS teacher_name
          FROM subjects sub
          JOIN teacher_subjects ts ON ts.subject_id = sub.id
          JOIN faculty f ON ts.teacher_id = f.id
          JOIN student_enrollments se ON se.subject_id = sub.id
          JOIN students st ON se.student_lrn = st.lrn
          WHERE st.section = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $section);
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
    <title>Section Attendance - <?php echo htmlspecialchars($section); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/attendance.css">
</head>
<body>
<?php include('adviser_sidebar.php'); ?>

<div class="main-content">
    <div class="header-row">
      <h2>Section Attendance: <?php echo htmlspecialchars($section); ?></h2>
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

        <div class="filter-group">
            <label for="periodSelect">Period:</label>
            <select id="periodSelect" class="form-input" onchange="toggleMonthYearSelector()">
                <option value="monthly">Monthly</option>
                <option value="weekly">Weekly</option>
                <option value="daily">Daily</option>
            </select>
        </div>
        
        <!-- Month & Year Selector - Only shows when Weekly is selected -->
        <div class="filter-group month-year-selector" id="monthYearSelector">
            <label for="monthYearInput">Month & Year:</label>
            <input type="month" id="monthYearInput" class="form-input" onchange="loadAttendance()">                  
        </div>
    </div>

    <!-- Attendance Summary Table -->
    <div id="attendanceList" class="attendance-list">
        <table id="attendanceTable">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Present</th>
                    <th>Absent</th>
                </tr>
            </thead>
            <tbody>
                <tr class="no-data">
                    <td colspan="3">Please select a subject and period to view attendance.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const section = "<?php echo htmlspecialchars($section); ?>";
    let attendanceData = []; // Store attendance data for export
    
    function toggleMonthYearSelector() {
        const period = document.getElementById("periodSelect").value;
        const monthYearSelector = document.getElementById("monthYearSelector");
        
        // Only show month picker for weekly view
        if (period === 'weekly') {
            monthYearSelector.style.display = 'flex';
        } else {
            monthYearSelector.style.display = 'none';
            // Clear the month input when switching to other views
            document.getElementById("monthYearInput").value = "";
        }
        
        // If daily is selected, redirect to adviser_section_attendance.php
        if (period === 'daily') {
            window.location.href = `adviser_section_attendance.php?section=${section}`;
            return;
        }
        
        loadAttendance();
    }
    
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
        const period = document.getElementById("periodSelect").value;
        const monthYearInput = document.getElementById("monthYearInput").value;
        const tableBody = document.querySelector("#attendanceTable tbody");

        if (subject === 'select') {
            tableBody.innerHTML = `
                <tr class="no-data">
                    <td colspan="3">Please select a subject and period to view attendance.</td>
                </tr>
            `;
            return;
        }

        // Only require month selection for weekly view
        if (period === 'weekly' && !monthYearInput) {
            tableBody.innerHTML = `
                <tr class="no-data">
                    <td colspan="3">Please select a month to view weekly attendance.</td>
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
            params.append('period', period);
            params.append('section', section);
            
            // Only add monthYear parameter for weekly view when it's set
            if (period === 'weekly' && monthYearInput) {
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

            // Calculate totals
            let totalPresent = 0;
            let totalAbsent = 0;
            
            // Render the table
            let html = '';
            
            for (const record of data) {
                if (record.label === 'No attendance records found') {
                    html += `
                        <tr class="data-row">
                            <td>${record.label}</td>
                            <td class="present">${record.present}</td>
                            <td class="absent">${record.absent}</td>
                        </tr>
                    `;
                    continue;
                }
                
                totalPresent += parseInt(record.present) || 0;
                totalAbsent += parseInt(record.absent) || 0;
                
                html += `
                    <tr class="data-row">
                        <td>${record.label}</td>
                        <td class="present">${record.present}</td>
                        <td class="absent">${record.absent}</td>
                    </tr>
                `;
            }

            // Add totals row
            html += `
                <tr class="totals-row">
                    <td><strong>Total</strong></td>
                    <td class="present"><strong>${totalPresent}</strong></td>
                    <td class="absent"><strong>${totalAbsent}</strong></td>
                </tr>
            `;
            
            // Add export button row
            html += `
                <tr>
                    <td colspan="3" style="text-align: right; padding: 15px;">
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
        const period = document.getElementById("periodSelect").value;
        const monthYearInput = document.getElementById("monthYearInput").value;
        const teacher = document.getElementById("teacherInput").value;
        
        // First, fetch the detailed attendance records
        fetchDetailedAttendanceRecords(subject, period, monthYearInput, teacher);
    }

    async function fetchDetailedAttendanceRecords(subject, period, monthYearInput, teacher) {
        try {
            const params = new URLSearchParams();
            params.append('ajax', 'get_detailed_attendance');
            params.append('subject', subject);
            params.append('section', section);
            
            // Only add monthYear parameter for weekly view
            if (period === 'weekly' && monthYearInput) {
                params.append('monthYear', monthYearInput);
            }
            
            const response = await fetch(`?${params.toString()}`);
            if (!response.ok) throw new Error('Network error');
            const detailedData = await response.json();
            
            // Now create the CSV with both summary and detailed data
            createCSVWithDetails(subject, period, monthYearInput, teacher, detailedData);
        } catch (error) {
            console.error('Error fetching detailed records:', error);
            alert('Error fetching detailed attendance records');
        }
    }

    function createCSVWithDetails(subject, period, monthYearInput, teacher, detailedData) {
        // Create CSV content
        let csvContent = "Section Attendance Report\r\n";
        csvContent += `Section: ${section}\r\n`;
        csvContent += `Subject: ${subject}\r\n`;
        csvContent += `Teacher: ${teacher}\r\n`;
        csvContent += `Period: ${period.charAt(0).toUpperCase() + period.slice(1)}\r\n`;
        
        // Only add month info for weekly view
        if (period === 'weekly' && monthYearInput) {
            const date = new Date(monthYearInput + '-01');
            const monthName = date.toLocaleString('default', { month: 'long' });
            const year = date.getFullYear();
            csvContent += `Month: ${monthName} ${year}\r\n`;
        }
        
        // Summary section
        csvContent += "\r\n=== SUMMARY ===\r\n";
        csvContent += "Period,Present,Absent\r\n";
        
        // Add summary data rows
        attendanceData.forEach(record => {
            if (record.label !== 'No attendance records found') {
                csvContent += `"${record.label}",${record.present},${record.absent}\r\n`;
            }
        });
        
        // Calculate totals
        const totalPresent = attendanceData.reduce((sum, row) => sum + (parseInt(row.present) || 0), 0);
        const totalAbsent = attendanceData.reduce((sum, row) => sum + (parseInt(row.absent) || 0), 0);
        
        csvContent += `"Total",${totalPresent},${totalAbsent}\r\n`;
        
        // Add space before detailed records
        csvContent += "\r\n\r\n=== DETAILED RECORDS ===\r\n";
        csvContent += "LRN,Student Name,Date,Time In,Remarks\r\n";
        
        // Add detailed records
        if (detailedData && detailedData.length > 0 && detailedData[0].student_name !== 'No attendance records found') {
            detailedData.forEach(record => {
                csvContent += `"${record.lrn}","${record.student_name}","${record.date}",${record.time_in || 'N/A'},"${record.remarks}"\r\n`;
            });
        } else {
            csvContent += "No detailed records available\r\n";
        }
        
        // Create download link
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        
        let fileName = `section_attendance_${period}_${subject.replace(/\s+/g, '_')}_${section.replace(/\s+/g, '_')}`;
        // Only add month to filename for weekly view
        if (period === 'weekly' && monthYearInput) {
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
        document.getElementById('periodSelect').addEventListener('change', toggleMonthYearSelector);
        
        // Initialize the month/year selector visibility
        toggleMonthYearSelector();
        
        // Set current month as default for the month picker
        const today = new Date();
        const currentMonth = today.toISOString().slice(0, 7); // YYYY-MM format
        document.getElementById("monthYearInput").value = currentMonth;
    });
</script>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>