<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$selectedSubject = $_GET['subject'] ?? 'all';
$selectedMonth = $_GET['month'] ?? date('Y-m');

// First, get the student's section from student_enrollments
$sectionQuery = "SELECT section_id FROM student_enrollments WHERE student_lrn = (SELECT lrn FROM students WHERE id = ?) LIMIT 1";
$stmt = $conn->prepare($sectionQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$sectionResult = $stmt->get_result();
$studentSection = $sectionResult->fetch_assoc();
$section_id = $studentSection ? $studentSection['section_id'] : null;

// Fetch enrolled subjects for this student with teacher information
$subjects = [];
if ($section_id) {
    $subject_query = "SELECT DISTINCT
                        s.id as subject_id,
                        s.subject_name,
                        f.id as faculty_id,
                        f.name as faculty_name
                      FROM student_enrollments se
                      JOIN subjects s ON se.subject_id = s.id
                      JOIN teacher_subjects ts ON s.id = ts.subject_id AND ts.section_id = ?
                      JOIN faculty f ON ts.teacher_id = f.id
                      WHERE se.student_lrn = (SELECT lrn FROM students WHERE id = ?)";
    
    $stmt = $conn->prepare($subject_query);
    $stmt->bind_param("ii", $section_id, $student_id);
    $stmt->execute();
    $subject_result = $stmt->get_result();

    while ($row = $subject_result->fetch_assoc()) {
        $subjects[$row['subject_id']] = [
            'subject_name' => $row['subject_name'],
            'faculty_id' => $row['faculty_id'],
            'faculty_name' => $row['faculty_name']
        ];
    }
}

// Set filtering condition
$firstDay = date('Y-m-01', strtotime($selectedMonth));
$lastDay = date('Y-m-t', strtotime($selectedMonth));
$dateCondition = "AND a.attendance_date BETWEEN '$firstDay' AND '$lastDay'";

// Add subject filter condition
$subjectCondition = "";
if ($selectedSubject !== 'all' && is_numeric($selectedSubject)) {
    $subjectCondition = "AND a.subject_id = ?";
}

// Fetch attendance records
$attendance_query = "SELECT a.attendance_date, a.time_in, a.status
                     FROM attendance a 
                     WHERE a.student_id = ? 
                     $dateCondition 
                     $subjectCondition 
                     ORDER BY a.attendance_date DESC, a.time_in DESC";

$stmt = $conn->prepare($attendance_query);
if ($selectedSubject !== 'all' && is_numeric($selectedSubject)) {
    $stmt->bind_param("ii", $student_id, $selectedSubject);
} else {
    $stmt->bind_param("i", $student_id);
}
$stmt->execute();
$attendance_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Record</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .no-data {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #6c757d;
        }
        .table-header-custom {
            background-color: #28a745 !important;
            color: white;
        }
        .table-header-custom th {
            background-color: #28a745 !important;
            color: white;
            border-color: #28a745;
        }
    </style>
</head>
<body>

<?php include('student_portal_navigation.php'); ?>

<div class="container mt-5">
    <h3 style="color: #28a745;">Attendance Record</h3>

    <!-- Filters -->
    <form method="GET" class="mb-4 d-flex gap-4 flex-wrap align-items-end">
        <!-- Subject Dropdown (from enrolled subjects) -->
        <div>
            <label for="subject" class="form-label">Subject:</label>
            <select name="subject" id="subject" class="form-select" onchange="updateFacultyInfo(this.value); this.form.submit()">
                <option value="all" <?= $selectedSubject === 'all' ? 'selected' : '' ?>>Select Subject</option>
                <?php foreach ($subjects as $id => $subject_info): ?>
                    <option value="<?= $id ?>" <?= $selectedSubject == $id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subject_info['subject_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Faculty Information Display -->
        <div class="filter-group">
            <label class="form-label">Teacher:</label>
            <div id="facultyInfo" class="p-2 border rounded bg-light">
                <?php if ($selectedSubject !== 'all' && isset($subjects[$selectedSubject])): ?>
                    <div>
                        <strong><?= htmlspecialchars($subjects[$selectedSubject]['faculty_name']) ?></strong>
                    </div>
                <?php else: ?>
                    <div class="text-muted">Select a subject to view teacher</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Month Selector -->
        <div>
            <label for="month" class="form-label">Month:</label>
            <input type="month" name="month" id="month" class="form-control" 
                   value="<?= htmlspecialchars($selectedMonth) ?>" 
                   onchange="this.form.submit()">
        </div>
    </form>

    <!-- Attendance Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-header-custom">
                <tr>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($attendance_result->num_rows > 0 && $selectedSubject !== 'all'): ?>
                    <?php while ($row = $attendance_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars(date("F j, Y", strtotime($row['attendance_date']))) ?></td>
                            <td><?= htmlspecialchars(date("h:i A", strtotime($row['time_in']))) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="no-data">
                            <?php if ($selectedSubject === 'all'): ?>
                                Please select a subject and month to view attendance data
                            <?php else: ?>
                                No data available.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Function to update faculty information based on selected subject
    function updateFacultyInfo(subjectId) {
        const facultyInfo = document.getElementById('facultyInfo');
        
        if (subjectId === 'all') {
            facultyInfo.innerHTML = '<div class="text-muted">Select a subject to view teacher</div>';
            return;
        }
        
        // Get faculty data from PHP array (converted to JS object)
        const subjects = <?= json_encode($subjects) ?>;
        
        if (subjects[subjectId]) {
            const faculty = subjects[subjectId];
            facultyInfo.innerHTML = `
                <div><strong>${faculty.faculty_name}</strong></div>
            `;
        } else {
            facultyInfo.innerHTML = '<div class="text-muted">Teacher information not available</div>';
        }
    }
    
    // Initialize faculty info on page load
    document.addEventListener('DOMContentLoaded', function() {
        const subjectSelect = document.getElementById('subject');
        updateFacultyInfo(subjectSelect.value);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>