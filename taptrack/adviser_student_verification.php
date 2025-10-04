<?php
session_start(); // ← KAILANGAN ITO MUNA
include 'db_connection.php'; 

// Get the teacher_id from session
$teacher_id = $_SESSION['teacher_id'] ?? null;

if (!$teacher_id) {
    // Optional: Redirect or show error if not logged in properly
    die("Unauthorized access. Please log in.");
}

// Helper function to format names as "LastName, FirstName M."
function formatName($first, $middle, $last) {
    $cap = function($str) {
        return ucfirst(strtolower($str));
    };
    
    $lastName = $cap($last);
    $firstName = $cap($first);
    $middleInitial = $middle ? strtoupper(substr($middle, 0, 1)) . '.' : '';
    
    return "{$lastName}, {$firstName} {$middleInitial}";
}

// Get students only in sections assigned to this teacher
$sql = "SELECT ps.*, s.section_name
        FROM pending_students ps
        JOIN sections s ON ps.section = s.section_name
        JOIN section_advisers sa ON sa.section_id = s.id
        WHERE sa.teacher_id = ?
        ORDER BY ps.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Verification</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/verification.css" />
    <style>
        /* Professional Rejection Modal Styles */
        .rejection-modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            transition: all 0.3s ease;
            overflow: auto;
        }

        .rejection-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border: none;
            width: 500px;
            max-width: 90%;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.3s ease-out;
            position: relative;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .rejection-content h2 {
            color: #d9534f;
            margin-top: 0;
            font-size: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            padding-right: 30px; /* Make space for close button */
        }

        .rejection-content p {
            color: #555;
            margin-bottom: 20px;
        }

        .rejection-reasons {
            margin: 25px 0;
        }

        .reason-item {
            margin: 15px 0;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .reason-item:hover {
            background-color: #f9f9f9;
        }

        .reason-item input[type="checkbox"] {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid #ccc;
            border-radius: 4px;
            margin-right: 12px;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
        }

        .reason-item input[type="checkbox"]:checked {
            background-color: #d9534f;
            border-color: #d9534f;
        }

        .reason-item input[type="checkbox"]:checked::after {
            content: "✓";
            position: absolute;
            color: white;
            font-size: 12px;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .reason-item label {
            color: #333;
            cursor: pointer;
            flex-grow: 1;
        }

        .custom-reason {
            margin-top: 15px;
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            transition: border-color 0.3s;
            display: none;
        }

        .custom-reason:focus {
            outline: none;
            border-color: #d9534f;
            box-shadow: 0 0 0 3px rgba(217, 83, 79, 0.1);
        }

        .rejection-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
        }

        .cancel-btn, .confirm-reject-btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .cancel-btn {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }

        .cancel-btn:hover {
            background-color: #e9e9e9;
        }

        .confirm-reject-btn {
            background-color: #d9534f;
            color: white;
            border: 1px solid #d9534f;
        }

        .confirm-reject-btn:hover {
            background-color: #c9302c;
            border-color: #c12e2a;
        }

        .close {
            color: #aaa;
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
            z-index: 1;
        }

        .close:hover {
            color: red;
        }
        
        /* Fullscreen image viewer */
.image-viewer {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
    text-align: center;
    overflow: auto;
}

.image-viewer img {
    margin-top: 5%;
    max-width: 90%;
    max-height: 85%;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.5);
    cursor: zoom-out;
}

.image-viewer .close-viewer {
    position: absolute;
    top: 20px;
    right: 35px;
    color: white;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
}

    </style>
</head>

<body>
    <!-- Include Sidebar -->
    <?php include('adviser_sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <h2>Student Verification</h2>

        <!-- Search Bar -->
        <div class="search-container">
            <div class="left-search">
                <form id="searchForm" onsubmit="return handleSearch(event)">
                    <input type="text" id="searchInput" placeholder="Search by LRN and Name..." />
                    <button type="submit">Search</button>
                </form>
            </div>
        </div>

        <table class="student-table">
            <thead>
                <tr>
                    <th>LRN</th>
                    <th>Name</th>
                    <th>Section</th>
                    <th>Registered Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="studentTableBody">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['lrn']) . "</td>";
                        echo "<td>" . htmlspecialchars(formatName($row['first_name'], $row['middle_name'], $row['last_name'])) . "</td>";
                        echo "<td>" . htmlspecialchars($row['section_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                        echo "<td>
                            <div class='action-buttons'>
                                <button class='approve-btn' onclick='approveStudent(" . $row['id'] . ")'>Approve</button>
                                <button class='reject-btn' onclick='showRejectionModal(" . $row['id'] . ")'>Reject</button>
                                <button class='view-btn' onclick='viewStudent(" . htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') . ")' title='View'>
                                    <ion-icon name='eye-outline'></ion-icon>
                                </button>
                            </div>
                        </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No pending students.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Rejection Reason Modal -->
    <div id="rejectionModal" class="rejection-modal">
        <div class="rejection-content">
            <span class="close" onclick="closeRejectionModal()">&times;</span>
            <h2>Reject Student Application</h2>
            <p>Please select the reason(s) for rejection:</p>
            
            <div class="rejection-reasons">
                <div class="reason-item">
                    <input type="checkbox" id="reason1" name="rejection_reason" value="Incomplete Documents">
                    <label for="reason1">Incomplete Documents</label>
                </div>
                <div class="reason-item">
                    <input type="checkbox" id="reason2" name="rejection_reason" value="Invalid Information">
                    <label for="reason2">Invalid Information</label>
                </div>
                <div class="reason-item">
                    <input type="checkbox" id="reason3" name="rejection_reason" value="Duplicate Registration">
                    <label for="reason3">Duplicate Registration</label>
                </div>
                <div class="reason-item">
                    <input type="checkbox" id="reason4" name="rejection_reason" value="Does Not Meet Requirements">
                    <label for="reason4">Does Not Meet Requirements</label>
                </div>
                <div class="reason-item">
                    <input type="checkbox" id="reason5" name="rejection_reason" value="Other">
                    <label for="reason5">Other (please specify below)</label>
                </div>
                <textarea id="customReason" class="custom-reason" placeholder="Enter detailed reason for rejection..." rows="3"></textarea>
            </div>
            
            <div class="rejection-actions">
                <button class="cancel-btn" onclick="closeRejectionModal()">Cancel</button>
                <button class="confirm-reject-btn" onclick="confirmRejection()">Confirm Rejection</button>
            </div>
        </div>
    </div>
    
<!-- Modal for Student Details -->
<div id="studentModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>

        <h2>Student Details</h2>
            <div class="modal-form">
                <div class="form-row">
                    <div class="form-column">
                        <label>First Name:</label>
                        <input type="text" id="modalFirstName" readonly>
                    </div>
                    <div class="form-column">
                        <label>Middle Name:</label>
                        <input type="text" id="modalMiddleName" readonly>
                    </div>
                    <div class="form-column">
                        <label>Last Name:</label>
                        <input type="text" id="modalLastName" readonly>
                    </div>
                </div>

                <div class="modal-form">
                <div class="form-row">
                    <div class="form-column">
                        <label>LRN:</label>
                        <input type="text" id="modalLRN" readonly>
                    </div>
                    <div class="form-column">
                        <label>Date of Birth:</label>
                        <input type="date" id="modalDOB" readonly>
                    </div>
                    <div class="form-column">
                        <label>Gender:</label>
                        <input type="gender" id="modalGender" readonly>
                    </div>
                </div>
                
                <div class="modal-form">
                <div class="form-row">
                    <div class="form-column">
                        <label>Citizenship:</label>
                        <input type="text" id="modalCitizenship" readonly>
                    </div>
                    <div class="form-column">
                        <label>Contact Number:</label>
                        <input type="text" id="modalContact" readonly>
                    </div>
                </div>

                <div class="modal-form">
                <div class="form-row">
                    <div class="form-column">
                        <label>Address:</label>
                        <input type="text" id="modalAddress" readonly>
                    </div>
                    <div class="form-column">
                        <label>Email Address:</label>
                        <input type="text" id="modalEmail" readonly>
                    </div>
                </div>

                <div class="modal-form">
                <div class="form-row">
                    <div class="form-column">
                        <label>Section:</label>
                        <input type="text" id="modalSection" readonly>
                    </div>
                    <div class="form-column">
                        <label>Grade Level:</label>
                        <input type="text" id="modalGradeLevel" readonly>
                    </div>
                    <div class="form-column">
                        <label>School Year:</label>
                        <input type="text" id="modalSchoolYear" readonly>
                    </div>
                    <div class="form-column">
                        <label>Student Type:</label>
                        <input type="text" id="modalStudentType" readonly>
                    </div>
                </div>

                <h2>Parent/Guardian Information</h2>
                <div class="modal-form">
                <div class="form-row">
                    <div class="form-column">
                        <label>Guardian Name:</label>
                        <input type="text" id="modalGuardianName" readonly>
                    </div>
                    <div class="form-column">
                        <label>Contact Number:</label>
                        <input type="text" id="modalGuardianContact" readonly>
                    </div>
                    </div>
                    
                    <div class="modal-form">
                    <div class="form-column">
                        <label>Guardian Address:</label>
                        <input type="text" id="modalGuardianAddress" readonly>
                    </div>
                    <div class="form-column">
                        <label>Relationship:</label>
                        <input type="text" id="modalGuardianRelationship" readonly>
                    </div>
                </div>

        <h2>Academic Information</h2>
        <div class="modal-form">
                <div class="form-row">
                    <div class="form-column">
                        <label>Elementary School:</label>
                        <input type="text" id="modalElemSchool" readonly>
                    </div>
                    <div class="form-column">
                        <label>Year Graduated:</label>
                        <input type="text" id="modalYearGraduated" readonly>
                    </div>
                </div>
     
        <div class="grid-container">
            <div class="form-column">
                <label>Recent ID Photo:</label>
                <img id="modalIDPhoto" class="doc-img" readonly>
            </div>
            <div class="form-column">
                <label>Birth Certificate:</label>
                <img id="modalBirthCert" class="doc-img" readonly>
            </div>
            <div class="form-column">
                <label>Good Moral Certificate:</label>
                <img id="modalGoodMoral" class="doc-img" readonly>
            </div>
            <div class="form-column">
                <label>Student Signature:</label>
                <img id="modalSignatureImage" class="doc-img" readonly>
            </div>
        </div>
        
        <!-- Fullscreen Image Viewer -->
<div id="imageViewer" class="image-viewer">
    <span class="close-viewer" onclick="closeImageViewer()">&times;</span>
    <img id="viewerImage" src="" alt="Preview">
</div>


<!-- JavaScript for Search Functionality -->
<script>
function searchStudent() {
    const input = document.getElementById("searchInput").value.toUpperCase();
    const rows = document.querySelectorAll("#studentTableBody tr");

    let hasMatch = false;

    rows.forEach(row => {
        const lrn = row.querySelector("td:nth-child(1)")?.textContent.toUpperCase() || '';
        const name = row.querySelector("td:nth-child(2)")?.textContent.toUpperCase() || '';

        if (lrn.includes(input) || name.includes(input)) {
            row.style.display = "";
            hasMatch = true;
        } else {
            row.style.display = "none";
        }
    });

    const noDataRow = document.getElementById("noDataRow");
    if (noDataRow) noDataRow.remove();

    if (!hasMatch) {
        const tbody = document.getElementById("studentTableBody");
        const newRow = document.createElement("tr");
        newRow.id = "noDataRow";
        newRow.innerHTML = `<td colspan="5">No matching results.</td>`;
        tbody.appendChild(newRow);
    }
}

    document.addEventListener("DOMContentLoaded", () => {
      document.getElementById("searchInput").addEventListener("input", searchStudent);
    });

    function approveStudent(studentId) {
        if (confirm("Are you sure you want to approve this student? An approval email will be sent.")) {
            // Show loading state
            const approveBtn = document.querySelector(`button[onclick="approveStudent(${studentId})"]`);
            const originalText = approveBtn.textContent;
            approveBtn.disabled = true;
            approveBtn.textContent = 'Processing...';
            
            // Redirect to approval script
            window.location.href = `adviser_approved_student.php?id=${studentId}`;
        }
    }

    let currentRejectionStudentId = null;

    function showRejectionModal(studentId) {
        currentRejectionStudentId = studentId;
        document.getElementById('rejectionModal').style.display = 'block';
    }

    function closeRejectionModal() {
        document.getElementById('rejectionModal').style.display = 'none';
        // Reset form
        document.querySelectorAll('input[name="rejection_reason"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('customReason').style.display = 'none';
        document.getElementById('customReason').value = '';
    }

    // Toggle custom reason textarea when "Other" is selected
    document.getElementById('reason5').addEventListener('change', function() {
        document.getElementById('customReason').style.display = this.checked ? 'block' : 'none';
    });

function confirmRejection() {
    const selectedReasons = [];
    document.querySelectorAll('input[name="rejection_reason"]:checked').forEach(checkbox => {
        if (checkbox.value === 'Other') {
            const customReason = document.getElementById('customReason').value.trim();
            if (customReason) {
                selectedReasons.push(customReason);
            }
        } else {
            selectedReasons.push(checkbox.value);
        }
    });

    if (selectedReasons.length === 0) {
        alert('Please select at least one rejection reason.');
        return;
    }

    if (confirm('Are you sure you want to reject this student? An email will be sent with the rejection reasons.')) {
        // Show loading state
        const rejectBtn = document.querySelector('.confirm-reject-btn');
        rejectBtn.disabled = true;
        rejectBtn.textContent = 'Processing...';
        
        fetch('adviser_reject_student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `student_id=${currentRejectionStudentId}&rejection_reasons=${encodeURIComponent(JSON.stringify(selectedReasons))}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        })
        .finally(() => {
            rejectBtn.disabled = false;
            rejectBtn.textContent = 'Confirm Rejection';
            closeRejectionModal();
        });
    }
}

function capitalizeFirstLetter(str) {
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

    function viewStudent(student) {
    console.log("Student ID Photo Path from DB: ", student.id_photo); // Debugging

document.getElementById("modalFirstName").value = capitalizeFirstLetter(student.first_name);
document.getElementById("modalMiddleName").value = capitalizeFirstLetter(student.middle_name);
document.getElementById("modalLastName").value = capitalizeFirstLetter(student.last_name);
    document.getElementById("modalLRN").value = student.lrn || "";
    document.getElementById("modalDOB").value = student.date_of_birth || "";
    document.getElementById("modalGender").value = student.gender || "";
    document.getElementById("modalCitizenship").value = student.citizenship || "";
    document.getElementById("modalAddress").value = student.address || "";
    document.getElementById("modalContact").value = student.contact_number || "";
    document.getElementById("modalEmail").value = student.email || "";
document.getElementById("modalSection").value = capitalizeFirstLetter(student.section);
    document.getElementById("modalGradeLevel").value = student.grade_level || "";
    document.getElementById("modalSchoolYear").value = student.school_year || "";
    document.getElementById("modalStudentType").value = student.student_type || "";
    document.getElementById("modalGuardianName").value = student.guardian_name || "";
    document.getElementById("modalGuardianAddress").value = student.guardian_address || "";
    document.getElementById("modalGuardianContact").value = student.guardian_contact || "";
document.getElementById("modalGuardianRelationship").value = capitalizeFirstLetter(student.guardian_relationship);
    document.getElementById("modalElemSchool").value = student.elementary_school || "";
    document.getElementById("modalYearGraduated").value = student.year_graduated || "";

    // Assign image paths correctly
    document.getElementById("modalIDPhoto").src = student.id_photo && student.id_photo.trim() !== "" 
        ? student.id_photo  // Use as is since it already includes "uploads/"
        : "uploads/default.png";

    document.getElementById("modalBirthCert").src = student.birth_certificate && student.birth_certificate.trim() !== "" 
        ? student.birth_certificate
        : "uploads/default.jpg";

    document.getElementById("modalGoodMoral").src = student.good_moral && student.good_moral.trim() !== "" 
        ? student.good_moral
        : "uploads/default.jpg";

    document.getElementById("modalSignatureImage").src = student.student_signature && student.student_signature.trim() !== "" 
        ? student.student_signature
        : "uploads/default.jpg";

    console.log("Final Image Path: ", document.getElementById("modalIDPhoto").src); // Debugging

    // Show the modal
    document.getElementById("studentModal").style.display = "block";
}

    // Close modal function
    function closeModal() {
        document.getElementById("studentModal").style.display = "none";
    }

    document.addEventListener("DOMContentLoaded", function () {
    // Hide modal initially
    document.getElementById("studentModal").style.display = "none";
});

// Open fullscreen image viewer
function openImageViewer(src) {
    document.getElementById("viewerImage").src = src;
    document.getElementById("imageViewer").style.display = "block";
}

// Close fullscreen image viewer
function closeImageViewer() {
    document.getElementById("imageViewer").style.display = "none";
    document.getElementById("viewerImage").src = "";
}

// Attach click event to document images when modal is shown
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".doc-img").forEach(img => {
        img.style.cursor = "zoom-in";
        img.addEventListener("click", function() {
            if (this.src && this.src.trim() !== "") {
                openImageViewer(this.src);
            }
        });
    });
});

</script>

        <!--Scripts-->
        <script src="assets/js/main.js"></script>      

        <!--ionicons-->
        <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
        <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>