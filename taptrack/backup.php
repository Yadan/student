import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:intl/intl.dart';
import 'dart:async';
import 'package:path_provider/path_provider.dart';
import 'package:open_file/open_file.dart';
import 'dart:io';
import 'database_helper.dart';
import 'connectivity_service.dart';

// Add this to your Drawer in DashboardPage

void main() {
  runApp(const TapInTimeApp());
}

class TapInTimeApp extends StatelessWidget {
  const TapInTimeApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'ISIERA',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.deepPurple),
        useMaterial3: true,
      ),
      home: const LoginPage(),
    );
  }
}

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final TextEditingController teacherIdController = TextEditingController();
  final TextEditingController dobController = TextEditingController();

void _login() async {
  String teacherId = teacherIdController.text.trim();
  String dobRaw = dobController.text.trim(); // Expected MMDDYYYY

  if (teacherId.isEmpty || dobRaw.isEmpty || dobRaw.length != 8) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Please enter a valid Teacher ID and DOB (MMDDYYYY)')),
    );
    return;
  }

  try {
    String mm = dobRaw.substring(0, 2);
    String dd = dobRaw.substring(2, 4);
    String yyyy = dobRaw.substring(4, 8);
    String formattedDob = '$yyyy-$mm-$dd'; // 'YYYY-MM-DD'

    final url = Uri.parse('http://192.168.1.57/isiera/teacher_login.php');

    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'teacher_id': teacherId,
        'dob': formattedDob,
      }),
    );

    final data = jsonDecode(response.body);

    if (data['success'] == true) {
      final teacher = data['teacher'];

      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (context) => DashboardPage(
            teacherName: teacher['name'],
            teacherId: teacher['id'],
          ),
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(data['message'])),
      );
    }
  } catch (e) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Error: $e')),
    );
  }
}


  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // Background logo
          Positioned.fill(
  child: IgnorePointer(
    child: Opacity(
      opacity: 0.1,
      child: Image.asset(
        'assets/imgs/dahs.jpg',
        fit: BoxFit.cover,
      ),
    ),
  ),
),
          // Login Form
          Center(
            child: Padding(
              padding: const EdgeInsets.all(24.0),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Image.asset(
                  'assets/imgs/dahs.jpg',width: 120,),
                    const SizedBox(height: 30),
                    TextField(
                      controller: teacherIdController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(
                        labelText: 'Teacher ID',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: dobController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(
                        labelText: 'Birthday (MMDDYYYY)',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: _login,
                      style: ElevatedButton.styleFrom(
                        minimumSize: const Size(double.infinity, 50),
                      ),
                      child: const Text('Login'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

//Dashboard

class DashboardPage extends StatelessWidget {
  final String teacherName;
  final String teacherId;

  const DashboardPage({
    super.key,
    required this.teacherName,
    required this.teacherId,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Dashboard')),
      drawer: Drawer(
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            DrawerHeader(
              decoration: const BoxDecoration(color: Colors.deepPurple),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('👋 Hello, $teacherName',
                      style: const TextStyle(color: Colors.white, fontSize: 18)),
                  Text('ID: $teacherId',
                      style: const TextStyle(color: Colors.white70)),
                ],
              ),
            ),
            ListTile(
              leading: const Icon(Icons.group),
              title: const Text('Assigned Students'),
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => AssignedStudentsPage(
                      teacherId: teacherId,
                      teacherName: teacherName,
                    ),
                  ),
                );
              },
            ),
ListTile(
  leading: const Icon(Icons.check_circle_outline),
  title: const Text('Record Attendance'),
  onTap: () {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => RecordAttendancePage(
          teacherId: teacherId,
          teacherName: teacherName,
        ),
      ),
    );
  },
),
            ListTile(
  leading: const Icon(Icons.history),
  title: const Text('Attendance Records'),
  onTap: () {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => AttendanceRecordsPage(
          teacherId: teacherId,
          teacherName: teacherName,
        ),
      ),
    );
  },
),
            ListTile(
              leading: const Icon(Icons.settings),
              title: const Text('User Settings'),
              onTap: () {
                // TODO: User settings page
              },
            ),
            // Add divider and sign out option
            const Divider(),
            ListTile(
              leading: const Icon(Icons.logout, color: Colors.red),
              title: const Text('Sign Out', style: TextStyle(color: Colors.red)),
              onTap: () {
                Navigator.pushAndRemoveUntil(
                  context,
                  MaterialPageRoute(builder: (context) => const LoginPage()),
                  (route) => false,
                );
              },
            ),
          ],
        ),
      ),
      body: const Center(
        child: Text('Welcome to the Teacher Dashboard!'),
      ),
    );
  }
}

//LIST OF ASSIGNED STUDENTS

class AssignedStudentsPage extends StatefulWidget {
  final String teacherId;
  final String teacherName;

  const AssignedStudentsPage({
    super.key,
    required this.teacherId,
    required this.teacherName,
  });

  @override
  State<AssignedStudentsPage> createState() => _AssignedStudentsPageState();
}

class _AssignedStudentsPageState extends State<AssignedStudentsPage> {
  Map<String, dynamic>? selectedSubject;
  Map<String, dynamic>? selectedSection;
  List<Map<String, dynamic>> subjects = [];
  List<Map<String, dynamic>> sections = [];
  List<dynamic> students = [];
  bool isLoading = false;

  @override
  void initState() {
    super.initState();
    fetchSubjects();
  }

  Future<void> fetchSubjects() async {
    setState(() => isLoading = true);
    final url = Uri.parse('http://192.168.1.57/isiera/get_teacher_subjects.php');
    
    try {
      final response = await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'teacher_id': widget.teacherId}),
      );

      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        setState(() {
          subjects = List<Map<String, dynamic>>.from(data['subjects']);
          isLoading = false;
        });
      } else {
        throw Exception(data['message'] ?? 'Failed to load subjects');
      }
    } catch (e) {
      setState(() => isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error loading subjects: $e')),
      );
    }
  }

Future<void> fetchSections(int subjectId) async {
  setState(() => isLoading = true);
  final url = Uri.parse('http://192.168.1.57/isiera/get_teacher_sections.php');
  
  try {
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'teacher_id': widget.teacherId,
        'subject_id': subjectId,
      }),
    ).timeout(const Duration(seconds: 10));

    print('Sections Response: ${response.body}'); // Debug print

    // Handle potential non-JSON responses
    if (response.body.trim().isEmpty) {
      throw Exception('Empty response from server');
    }

    final dynamic decoded = jsonDecode(response.body);
    final Map<String, dynamic> data = decoded is Map ? decoded : jsonDecode(decoded);

    if (data['success'] == true) {
      setState(() {
        sections = List<Map<String, dynamic>>.from(data['sections']);
        isLoading = false;
      });
    } else {
      throw Exception(data['message'] ?? 'Failed to load sections');
    }
  } on FormatException catch (e) {
    setState(() => isLoading = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Invalid server response format: $e')),
    );
    print('FormatException details: $e');
  } catch (e) {
    setState(() => isLoading = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Error loading sections: ${e.toString()}')),
    );
    print('Error details: $e');
  }
}

  Future<void> fetchStudents(int subjectId, int sectionId) async {
    setState(() => isLoading = true);
    final url = Uri.parse('http://192.168.1.57/isiera/get_teacher_students.php');
    
    try {
      final response = await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'teacher_id': widget.teacherId,
          'subject_id': subjectId,
          'section_id': sectionId,
        }),
      );

      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        setState(() {
          students = List<dynamic>.from(data['students']);
          isLoading = false;
        });
      } else {
        throw Exception(data['message'] ?? 'Failed to load students');
      }
    } catch (e) {
      setState(() => isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error loading students: $e')),
      );
    }
  }

  Widget _buildSubjectList() {
    return ListView.builder(
      itemCount: subjects.length,
      itemBuilder: (context, index) {
        final subject = subjects[index];
        return Card(
          margin: const EdgeInsets.symmetric(vertical: 6, horizontal: 10),
          child: ListTile(
            leading: const Icon(Icons.book_outlined),
            title: Text(subject['name']),
            onTap: () {
              setState(() {
                selectedSubject = subject;
                selectedSection = null;
                students.clear();
              });
              fetchSections(subject['id']);
            },
          ),
        );
      },
    );
  }

  Widget _buildSectionList() {
    return ListView.builder(
      itemCount: sections.length,
      itemBuilder: (context, index) {
        final section = sections[index];
        return Card(
          margin: const EdgeInsets.symmetric(vertical: 6, horizontal: 10),
          child: ListTile(
            leading: const Icon(Icons.class_),
            title: Text(section['name']),
            subtitle: Text(selectedSubject?['name'] ?? ''),
            onTap: () {
              setState(() {
                selectedSection = section;
              });
              fetchStudents(selectedSubject!['id'], section['id']);
            },
          ),
        );
      },
    );
  }

  Widget _buildStudentList() {
    return ListView.builder(
      itemCount: students.length,
      itemBuilder: (context, index) {
        final student = students[index];
        return Card(
          margin: const EdgeInsets.symmetric(vertical: 6, horizontal: 10),
          child: ListTile(
            leading: const Icon(Icons.person),
            title: Text(student['name']),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('LRN: ${student['lrn']}'),
                Text('Section: ${student['section']}'),
                Text('Grade: ${student['grade_level']}'),
                Text('RFID: ${student['rfid']}'),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildBackButton(VoidCallback onPressed, String label) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: ElevatedButton.icon(
        icon: const Icon(Icons.arrow_back),
        label: Text(label),
        onPressed: onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.deepPurple.shade100,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Assigned Students'),
        backgroundColor: Colors.deepPurple,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : Padding(
              padding: const EdgeInsets.all(16),
              child: Builder(
                builder: (context) {
                  if (selectedSubject == null) {
                    return _buildSubjectList();
                  } else if (selectedSection == null) {
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildBackButton(() {
                          setState(() {
                            selectedSubject = null;
                            sections.clear();
                          });
                        }, 'Back to Subjects'),
                        const SizedBox(height: 10),
                        const Text('Select a section:',
                            style: TextStyle(fontSize: 16)),
                        const SizedBox(height: 10),
                        Expanded(child: _buildSectionList()),
                      ],
                    );
                  } else {
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildBackButton(() {
                          setState(() {
                            selectedSection = null;
                            students.clear();
                          });
                        }, 'Back to Sections'),
                        const SizedBox(height: 10),
                        Text(
                          '${selectedSubject!['name']} - ${selectedSection!['name']}',
                          style: const TextStyle(
                              fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 10),
                        Expanded(child: _buildStudentList()),
                      ],
                    );
                  }
                },
              ),
            ),
    );
  }
}

// MODIFIED RecordAttendancePage
class RecordAttendancePage extends StatefulWidget {
  final String teacherId;
  final String teacherName;

  const RecordAttendancePage({
    super.key,
    required this.teacherId,
    required this.teacherName,
  });

  @override
  State<RecordAttendancePage> createState() => _RecordAttendancePageState();
}

class _RecordAttendancePageState extends State<RecordAttendancePage> {
  List<Map<String, dynamic>> subjects = [];
  bool isLoading = false;

  @override
  void initState() {
    super.initState();
    fetchSubjects();
  }

  Future<void> fetchSubjects() async {
    setState(() => isLoading = true);
    try {
      final response = await http.post(
        Uri.parse('http://192.168.1.57/isiera/get_teacher_subjects.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'teacher_id': widget.teacherId}),
      );

      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        setState(() {
          subjects = List<Map<String, dynamic>>.from(data['subjects']);
        });
      } else {
        throw Exception(data['message'] ?? 'Failed to load subjects');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    } finally {
      setState(() => isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Select Subject'),
        backgroundColor: Colors.deepPurple,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : ListView.builder(
              itemCount: subjects.length,
              itemBuilder: (context, index) {
                final subject = subjects[index];
                return Card(
                  margin: const EdgeInsets.symmetric(vertical: 6, horizontal: 10),
                  child: ListTile(
                    leading: const Icon(Icons.book_outlined),
                    title: Text(subject['name']),
                    onTap: () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => SelectSectionPage(
                          teacherId: widget.teacherId,
                          subject: subject,
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
    );
  }
}

// NEW PAGE FOR SECTION SELECTION
class SelectSectionPage extends StatefulWidget {
  final String teacherId;
  final Map<String, dynamic> subject;

  const SelectSectionPage({
    super.key,
    required this.teacherId,
    required this.subject,
  });

  @override
  State<SelectSectionPage> createState() => _SelectSectionPageState();
}

class _SelectSectionPageState extends State<SelectSectionPage> {
  List<Map<String, dynamic>> sections = [];
  bool isLoading = false;

  @override
  void initState() {
    super.initState();
    fetchSections();
  }

  Future<void> fetchSections() async {
    setState(() => isLoading = true);
    try {
      final response = await http.post(
        Uri.parse('http://192.168.1.57/isiera/get_teacher_sections.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'teacher_id': widget.teacherId,
          'subject_id': widget.subject['id'],
        }),
      );

      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        setState(() {
          sections = List<Map<String, dynamic>>.from(data['sections']);
        });
      } else {
        throw Exception(data['message'] ?? 'Failed to load sections');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    } finally {
      setState(() => isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Select Section for ${widget.subject['name']}'),
        backgroundColor: Colors.deepPurple,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : ListView.builder(
              itemCount: sections.length,
              itemBuilder: (context, index) {
                final section = sections[index];
                return Card(
                  margin: const EdgeInsets.symmetric(vertical: 6, horizontal: 10),
                  child: ListTile(
                    leading: const Icon(Icons.class_),
                    title: Text(section['name']),
                    onTap: () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => AttendanceRecordingPage(
                          teacherId: widget.teacherId,
                          subject: widget.subject,
                          section: section,
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
    );
  }
}

// PAGE FOR ATTENDANCE RECORDING
class AttendanceRecordingPage extends StatefulWidget {
  final String teacherId;
  final Map<String, dynamic> subject;
  final Map<String, dynamic> section;
  

  const AttendanceRecordingPage({
    super.key,
    required this.teacherId,
    required this.subject,
    required this.section,
  });

  @override
  State<AttendanceRecordingPage> createState() => _AttendanceRecordingPageState();
}


class _AttendanceRecordingPageState extends State<AttendanceRecordingPage> {
  String rfidInput = '';
  String statusMessage = 'Ready for scan';
  Color messageColor = Colors.grey;
  bool isLoading = false;
  bool showSuccessPopup = false;
  Timer? _resetTimer;
  final FocusNode _rfidFocusNode = FocusNode();
  final TextEditingController _rfidController = TextEditingController();
  bool isExporting = false;

  @override
  void initState() {
    super.initState();
    // Set focus to the hidden text field when page loads
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _rfidFocusNode.requestFocus();
    });
  }

  @override
  void dispose() {
    _resetTimer?.cancel();
    _rfidFocusNode.dispose();
    _rfidController.dispose();
    super.dispose();
  }

  Future<void> recordAttendance(String rfid) async {
    if (isLoading) return; // Prevent multiple scans while processing
    
    _resetTimer?.cancel();
    _rfidController.clear(); // Clear the input for next scan
    
    setState(() {
      isLoading = true;
      statusMessage = 'Processing RFID...';
      messageColor = Colors.blue;
      showSuccessPopup = false;
    });

    try {
      final response = await http.post(
        Uri.parse('http://192.168.1.57/isiera/record_attendance.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'teacher_id': widget.teacherId,
          'subject_id': widget.subject['id'],
          'section_id': widget.section['id'],
          'rfid': rfid,
          'date': DateTime.now().toIso8601String(),
        }),
      );

      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        final studentName = data['data']['student']['name'] ?? 'Student';
        setState(() {
          statusMessage = 'Attendance recorded for $studentName';
          messageColor = Colors.green;
          showSuccessPopup = true;
        });

        _resetTimer = Timer(const Duration(seconds: 3), () {
          setState(() {
            showSuccessPopup = false;
            statusMessage = 'Ready for next scan';
            messageColor = Colors.grey;
          });
          // Return focus to the input field after success
          _rfidFocusNode.requestFocus();
        });
      } else {
        setState(() {
          statusMessage = data['message'] ?? 'Failed to record attendance';
          messageColor = Colors.red;
        });
        // Return focus immediately on error
        _rfidFocusNode.requestFocus();
      }
    } catch (e) {
      setState(() {
        statusMessage = 'Error: $e';
        messageColor = Colors.red;
      });
      // Return focus immediately on error
      _rfidFocusNode.requestFocus();
    } finally {
      setState(() => isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Record Attendance - ${widget.section['name']}'),
        backgroundColor: Colors.deepPurple,
      ),
      body: Stack(
        children: [
          Center(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Visual RFID Scanner UI
                  Container(
                    width: 200,
                    height: 200,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: _getScannerColor().withOpacity(0.1),
                      border: Border.all(
                        color: _getScannerColor(),
                        width: 3,
                      ),
                    ),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.credit_card,
                          size: 50,
                          color: _getScannerColor(),
                        ),
                        const SizedBox(height: 10),
                        Text(
                          _getScannerText(),
                          style: TextStyle(
                            fontSize: 18,
                            color: _getScannerColor(),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 30),
                  // Hidden text field for actual RFID input
                  SizedBox(
                    width: 0,
                    height: 0,
                    child: TextField(
                      focusNode: _rfidFocusNode,
                      controller: _rfidController,
                      autofocus: true,
                      onChanged: (value) => rfidInput = value,
                      onSubmitted: (value) {
                        if (value.isNotEmpty) {
                          recordAttendance(value);
                        }
                      },
                    ),
                  ),
                  const SizedBox(height: 20),
                  if (isLoading) const CircularProgressIndicator(),
                  Text(
                    statusMessage,
                    style: TextStyle(
                      color: messageColor,
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          ),
          // Success Popup
          if (showSuccessPopup)
            Center(
              child: Container(
                padding: const EdgeInsets.all(20),
                margin: const EdgeInsets.symmetric(horizontal: 30),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(15),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.2),
                      blurRadius: 10,
                      spreadRadius: 2,
                    ),
                  ],
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.check_circle,
                      color: Colors.green,
                      size: 50,
                    ),
                    const SizedBox(height: 15),
                    Text(
                      statusMessage,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  Color _getScannerColor() {
    if (isLoading) return Colors.blue;
    if (messageColor == Colors.green) return Colors.green;
    if (messageColor == Colors.red) return Colors.red;
    return Colors.deepPurple;
  }

  String _getScannerText() {
    if (isLoading) return 'Processing...';
    if (messageColor == Colors.green) return 'Success!';
    if (messageColor == Colors.red) return 'Try Again';
    return 'Tap RFID Card';
  }
}

//PAGE FOR ATTENDANCE VIEWING
//PAGE FOR ATTENDANCE VIEWING
class AttendanceRecordsPage extends StatefulWidget {
  final String teacherId;
  final String teacherName;

  const AttendanceRecordsPage({
    super.key,
    required this.teacherId,
    required this.teacherName,
  });

  @override
  State<AttendanceRecordsPage> createState() => _AttendanceRecordsPageState();
}

class _AttendanceRecordsPageState extends State<AttendanceRecordsPage> {
  List<Map<String, dynamic>> subjects = [];
  Map<String, dynamic>? selectedSubject;
  Map<String, dynamic>? selectedSection;
  List<Map<String, dynamic>> sections = [];
  List<Map<String, dynamic>> attendanceRecords = [];
  bool isLoading = false;
  DateTime _startDate = DateTime.now();
  DateTime _endDate = DateTime.now();
  bool _isDateRange = false;

  @override
  void initState() {
    super.initState();
    fetchSubjects();
  }

  Future<void> _selectDate(BuildContext context, bool isStartDate) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: isStartDate ? _startDate : _endDate,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );

    if (picked != null) {
      setState(() {
        if (isStartDate) {
          _startDate = picked;
          if (!_isDateRange || _endDate.isBefore(_startDate)) {
            _endDate = _startDate;
          }
        } else {
          _endDate = picked;
        }
      });

      fetchAttendanceRecords();
    }
  }

  Future<void> fetchSubjects() async {
    setState(() => isLoading = true);
    try {
      final response = await http.post(
        Uri.parse('http://192.168.1.57/isiera/get_teacher_subjects.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'teacher_id': widget.teacherId}),
      );

      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        setState(() {
          subjects = List<Map<String, dynamic>>.from(data['subjects']);
        });
      } else {
        throw Exception(data['message'] ?? 'Failed to load subjects');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    } finally {
      setState(() => isLoading = false);
    }
  }

  Future<void> fetchSections(int subjectId) async {
    setState(() => isLoading = true);
    try {
      final response = await http.post(
        Uri.parse('http://192.168.1.57/isiera/get_teacher_sections.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'teacher_id': widget.teacherId,
          'subject_id': subjectId,
        }),
      );

      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        setState(() {
          sections = List<Map<String, dynamic>>.from(data['sections']);
        });
      } else {
        throw Exception(data['message'] ?? 'Failed to load sections');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    } finally {
      setState(() => isLoading = false);
    }
  }

  Future<void> fetchAttendanceRecords() async {
    if (selectedSubject == null || selectedSection == null) return;

    setState(() => isLoading = true);
    try {
      final response = await http.post(
        Uri.parse('http://192.168.1.57/isiera/get_attendance_records.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'teacher_id': widget.teacherId,
          'subject_id': selectedSubject!['id'],
          'section_id': selectedSection!['id'],
          'start_date': DateFormat('yyyy-MM-dd').format(_startDate),
          'end_date': _isDateRange ? DateFormat('yyyy-MM-dd').format(_endDate) : null,
        }),
      );

      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        setState(() {
          attendanceRecords = List<Map<String, dynamic>>.from(data['records']);
        });
      } else {
        throw Exception(data['message'] ?? 'Failed to load attendance records');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    } finally {
      setState(() => isLoading = false);
    }
  }
Future<void> exportToCSV() async {
  if (attendanceRecords.isEmpty) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('No attendance records to export')),
    );
    return;
  }

  setState(() => isLoading = true);

  try {
    final buffer = StringBuffer();
    final String sectionName = selectedSection?['name']?.toString() ?? 'Unknown Section';
    
    // Add section header at the top
    buffer.writeln('Section: $sectionName');
    buffer.writeln();

    // Sort records by date first
    attendanceRecords.sort((a, b) => (a['date'] as String).compareTo(b['date'] as String));

    String currentDate = '';
    bool isFirstRecord = true;

    for (final record in attendanceRecords) {
      final recordDate = record['date']?.toString() ?? '';
      
      // Add spacing when date changes (3 empty rows)
      if (recordDate != currentDate) {
        if (!isFirstRecord) {
          buffer.writeln();
          buffer.writeln();
          buffer.writeln();
        }
        currentDate = recordDate;
        
        // Write date header
        buffer.writeln('Date,Name,Time,Status');
      }

      // Convert time to 12-hour format
      String formattedTime = '';
      try {
        final timeParts = (record['time']?.toString() ?? '').split(':');
        if (timeParts.length >= 2) {
          int hour = int.tryParse(timeParts[0]) ?? 0;
          final minute = timeParts[1];
          final period = hour >= 12 ? 'PM' : 'AM';
          hour = hour > 12 ? hour - 12 : hour;
          hour = hour == 0 ? 12 : hour;
          formattedTime = '$hour:$minute $period';
        }
      } catch (e) {
        formattedTime = record['time']?.toString() ?? '';
      }

      // Write record
      buffer.writeln([
        '"${record['date']?.toString() ?? ''}"',
        '"${record['student_name']?.toString().replaceAll('"', '""') ?? ''}"',
        '"$formattedTime"',
        '"${record['status']?.toString().replaceAll('"', '""') ?? ''}"',
      ].join(','));

      isFirstRecord = false;
    }

    // Generate filename
    String sanitize(String input) => input.replaceAll(RegExp(r'[^\w-]'), '_');
    final subjectName = sanitize(selectedSubject?['name']?.toString() ?? '');
    final sanitizedSectionName = sanitize(sectionName);
    final datePart = _isDateRange 
        ? '${_startDate.toString().substring(0, 10)}_to_${_endDate.toString().substring(0, 10)}'
        : _startDate.toString().substring(0, 10);
    
    final fileName = 'Attendance_${subjectName}_${sanitizedSectionName}_$datePart.csv';

    // Save file
    final directory = await getExternalStorageDirectory();
    final path = directory?.path ?? '/storage/emulated/0/Download';
    final file = File('$path/$fileName');
    await file.writeAsString(buffer.toString());

    // Open the file
    await OpenFile.open(file.path);

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Exported to $fileName')),
    );
  } catch (e) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Export failed: $e')),
    );
  } finally {
    setState(() => isLoading = false);
  }
}

// Add this new function for exporting attendance summary
Future<void> exportAttendanceSummary() async {
  if (attendanceRecords.isEmpty) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('No attendance records to export')),
    );
    return;
  }

  setState(() => isLoading = true);

  try {
    final buffer = StringBuffer();
    final String sectionName = selectedSection?['name']?.toString() ?? 'Unknown Section';
    final String subjectName = selectedSubject?['name']?.toString() ?? 'Unknown Subject';
    final String instructorName = widget.teacherName;
    final dateRange = '${DateFormat('MM/dd/yyyy').format(_startDate)}-${DateFormat('MM/dd/yyyy').format(_endDate)}';

    // Group records by student and count presents
    final Map<String, Map<String, dynamic>> studentData = {};
    
    for (final record in attendanceRecords) {
      final studentId = record['student_id'].toString();
      final studentName = record['student_name']?.toString() ?? 'Unknown';
      final status = (record['status']?.toString() ?? '').toLowerCase();
      
      if (!studentData.containsKey(studentId)) {
        studentData[studentId] = {
          'name': studentName,
          'present_count': 0,
          'dates': <String>[],
        };
      }
      
      if (status == 'present') {
        studentData[studentId]!['present_count'] += 1;
        studentData[studentId]!['dates'].add(record['date'] ?? '');
      }
    }

    // Write CSV header with all required information
    buffer.writeln('Subject: $subjectName');
    buffer.writeln('Section: $sectionName');
    buffer.writeln('Instructor: $instructorName');
    buffer.writeln('Date Range: $dateRange');
    buffer.writeln();
    buffer.writeln('Student Name,Total Present,Present Dates');
    
    // Write student data
    studentData.forEach((id, data) {
      buffer.writeln([
        '"${data['name']}"',
        data['present_count'].toString(),
        '"${(data['dates'] as List<String>).join(', ')}"',
      ].join(','));
    });

    // Generate filename
    String sanitize(String input) => input.replaceAll(RegExp(r'[^\w-]'), '_');
    final fileName = 'Attendance_Summary_${sanitize(subjectName)}_${sanitize(sectionName)}.csv';

    // Save file
    final directory = await getExternalStorageDirectory();
    final path = directory?.path ?? '/storage/emulated/0/Download';
    final file = File('$path/$fileName');
    await file.writeAsString(buffer.toString());

    await OpenFile.open(file.path);

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Attendance summary exported to $fileName')),
    );
  } catch (e) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Export failed: ${e.toString()}')),
    );
  } finally {
    setState(() => isLoading = false);
  }
}

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Attendance Records'),
        backgroundColor: Colors.deepPurple,
        actions: [
          if (selectedSubject != null && selectedSection != null && attendanceRecords.isNotEmpty)
      Row(
        children: [
          IconButton(
            icon: const Icon(Icons.summarize),
            onPressed: exportAttendanceSummary,
            tooltip: 'Export Summary',
          ),
          IconButton(
            icon: const Icon(Icons.download),
            onPressed: exportToCSV,
            tooltip: 'Export Detailed Records',
          ),
        ],
      ),
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Subject and Section Selection
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Select Subject and Section',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                          ),
                          const SizedBox(height: 16),
                          // Subject Dropdown
                          DropdownButtonFormField<Map<String, dynamic>>(
                            value: selectedSubject,
                            isExpanded: true,
                            decoration: InputDecoration(
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                              ),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
                            ),
                            hint: const Text('Select Subject'),
                            items: subjects.map((subject) {
                              return DropdownMenuItem<Map<String, dynamic>>(
                                value: subject,
                                child: Text(
                                  subject['name'],
                                  overflow: TextOverflow.ellipsis,
                                ),
                              );
                            }).toList(),
                            onChanged: (subject) {
                              setState(() {
                                selectedSubject = subject;
                                selectedSection = null;
                                attendanceRecords.clear();
                              });
                              if (subject != null) {
                                fetchSections(subject['id']);
                              }
                            },
                          ),
                          const SizedBox(height: 16),
                          // Section Dropdown
                          if (selectedSubject != null)
                            DropdownButtonFormField<Map<String, dynamic>>(
                              value: selectedSection,
                              isExpanded: true,
                              decoration: InputDecoration(
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
                              ),
                              hint: const Text('Select Section'),
                              items: sections.map((section) {
                                return DropdownMenuItem<Map<String, dynamic>>(
                                  value: section,
                                  child: Text(
                                    section['name'],
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                );
                              }).toList(),
                              onChanged: (section) {
                                setState(() {
                                  selectedSection = section;
                                });
                                if (section != null) {
                                  fetchAttendanceRecords();
                                }
                              },
                            ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  // Date Selection
                  if (selectedSection != null)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Checkbox(
                                  value: _isDateRange,
                                  onChanged: (value) {
                                    setState(() {
                                      _isDateRange = value ?? false;
                                      if (!_isDateRange && _endDate.isAfter(_startDate)) {
                                        _endDate = _startDate;
                                      }
                                    });
                                    fetchAttendanceRecords();
                                  },
                                ),
                                const Text('Select Date Range'),
                              ],
                            ),
                            const SizedBox(height: 8),
                            // Start Date
                            ListTile(
                              leading: const Icon(Icons.calendar_today),
                              title: const Text('Start Date'),
                              subtitle: Text(DateFormat('MMMM d, yyyy').format(_startDate)),
                              trailing: IconButton(
                                icon: const Icon(Icons.edit),
                                onPressed: () => _selectDate(context, true),
                              ),
                            ),
                            // End Date (only shown if date range is selected)
                            if (_isDateRange)
                              ListTile(
                                leading: const Icon(Icons.calendar_today),
                                title: const Text('End Date'),
                                subtitle: Text(DateFormat('MMMM d, yyyy').format(_endDate)),
                                trailing: IconButton(
                                  icon: const Icon(Icons.edit),
                                  onPressed: () => _selectDate(context, false),
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  const SizedBox(height: 16),
                  // Attendance Records
                  if (selectedSection != null)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'Attendance Records',
                                  style: Theme.of(context).textTheme.titleMedium,
                                ),
                                if (attendanceRecords.isNotEmpty)
                                  Text(
                                    'Total: ${attendanceRecords.length}',
                                    style: Theme.of(context).textTheme.bodySmall,
                                  ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            if (attendanceRecords.isEmpty)
                              const Center(
                                child: Padding(
                                  padding: EdgeInsets.all(16),
                                  child: Text('No attendance records found'),
                                ),
                              )
                            else
                              ListView.builder(
                                shrinkWrap: true,
                                physics: const NeverScrollableScrollPhysics(),
                                itemCount: attendanceRecords.length,
                                itemBuilder: (context, index) {
                                  final record = attendanceRecords[index];
                                  return Padding(
                                    padding: const EdgeInsets.symmetric(vertical: 8),
                                    child: ListTile(
                                      leading: const Icon(Icons.person),
                                      title: Text(record['student_name']),
                                      subtitle: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text('Date: ${record['date']}'),
                                          Text('Time: ${record['time']}'),
                                        ],
                                      ),
                                      trailing: Chip(
                                        label: Text(
                                          record['status'],
                                          style: const TextStyle(color: Colors.white),
                                        ),
                                        backgroundColor: record['status'] == 'Present'
                                            ? Colors.green
                                            : Colors.red,
                                      ),
                                    ),
                                  );
                                },
                              ),
                          ],
                        ),
                      ),
                    ),
                ],
              ),
            ),
    );
  }
}