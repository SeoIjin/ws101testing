<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: sign-in.php");
    exit();
}

// Handle logout from header
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_destroy();
    header("Location: sign-in.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "users";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['logout'])) {
    $requesttype = $_POST['requesttype'];
    $description = trim($_POST['description']);
    $user_id = $_SESSION['user_id'];

    // Validation
    if (empty($requesttype) || empty($description)) {
        $error = "All fields are required.";
    } else {
        // Generate unique ticket ID
        $year = date('Y');
        $stmt = $conn->prepare("SELECT COUNT(*) FROM requests WHERE YEAR(submitted_at) = ?");
        $stmt->bind_param("i", $year);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $ticket_id = 'BHR-' . $year . '-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);

        // Get user email for the name field
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($user_email);
        $stmt->fetch();
        $stmt->close();

        // Insert request
        $stmt = $conn->prepare("INSERT INTO requests (ticket_id, user_id, fullname, contact, requesttype, description) VALUES (?, ?, ?, ?, ?, ?)");
        $contact = 'N/A';
        $fullname = $user_email;
        $stmt->bind_param("sissss", $ticket_id, $user_id, $fullname, $contact, $requesttype, $description);
        if ($stmt->execute()) {
            $success = "Request submitted successfully! Your Ticket ID is: <strong>$ticket_id</strong>. Use it to track your request.";
        } else {
            $error = "Error submitting request. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch user's requests for notification dropdown
$user_id = $_SESSION['user_id'];
$requests_query = "SELECT r.*, 
    (SELECT COUNT(*) FROM request_updates WHERE request_id = r.id) as update_count
    FROM requests r 
    WHERE r.user_id = ? 
    ORDER BY r.submitted_at DESC";
$stmt = $conn->prepare($requests_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_requests = $stmt->get_result();
$requests = [];
while ($row = $user_requests->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();

// Fetch all updates for user's requests
$all_updates = [];
foreach ($requests as $request) {
    $updates_query = "SELECT * FROM request_updates WHERE request_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($updates_query);
    $stmt->bind_param("i", $request['id']);
    $stmt->execute();
    $updates_result = $stmt->get_result();
    while ($update = $updates_result->fetch_assoc()) {
        $update['request_type'] = $request['requesttype'];
        $update['ticket_id'] = $request['ticket_id'];
        $all_updates[] = $update;
    }
    $stmt->close();
}

// Sort all updates by created_at
usort($all_updates, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Submit Request – eBCsH</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { 
      margin: 0; 
      padding: 0; 
      box-sizing: border-box; 
      font-family: 'Poppins', sans-serif; 
    }
    
    body { 
      min-height: 100vh;
      background: #DAF1DE;
      color: #333; 
    }

    /* Header */
    header { 
      background: white;
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      padding: 1rem 2rem; 
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .header-left { 
      display: flex; 
      align-items: center;
      cursor: pointer;
      background: transparent;
      border: none;
      text-decoration: none;
      color: inherit;
      transition: opacity 0.3s;
    }

    .header-left:hover {
      opacity: 0.8;
    }
    
    .header-left img { 
      height: 50px;
      width: 50px;
      margin-right: 10px;
      border-radius: 50%;
    }
    
    .header-title-wrap {
      text-align: left;
    }
    
    .header-title-wrap .title { 
      font-size: 16px; 
      font-weight: 500;
      color: #000;
    }
    
    .header-title-wrap .subtitle {
      font-size: 14px;
      opacity: 0.7;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* Notification Bell */
    .notification-wrapper {
      position: relative;
    }

    .notification-btn {
      background: #16a34a;
      color: white;
      border: none;
      padding: 10px;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      transition: background 0.3s;
    }

    .notification-btn:hover {
      background: #15803d;
    }

    .notification-badge {
      position: absolute;
      top: -4px;
      right: -4px;
      background: #ef4444;
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: 600;
    }

    .notification-dropdown {
      display: none;
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
      width: 400px;
      max-height: 500px;
      overflow-y: auto;
      z-index: 1000;
    }

    .notification-dropdown.show {
      display: block;
    }

    .notification-header {
      padding: 16px;
      border-bottom: 1px solid #e5e7eb;
      position: sticky;
      top: 0;
      background: white;
      z-index: 10;
    }

    .notification-header h3 {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
      color: #14532d;
    }

    .notification-empty {
      padding: 32px;
      text-align: center;
      color: #6b7280;
    }

    .notification-item {
      padding: 16px;
      border-bottom: 1px solid #f3f4f6;
      cursor: pointer;
      transition: background 0.2s;
    }

    .notification-item:hover {
      background: #f9fafb;
    }

    .notification-item-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 8px;
    }

    .notification-status-time {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 4px;
    }

    .status-badge {
      padding: 2px 6px;
      border-radius: 3px;
      font-size: 11px;
      font-weight: 600;
      color: white;
    }

    .notification-time {
      font-size: 11px;
      color: #9ca3af;
    }

    .notification-type {
      font-size: 13px;
      font-weight: 600;
      color: #14532d;
      margin-bottom: 4px;
    }

    .notification-ticket {
      font-size: 11px;
      color: #6b7280;
      margin-bottom: 6px;
    }

    .notification-message {
      font-size: 12px;
      color: #4b5563;
      line-height: 1.4;
    }

    .notification-updater {
      font-size: 11px;
      color: #9ca3af;
      margin-top: 8px;
      font-style: italic;
    }

    .notification-footer {
      padding: 12px;
      border-top: 1px solid #e5e7eb;
      background: #f9fafb;
    }

    .notification-footer button {
      width: 100%;
      background: #16a34a;
      color: white;
      border: none;
      padding: 8px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }

    .notification-footer button:hover {
      background: #15803d;
    }

    .profile-btn {
      background: #16a34a;
      color: white;
      border: none;
      padding: 10px;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.3s;
      text-decoration: none;
    }

    .profile-btn:hover {
      background: #15803d;
    }
    
    .logout-btn { 
      background: #FD7E7E;
      color: #fff; 
      border: none; 
      padding: 10px 20px; 
      border-radius: 6px; 
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .logout-btn:hover {
      background: #fc6b6b;
    }
    
    .logout-btn i { 
      margin-right: 6px; 
    }

    /* Main Content */
    .main-container {
      max-width: 1000px;
      margin: 28px auto;
      padding: 0 16px;
    }

    .form-container { 
      max-width: 700px;
      margin: 20px auto;
      background: white;
      padding: 32px; 
      border-radius: 16px; 
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .form-title { 
      font-size: 28px; 
      font-weight: 600; 
      color: #2e7d3a; 
      margin-bottom: 6px; 
    }
    
    .form-description { 
      color: #556; 
      margin-bottom: 20px;
      line-height: 1.6;
      font-size: 15px;
    }
    
    form { 
      display: flex; 
      flex-direction: column; 
      gap: 20px; 
    }
    
    .form-group { 
      display: flex; 
      flex-direction: column; 
    }
    
    .form-label { 
      font-weight: 500; 
      color: #249c3b; 
      margin-bottom: 6px;
      font-size: 14px;
    }
    
    select, textarea { 
      padding: 10px; 
      border-radius: 8px; 
      border: 1px solid #e6efe6; 
      background: #fbfffb; 
      outline: none;
      font-size: 14px;
      font-family: 'Poppins', sans-serif;
    }

    select:focus, textarea:focus {
      border-color: #249c3b;
    }
    
    textarea { 
      min-height: 120px;
      resize: vertical;
    }

    select {
      cursor: pointer;
    }
    
    .submit-button { 
      background: #00B050;
      color: #fff; 
      padding: 12px; 
      border-radius: 8px; 
      border: none; 
      font-weight: 600;
      font-size: 15px;
      cursor: pointer;
      transition: background 0.3s;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .submit-button:hover {
      background: #009944;
    }
    
    .message { 
      padding: 12px; 
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
    }
    
    .success { 
      background: #e6f8ec; 
      color: #1b6b2b;
    }
    
    .error { 
      background: #fff0f0; 
      color: #942020;
    }

    /* Footer */
    footer {
      background: white;
      border-top: 1px solid #dcfce7;
      margin-top: 48px;
    }

    .footer-content {
      max-width: 1000px;
      margin: 0 auto;
      padding: 32px 24px;
    }

    .footer-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 32px;
      margin-bottom: 24px;
    }

    .footer-section {
      text-align: center;
    }

    .footer-section h3 {
      font-size: 18px;
      color: #14532d;
      font-weight: 600;
      margin-bottom: 16px;
    }

    .footer-section-content {
      display: inline-block;
      text-align: left;
    }

    .footer-item {
      margin-bottom: 12px;
      font-size: 15px;
    }

    .footer-item-label {
      color: #15803d;
      font-weight: 500;
      margin-bottom: 4px;
    }

    .footer-item-value {
      color: #166534;
      font-size: 14px;
    }

    .footer-hospital {
      margin-bottom: 12px;
    }

    .footer-hospital-name {
      color: #15803d;
      font-weight: 500;
    }

    .footer-hospital-phone {
      color: #166534;
      font-size: 14px;
    }

    .footer-copyright {
      border-top: 1px solid #dcfce7;
      padding-top: 24px;
      text-align: center;
      color: #15803d;
      font-size: 15px;
    }

    .footer-copyright p {
      margin-bottom: 8px;
    }

    @media (max-width: 768px) { 
      header {
        padding: 12px 16px;
      }

      .header-left img {
        height: 40px;
        width: 40px;
      }

      .notification-dropdown {
        width: calc(100vw - 32px);
        right: -140px;
      }

      .main-container {
        padding: 0 12px;
      }

      .form-container {
        padding: 24px;
      }

      .form-title {
        font-size: 24px;
      }

      .footer-grid {
        grid-template-columns: 1fr;
        gap: 24px;
      }
    }
  </style>
</head>
<body>
  <header>
    <a href="homepage.php" class="header-left">
      <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRTDCuh4kIpAtR-QmjA1kTjE_8-HSd8LSt3Gw&s" alt="Logo">
      <div class="header-title-wrap">
        <div class="title">Barangay 170</div>
        <div class="subtitle">Community Portal</div>
      </div>
    </a>
    <div class="header-right">
      <!-- Notification Bell -->
      <div class="notification-wrapper">
        <button class="notification-btn" onclick="toggleNotifications()">
          <i class="fas fa-bell"></i>
          <?php if (count($requests) > 0): ?>
            <span class="notification-badge"><?php echo count($requests); ?></span>
          <?php endif; ?>
        </button>

        <div class="notification-dropdown" id="notificationDropdown">
          <div class="notification-header">
            <h3>Your Request Progress</h3>
          </div>

          <?php if (count($requests) === 0): ?>
            <div class="notification-empty">
              <p>No requests yet. Submit your first request!</p>
            </div>
          <?php elseif (count($all_updates) === 0): ?>
            <div class="notification-empty">
              <p>No updates yet. Check back later!</p>
            </div>
          <?php else: ?>
            <?php foreach ($all_updates as $update): ?>
              <div class="notification-item" onclick="window.location.href='trackreq.php'">
                <div class="notification-item-header">
                  <div style="flex: 1;">
                    <div class="notification-status-time">
                      <span class="status-badge" style="background: <?php echo getStatusColor($update['status']); ?>">
                        <?php echo htmlspecialchars($update['status']); ?>
                      </span>
                      <span class="notification-time">
                        <?php echo formatTimestamp($update['timestamp']); ?>
                      </span>
                    </div>
                    <div class="notification-type">
                      <?php echo htmlspecialchars($update['request_type']); ?>
                    </div>
                    <div class="notification-ticket">
                      <?php echo htmlspecialchars($update['ticket_id']); ?>
                    </div>
                  </div>
                </div>
                <p class="notification-message">
                  <?php echo htmlspecialchars($update['message']); ?>
                </p>
                <div class="notification-updater">
                  Updated by: <?php echo htmlspecialchars($update['updated_by']); ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <div class="notification-footer">
            <button onclick="window.location.href='trackreq.php'">
              View All Requests
            </button>
          </div>
        </div>
      </div>

      <!-- Profile Button -->
      <a href="profile.php" class="profile-btn">
        <i class="fas fa-user-circle"></i>
      </a>

      <!-- Logout Button -->
      <form method="POST" action="submitreq.php" style="display:inline; margin: 0;">
        <button type="submit" name="logout" class="logout-btn">
          <i class="fas fa-sign-out-alt"></i> Logout
        </button>
      </form>
    </div>
  </header>

  <div class="main-container">
    <div class="form-container">
      <h1 class="form-title">Submit New Request</h1>
      <p class="form-description">
        Please provide detailed information about your request or concern. 
        The barangay will process it and notify you when ready.
      </p>

      <?php if ($success): ?>
        <div class="message success"><?php echo $success; ?></div>
      <?php elseif ($error): ?>
        <div class="message error"><?php echo $error; ?></div>
      <?php endif; ?>

      <form method="POST" action="submitreq.php">
        <div class="form-group">
          <label class="form-label" for="requesttype">Request Type</label>
          <select id="requesttype" name="requesttype" required>
            <option value="" disabled selected>Select the type of request</option>
            <option value="Barangay Blotter / Incident Report Copy">Barangay Blotter / Incident Report Copy</option>
            <option value="Barangay Business Clearance">Barangay Business Clearance</option>
            <option value="Barangay Certificate for Livelihood Program Application">Barangay Certificate for Livelihood Program Application</option>
            <option value="Barangay Certificate for Water/Electric Connection (Proof of Occupancy/Ownership)">Barangay Certificate for Water/Electric Connection (Proof of Occupancy/Ownership)</option>
            <option value="Barangay Certificate of Guardianship">Barangay Certificate of Guardianship</option>
            <option value="Barangay Certificate of Household Membership">Barangay Certificate of Household Membership</option>
            <option value="Barangay Certificate of No Derogatory Record">Barangay Certificate of No Derogatory Record</option>
            <option value="Barangay Certificate of No Objection (CNO)">Barangay Certificate of No Objection (CNO)</option>
            <option value="Barangay Certification for PWD">Barangay Certification for PWD</option>
            <option value="Barangay Certification for Solo Parent (Referral for DSWD)">Barangay Certification for Solo Parent (Referral for DSWD)</option>
            <option value="Barangay Clearance">Barangay Clearance</option>
            <option value="Barangay Clearance for Street Vending">Barangay Clearance for Street Vending</option>
            <option value="Barangay Construction / Renovation Permit">Barangay Construction / Renovation Permit</option>
            <option value="Barangay Endorsement Letter">Barangay Endorsement Letter</option>
            <option value="Barangay Event Permit (Sound Permit, Activity Permit)">Barangay Event Permit (Sound Permit, Activity Permit)</option>
            <option value="Barangay ID">Barangay ID</option>
            <option value="Certificate of Indigency">Certificate of Indigency</option>
            <option value="Certificate of Residency">Certificate of Residency</option>
            <option value="Clearance of No Objection">Clearance of No Objection</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="description">Detailed Description</label>
          <textarea id="description" name="description" placeholder="Provide details, symptoms, timeline, or assistance needed." required></textarea>
        </div>

        <button type="submit" class="submit-button">Submit</button>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <div class="footer-content">
      <div class="footer-grid">
        <!-- Barangay Health Office -->
        <div class="footer-section">
          <h3>🏢 Barangay Health Office</h3>
          <div class="footer-section-content">
            <div class="footer-item">
              <div class="footer-item-label">📍 Address</div>
              <div class="footer-item-value">Deparo, Caloocan City, Metro Manila</div>
            </div>
            <div class="footer-item">
              <div class="footer-item-label">📞 Hotline</div>
              <div class="footer-item-value">(02) 8123-4567</div>
            </div>
            <div class="footer-item">
              <div class="footer-item-label">📧 Email</div>
              <div class="footer-item-value">K1contrerascris@gmail.com</div>
            </div>
            <div class="footer-item">
              <div class="footer-item-label">🕐 Office Hours</div>
              <div class="footer-item-value">Mon-Fri, 8:00 AM - 5:00 PM</div>
            </div>
          </div>
        </div>

        <!-- Emergency Hotlines -->
        <div class="footer-section">
          <h3>📞 Emergency Hotlines</h3>
          <div class="footer-section-content">
            <div class="footer-item">
              <span class="footer-item-label" style="min-width: 80px; display: inline-block;">Police</span>
              <span class="footer-item-value">(02) 8426-4663</span>
            </div>
            <div class="footer-item">
              <span class="footer-item-label" style="min-width: 80px; display: inline-block;">BFP</span>
              <span class="footer-item-value">(02) 8245 0849</span>
            </div>
          </div>
        </div>

        <!-- Hospitals Near Barangay -->
        <div class="footer-section">
          <h3>🏥 Hospitals Near Barangay</h3>
          <div class="footer-section-content">
            <div class="footer-hospital">
              <div class="footer-hospital-name">Camarin Doctors Hospital</div>
              <div class="footer-hospital-phone">(02) 2-7004-2881</div>
            </div>
            <div class="footer-hospital">
              <div class="footer-hospital-name">Caloocan City North Medical</div>
              <div class="footer-hospital-phone">(02) 8288 7077</div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Copyright -->
      <div class="footer-copyright">
        <p>© 2025 Barangay 170, Deparo, Caloocan. All rights reserved.</p>
        <p>Electronic Barangay Certificate System for Health (eBCsH)</p>
      </div>
    </div>
  </footer>

  <script>
    function toggleNotifications() {
      const dropdown = document.getElementById('notificationDropdown');
      dropdown.classList.toggle('show');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const wrapper = document.querySelector('.notification-wrapper');
      const dropdown = document.getElementById('notificationDropdown');
      
      if (!wrapper.contains(event.target)) {
        dropdown.classList.remove('show');
      }
    });
  </script>
</body>
</html>

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'New': return '#3b82f6';
        case 'Under Review': return '#f59e0b';
        case 'In Progress': return '#8b5cf6';
        case 'Ready': return '#10b981';
        case 'Completed': return '#059669';
        case 'Rejected': return '#ef4444';
        default: return '#6b7280';
    }
}

function formatTimestamp($timestamp) {
    $date = strtotime($timestamp);
    $now = time();
    $diff = $now - $date;
    $diffMins = floor($diff / 60);
    $diffHours = floor($diff / 3600);
    $diffDays = floor($diff / 86400);

    if ($diffMins < 60) {
        return $diffMins === 0 ? 'Just now' : $diffMins . ' min' . ($diffMins > 1 ? 's' : '') . ' ago';
    } elseif ($diffHours < 24) {
        return $diffHours . ' hour' . ($diffHours > 1 ? 's' : '') . ' ago';
    } elseif ($diffDays < 7) {
        return $diffDays . ' day' . ($diffDays > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $date);
    }
}
?>