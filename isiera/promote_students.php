<?php
include 'db_connection.php';

$section = $_GET['section'] ?? '';
$grade_level = $_GET['grade_level'] ?? '';

if (isset($_GET['promoted'], $_GET['grade_level'], $_GET['section'])) {
    $grade_level = $_GET['grade_level'];
    $section = $_GET['section'];
}

// Fetch sections for all grade levels
$sections_by_grade = [];
$grades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

foreach ($grades as $grade) {
    $sql = "SELECT section_name FROM sections WHERE grade_level = ? ORDER BY section_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $grade);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row['section_name'];
    }
    
    $sections_by_grade[$grade] = $sections;
    $stmt->close();
}
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Promote Students</title>
    <link rel="stylesheet" href="assets/css/style.css" />  
    <link rel="stylesheet" href="assets/css/promotion_section.css" />
    <style>
        .promote-row {
            background-color: #f8f9fa;
        }
        
        .promote-btn {
            padding: 8px 16px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        
        .promote-btn:hover {
            background-color: #0b6609;
        }
        
        .promote-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
<?php include('sidebar.php'); ?>

<div class="main-content">
    <h2>Promote Students</h2>

    <div class="search-container">
        <form id="searchForm" onsubmit="return handleSearch(event)">
            <input type="text" id="searchInput" placeholder="Search by LRN and Name..." />
            <button type="submit">Search</button>
        </form>
    </div>

<form method="post" action="process_promotion.php">
    <input type="hidden" name="current_section" value="<?= htmlspecialchars($section) ?>" />
    <input type="hidden" name="current_grade" value="<?= htmlspecialchars($grade_level) ?>" />

    <div class="table-container">
        <table class="student-table" id="studentTable">
            <thead>
                <tr>
                    <th>LRN</th>
                    <th>Name</th>
                    <th class="actions-header" style="position: relative;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Actions</span>
                            <ion-icon name="ellipsis-vertical-outline" class="header-icon" id="promoteHeaderDropdownToggle" style="cursor: pointer;"></ion-icon>
                        </div>
                        <div id="promoteHeaderDropdownMenu" class="dropdown-menu" style="position: absolute; top: 100%; right: 0; background: white; border: 1px solid #ccc; display: none;">
                            <div class="dropdown-item" id="selectAllOption" style="padding: 8px 15px; cursor: pointer;">Select All</div>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT lrn, last_name, first_name, middle_name 
                        FROM students 
                        WHERE section = ? AND grade_level = ?
                        ORDER BY last_name ASC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $section, $grade_level);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $hasStudents = $result->num_rows > 0;
                
                if ($hasStudents) {
                    while ($row = $result->fetch_assoc()) {
                        $lrn = htmlspecialchars($row['lrn']);
                        $last_name = ucwords(strtolower($row['last_name']));
                        $first_name = ucwords(strtolower($row['first_name']));
                        $middle_initial = strtoupper(substr($row['middle_name'], 0, 1));
                        $full_name = "{$last_name}, {$first_name}" . ($middle_initial ? " {$middle_initial}." : "");
                        echo "<tr>
                                <td>{$lrn}</td>
                                <td>{$full_name}</td>
                                <td><input type='checkbox' class='student-checkbox' name='selected_students[]' value='{$lrn}'></td>
                              </tr>";
                    }
                    
                    // Add the promote button row as the last row in the table
                    echo "<tr class='promote-row'>
                            <td colspan='3' style='text-align: right; padding-right: 10px;'>
                                <button type='button' class='promote-btn' onclick='handlePromotion(\"$grade_level\")'>
                                    " . (($grade_level == 'Grade 10' || $grade_level == 'Grade 12') ? 'Promote' : 'Promote') . "
                                </button>
                            </td>
                          </tr>";
                } else {
                    echo "<tr id='noDataRow'><td colspan='3'>No data available.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</form>

<?php
$stmt->close();
$conn->close();
?>

<!-- Updated Modal with dropdown -->
<div class="section-modal" id="sectionModal">
    <div class="section-modal-content">
        <span class="section-close" onclick="closeSectionModal()">&times;</span>
        <h3>Select New Section</h3>
        <form id="sectionForm" onsubmit="handleSectionSubmit(event)">
            <select id="sectionSelect" name="new_section" required>
                <option value="">-- Select Section --</option>
                <!-- Options will be populated by JavaScript -->
            </select>
            <input type="hidden" id="currentGrade" value="" />
            <button type="submit">Confirm</button>
        </form>
    </div>
</div>

<!-- Scripts -->
<script>
// Get sections data from PHP
const sectionsByGrade = <?php echo json_encode($sections_by_grade); ?>;

function getNextGradeLevel(currentGrade) {
    const gradeOrder = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
    const currentIndex = gradeOrder.indexOf(currentGrade);
    return gradeOrder[currentIndex + 1] || currentGrade;
}

function handlePromotion(currentGrade) {
    const checked = document.querySelectorAll("input[name='selected_students[]']:checked");
    if (checked.length === 0) {
        alert("Please select at least one student.");
        return;
    }
    
    // For Grade 10 and Grade 12, archive graduates directly
    if (currentGrade === 'Grade 10' || currentGrade === 'Grade 12') {
        const graduateType = currentGrade === 'Grade 10' ? 'JHS Graduates' : 'SHS Graduates';
        
        if (!confirm(`Promote selected students as ${graduateType}?`)) {
            return;
        }
        
        const form = document.querySelector("form[action='process_promotion.php']");
        const archiveInput = document.createElement("input");
        archiveInput.type = "hidden";
        archiveInput.name = "archive_graduates";
        archiveInput.value = "true";
        form.appendChild(archiveInput);
        
        const graduateTypeInput = document.createElement("input");
        graduateTypeInput.type = "hidden";
        graduateTypeInput.name = "graduate_type";
        graduateTypeInput.value = graduateType;
        form.appendChild(graduateTypeInput);
        
        form.submit();
    } else {
        // For other grades, open section selection modal
        openSectionModal(currentGrade);
    }
}

function openSectionModal(currentGrade) {
    // Set current grade in hidden field
    document.getElementById('currentGrade').value = currentGrade;
    
    // Clear and populate section dropdown
    const sectionSelect = document.getElementById('sectionSelect');
    sectionSelect.innerHTML = '<option value="">-- Select Section --</option>';
    
    const nextGrade = getNextGradeLevel(currentGrade);
    const sections = sectionsByGrade[nextGrade];
    
    if (sections && sections.length > 0) {
        sections.forEach(section => {
            const option = document.createElement('option');
            option.value = section;
            option.textContent = section;
            sectionSelect.appendChild(option);
        });
    } else {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No sections available for ' + nextGrade;
        option.disabled = true;
        sectionSelect.appendChild(option);
    }
    
    document.getElementById("sectionModal").classList.add("show");
}

function closeSectionModal() {
    document.getElementById("sectionModal").classList.remove("show");
}

function handleSectionSubmit(event) {
    event.preventDefault();
    const section = document.getElementById("sectionSelect").value.trim();
    if (!section) {
        alert("Please select a section.");
        return;
    }
    
    const currentGrade = document.getElementById("currentGrade").value;
    const nextGrade = getNextGradeLevel(currentGrade);
    
    if (!confirm(`Promote selected students to ${nextGrade} and assign them to section ${section}?`)) {
        return;
    }
    
    const form = document.querySelector("form[action='process_promotion.php']");
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = "new_section";
    input.value = section;
    form.appendChild(input);
    
    // Add next grade level to form
    const gradeInput = document.createElement("input");
    gradeInput.type = "hidden";
    gradeInput.name = "next_grade";
    gradeInput.value = nextGrade;
    form.appendChild(gradeInput);
    
    form.submit();
}

function searchStudent() {
    const input = document.getElementById("searchInput").value.toUpperCase();
    const rows = document.querySelectorAll("#studentTable tbody tr");
    const promoteBtn = document.querySelector(".promote-btn");

    let hasMatch = false;
    let hasStudents = false;

    rows.forEach(row => {
        if (row.classList.contains('promote-row')) return;
        
        const lrn = row.cells[0]?.textContent.toUpperCase() || "";
        const name = row.cells[1]?.textContent.toUpperCase() || "";
        const match = lrn.includes(input) || name.includes(input);

        row.style.display = match ? "" : "none";
        if (match) {
            hasMatch = true;
            hasStudents = true;
        }
    });

    const existingNoData = document.getElementById("noDataRow");
    if (existingNoData && existingNoData.id === "noDataRow") {
        existingNoData.remove();
    }

    if (!hasMatch) {
        const tbody = document.querySelector("#studentTable tbody");
        const newRow = document.createElement("tr");
        newRow.id = "noDataRow";
        newRow.innerHTML = `<td colspan="3">No matching results.</td>`;
        tbody.appendChild(newRow);
        hasStudents = false;
    }

    // Enable/disable promote button based on whether there are students
    if (promoteBtn) {
        promoteBtn.disabled = !hasStudents;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("searchInput").addEventListener("input", searchStudent);
    
    // Dropdown toggle
    const promoteToggle = document.getElementById('promoteHeaderDropdownToggle');
    const promoteDropdown = document.getElementById('promoteHeaderDropdownMenu');
    const selectAllOption = document.getElementById('selectAllOption');
    let allSelected = false;

    promoteToggle.addEventListener('click', e => {
        e.stopPropagation();
        promoteDropdown.style.display = promoteDropdown.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', () => {
        promoteDropdown.style.display = 'none';
    });

    selectAllOption.addEventListener('click', () => {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        allSelected = !allSelected;
        checkboxes.forEach(cb => cb.checked = allSelected);
        selectAllOption.textContent = allSelected ? 'Deselect All' : 'Select All';
        promoteDropdown.style.display = 'none';
    });
});
</script>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>