<?php
include 'db_connection.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Strand
    if (isset($_POST['add_strand'])) {
        $name = trim($_POST['strand_name']);
        if ($name !== '') {
            $stmt = $conn->prepare("INSERT INTO strands (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
        }
    }
    // Edit Strand
    elseif (isset($_POST['edit_strand'])) {
        $id = $_POST['strand_id'];
        $newName = trim($_POST['new_name']);
        if ($newName !== '') {
            $stmt = $conn->prepare("UPDATE strands SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $newName, $id);
            $stmt->execute();
        }
    }

// Assign Subjects to Grade Levels with optional Strand
elseif (isset($_POST['assign_subjects'])) {
    $gradeLevels = $_POST['grade_levels'] ?? [];
    $subjectIds = $_POST['subject_ids'] ?? [];
    $selectedStrand = $_POST['strand'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $strandId = null;

    if ($selectedStrand !== '') {
        $stmt = $conn->prepare("SELECT id FROM strands WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $selectedStrand);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $strandId = $row['id'] ?? null;
    }

    // ✅ Require semester if SHS is selected
    if ($_POST['student_type'] === 'SHS' && $semester === '') {
        $error = "Please select a semester.";
    }
    elseif (!empty($gradeLevels) && !empty($subjectIds)) {
        foreach ($gradeLevels as $gradeLevel) {
            foreach ($subjectIds as $subjectId) {
                $stmt = $conn->prepare("INSERT IGNORE INTO subject_grade_strand_assignments 
                    (subject_id, grade_level, strand_id, semester) 
                    VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isis", $subjectId, $gradeLevel, $strandId, $semester);
                $stmt->execute();
            }
        }
        $success = "Subjects successfully assigned to selected Grade Levels.";
    } else {
        $error = "Please select at least one grade level and subject.";
    }
}
}

$result = $conn->query("SELECT subjects.*, strands.name as strand_name FROM subjects LEFT JOIN strands ON subjects.strand_id = strands.id ORDER BY subject_name ASC");
$subjects = $result->fetch_all(MYSQLI_ASSOC);
$strandsResult = $conn->query("SELECT * FROM strands ORDER BY name ASC");
$strands = $strandsResult->fetch_all(MYSQLI_ASSOC);

$studentType = $_GET['type'] ?? 'JHS';
$strand = $_GET['strand'] ?? '';

$query = "
  SELECT s.subject_name, s.student_type, g.grade_level, str.name AS strand, g.semester
  FROM subject_grade_strand_assignments g
  JOIN subjects s ON s.id = g.subject_id
  LEFT JOIN strands str ON g.strand_id = str.id
  WHERE s.student_type = ?
";

if ($studentType === 'SHS' && $strand !== '' && $strand !== 'All') {
    $query .= " AND str.name = ?";
    $assigned = $conn->prepare($query);
    $assigned->bind_param("ss", $studentType, $strand);
} else {
    $assigned = $conn->prepare($query);
    $assigned->bind_param("s", $studentType);
}

$assigned->execute();
$result = $assigned->get_result();
$assignedSubjects = $result->fetch_all(MYSQLI_ASSOC);

// Group subjects
$shsGroups = []; // Key: Strand, Value: ['11' => [...], '12' => [...]]

foreach ($assignedSubjects as $row) {
    $grade = $row['grade_level'];
    $strandName = $row['strand'] ?? 'All';
    $semester = $row['semester'] ?? 'Unassigned';

    if (!isset($shsGroups[$strandName])) {
        $shsGroups[$strandName] = ['11' => [], '12' => []];
    }

    $shsGroups[$strandName][$grade][$semester][] = $row['subject_name'];
}

$jhsGroups = []; // Key: Grade, Value: Subjects
foreach ($assignedSubjects as $row) {
    if ($row['student_type'] === 'JHS') {
        $grade = $row['grade_level'];
        $jhsGroups[$grade][] = $row['subject_name'];
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Assign Grade level to Subjects</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <style> 

  /* Dropdown navigation */
    .dropdown-nav {
      background-color: #fff;
      border-radius: 12px;
      padding: 15px 20px;
      margin-bottom: 20px;
      box-shadow: 0 0 8px rgba(0,0,0,0.05);
    }

    .dropdown-nav select {
  padding: 8px 12px;
  border-radius: 6px;
} /* ← CLOSE the select block properly first */

    .card {
      background: #ffffff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      max-width: 800px;
      margin: auto;
    }

    .card h3 {
      margin-bottom: 20px;
      color: #black;
    }

    label {
      font-weight: 600;
      display: block;
      margin-top: 15px;
    }

    .checkbox-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }

    .checkbox-row label {
      display: flex;
      align-items: center;
      padding: 6px 12px;
      background: #f2f2f2;
      border-radius: 6px;
      cursor: pointer;
    }

    .checkbox-row input {
      margin-right: 6px;
    }

    button[type=submit] {
      margin-top: 20px;
      width: 100%;
      padding: 12px;
      background-color: #28a745;
      color: white;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button[type=submit]:hover {
      background-color: #0b6609;
    }

    .alert {
      padding: 12px;
      margin-bottom: 20px;
      border-radius: 6px;
    }

    .success {
      background: #d4edda;
      color: #155724;
    }

    .error {
      background: #f8d7da;
      color: #721c24;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th, td {
      padding: 10px;
      border: 1px solid #ddd;
      text-align: left;
    }
    

.btn { padding: 8px 16px; background-color: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; transition: background 0.3s ease; }
.btn:hover { background-color: #0b6609; }
.btn-secondary { background-color: #555; }
.btn-secondary:hover { background-color: #333; }
.modal { display: none; position: fixed; z-index: 10; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
.modal-content { background-color: #fff; margin: 10% auto; padding: 20px; border-radius: 12px; width: 400px; max-width: 90%; }
.close { float: right; font-size: 20px; font-weight: bold; cursor: pointer; } .close:hover {
  color: #ff0000;
}
    
 </style>
</head>
<body>
<?php include('sidebar.php'); ?>
<div class="main-content">
  <div class="dropdown-nav">
    <label>Navigate to:</label>
    <select onchange="navigate(this.value)">
      <option value="">-- Select an option --</option>
      <option value="section_management.php">Section Management</option>
      <option value="assign_subjects_grade.php">Curriculum | Subjects - Grade Level</option>
      <option value="teacher_assigning.php">Subject Teacher | Class adviser</option>
    </select>
  </div>

<button class="btn" onclick="openStrandModal()">Manage Strands</button>
<div class="card">
<h3 style="text-align: center;">Assign Subjects to Grade Level</h3>
<?php if (isset($success)) echo "<div class='alert success'>$success</div>"; ?>
<?php if (isset($error)) echo "<div class='alert error'>$error</div>"; ?>

<form method="POST">
<label>Student Type</label>
<select name="student_type" id="student_type" onchange="filterForm()" required>
  <option value="">-- Select Type --</option>
  <option value="JHS">JHS</option>
  <option value="SHS">SHS</option>
</select>

<div id="strand_container" style="display:none;">
  <label>Strand</label>
<select name="strand" onchange="filterForm()">
  <option value="">-- Select Strand --</option>
  <?php foreach ($strands as $str): ?>
    <option value="<?= $str['name'] ?>"><?= $str['name'] ?></option>
  <?php endforeach; ?>
</select>
  
  <label>Semester (SHS only)</label>
<select name="semester">
  <option value="">-- Select Semester --</option>
  <option value="1st Semester">1st Semester</option>
  <option value="2nd Semester">2nd Semester</option>
</select>
</div>



<div id="grade_level_container" style="display:none;">
  <label>Select Grade Level(s)</label>
  <div class="checkbox-row">
    <?php foreach (range(7, 12) as $grade): ?>
      <label id="grade_box_<?= $grade ?>" style="display:none;">
        <input type="checkbox" name="grade_levels[]" value="<?= $grade ?>">
        Grade <?= $grade ?>
      </label>
    <?php endforeach; ?>
  </div>
</div>

<div id="subject_container" style="display:none;">


  <div style="margin-bottom: 10px; flex-end;">
  <input type="text" id="subjectSearch" placeholder="Search by subject or strand" onkeyup="filterSubjectSearch()" style="width: 50%; padding: 5px; margin-bottom: 5px; border-radius: 6px; border: 1px solid #ccc; flex-end; margin-top:10px" />

  <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: normal;">
  <input type="checkbox" id="selectAllSubjects" onclick="toggleSelectAll(this)">
  Select All
</label>

  </div>

  <div class="checkbox-row" id="filteredSubjects">
    <?php foreach ($subjects as $subject): ?>
      <label class="subject-row"
             data-type="<?= $subject['student_type'] ?>"
             data-strand="<?= $subject['strand_id'] ?? '' ?>"
             data-strand-name="<?= $subject['strand_name'] ?? '' ?>"
             style="display:none; font-size: 13px; font-weight: normal;">
        <input type="checkbox" name="subject_ids[]" value="<?= $subject['id'] ?>">
        <?= htmlspecialchars($subject['subject_name']) ?>
      </label>
    <?php endforeach; ?>
  </div>
</div>


<button type="submit" name="assign_subjects" class="btn">Assign Subjects</button>
</form>
</div>

<!-- Flex wrapper to align right -->
<div style="display: flex; justify-content: flex-end; margin-bottom: 20px; margin-top: 20px;">
  <form method="GET" style="display: flex; align-items: center; gap: 15px;">
    <div style="display: flex; flex-direction: column; text-align: center;">
      <label for="type" style="font-size: 12px; margin-bottom: 2px;">Student Type</label>
      <select name="type" id="type" onchange="this.form.submit()" style="padding: 6px 10px;">
        <option value="JHS" <?= $studentType === 'JHS' ? 'selected' : '' ?>>JHS</option>
        <option value="SHS" <?= $studentType === 'SHS' ? 'selected' : '' ?>>SHS</option>
      </select>
    </div>

    <?php if ($studentType === 'SHS'): ?>
    <div style="display: flex; flex-direction: column; text-align: center; ">
      <label for="strand" style="font-size: 12px; margin-bottom: 2px;">Strand</label>
      <select name="strand" id="strand" onchange="this.form.submit()" style="padding: 6px 10px;">
        <option value="All" <?= $strand === 'All' ? 'selected' : '' ?>>All</option>
<?php foreach ($strands as $option): ?>
  <option value="<?= $option['name'] ?>" <?= $strand === $option['name'] ? 'selected' : '' ?>>
    <?= $option['name'] ?>
  </option>
<?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
  </form>
</div>

<?php if ($studentType === 'JHS'): ?>
  <h3 style="text-align: center; margin-top: 20px; color: black;">
    JHS Curriculum
  </h3>
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 800px; margin: 20px auto;">
    <?php foreach (range(7,10) as $grade): ?>
      <?php if (!empty($jhsGroups[$grade])): ?>
        <div style="background: #f9f9f9; border-radius: 8px; padding: 15px;">
          <h4 style="color: black; text-align: center;">Grade <?= $grade ?></h4>
          <ul style="padding-left: 20px; margin-top: 5px; margin-bottom: 10px;">
            <?php foreach ($jhsGroups[$grade] as $subj): ?>
              <li><?= htmlspecialchars($subj) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($studentType === 'SHS'): ?>
  <h3 style="text-align: center; margin-top: 20px; color: black;">
    SHS Curriculum
  </h3>
  <div style="display: flex; flex-direction: column; gap: 20px; align-items: center; margin-top: 20px;">
    <?php foreach ($shsGroups as $strand => $grades): ?>
    <div style="display: flex; gap: 50px; justify-content: center; width: 100%; max-width: 800px;">
      <?php $semesters = ['1st Semester', '2nd Semester']; ?>

      <!-- Grade 11 -->
      <div style="flex: 1; background: #f9f9f9; border-radius: 8px; padding: 10px;">
        <h4 style="color: black; text-align: center;">Grade 11 <?= $strand ?></h4>
        <?php foreach ($semesters as $sem): ?>
          <?php if (!empty($grades['11'][$sem])): ?>
            <h5><?= $sem ?></h5>
            <ul style="padding-left: 20px;">
              <?php foreach ($grades['11'][$sem] as $subj): ?>
                <li><?= htmlspecialchars($subj) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <!-- Grade 12 -->
      <div style="flex: 1; background: #f9f9f9; border-radius: 8px; padding: 10px;">
        <h4 style="color: black; text-align: center;">Grade 12 <?= $strand ?></h4>
        <?php foreach ($semesters as $sem): ?>
          <?php if (!empty($grades['12'][$sem])): ?>
            <h5><?= $sem ?></h5>
            <ul style="padding-left: 20px;">
              <?php foreach ($grades['12'][$sem] as $subj): ?>
                <li><?= htmlspecialchars($subj) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Strand Modal -->
<div id="strandModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeStrandModal()">&times;</span>
    <h3>Manage Strands</h3>
    <form method="POST">
      <label>Strand Name:</label>
      <input type="text" name="strand_name" required style="width:100%;padding:8px;margin-bottom:10px;" />
      <button class="btn" type="submit" name="add_strand">Add Strand</button>
    </form>
    <hr style="margin:15px 0;">
    <form method="POST">
      <label>Edit Existing Strand:</label>
      <select name="strand_id" style="width:100%;padding:8px;margin-bottom:10px;">
        <?php foreach ($strands as $row): ?>
          <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="new_name" placeholder="New Strand Name" required style="width:100%;padding:8px;" />
      <button class="btn btn-secondary" type="submit" name="edit_strand">Edit Strand</button>
    </form>
  </div>
</div>

<script>
function filterForm() {
  const selectedType = document.getElementById('student_type').value;
  const selectedStrand = document.querySelector('select[name=\"strand\"]').value;
  const gradeContainer = document.getElementById('grade_level_container');
  const subjectContainer = document.getElementById('subject_container');
  const strandContainer = document.getElementById('strand_container');

  gradeContainer.style.display = subjectContainer.style.display = (selectedType === 'JHS' || selectedType === 'SHS') ? 'block' : 'none';
  strandContainer.style.display = (selectedType === 'SHS') ? 'block' : 'none';

  for (let i = 7; i <= 12; i++) {
    const box = document.getElementById('grade_box_' + i);
    box.style.display = (selectedType === 'JHS' && i <= 10 || selectedType === 'SHS' && i >= 11) ? 'block' : 'none';
  }

  // Filter subject checkboxes
  document.querySelectorAll('.subject-row').forEach(row => {
    const subjectType = row.dataset.type;
    const strand = row.dataset.strand;

if (subjectType === selectedType) {
  if (selectedType === 'SHS') {
    const strandName = row.dataset.strandName; // use the name instead
    row.style.display = (selectedStrand === '' || strandName === selectedStrand) ? 'flex' : 'none';
  } else {
    row.style.display = 'flex';
  }
} else {
  row.style.display = 'none';
}
  });
}

function openStrandModal() {
  document.getElementById("strandModal").style.display = "block";
}
function closeStrandModal() {
  document.getElementById("strandModal").style.display = "none";
}

window.onclick = function(event) {
  const modal = document.getElementById("strandModal");
  if (event.target === modal) modal.style.display = "none";
};

function toggleSelectAll(checkbox) {
  const visibleSubjects = document.querySelectorAll('#filteredSubjects label[style*=\"display: flex\"] input[type=checkbox]');
  visibleSubjects.forEach(cb => cb.checked = checkbox.checked);
}

function filterSubjectSearch() {
  const input = document.getElementById("subjectSearch").value.toLowerCase();
  const labels = document.querySelectorAll(".subject-row");

  labels.forEach(label => {
    const subject = label.textContent.toLowerCase();
    const strand = label.getAttribute("data-strand-name")?.toLowerCase() || "";

    if (subject.includes(input) || strand.includes(input)) {
      label.style.display = "flex";
    } else {
      label.style.display = "none";
    }
  });
}

function navigate(path) {
  if (path) {
    window.location.href = path;
  }
}
</script>

<!-- Ionicons -->
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>