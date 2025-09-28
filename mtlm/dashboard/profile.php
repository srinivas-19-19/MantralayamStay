<?php
require '../db_connect.php';

// --- Security Check & Fetch Owner Data ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'owner') { header("Location: ../login_owner.php"); exit(); }
$owner_id = $_SESSION['user_id'];
// Add a full status check for extra security on every page
$stmt_status_check = $conn->prepare("SELECT status FROM owners WHERE id = ?");
$stmt_status_check->bind_param("i", $owner_id);
$stmt_status_check->execute();
$owner_status_result = $stmt_status_check->get_result()->fetch_assoc();
if (!$owner_status_result || $owner_status_result['status'] !== 'approved') {
    session_destroy();
    header("Location: ../login_owner.php?error=not_approved");
    exit();
}
$stmt_status_check->close();


// --- Handle Personal Details Update ---
$update_message = '';
$update_message_type = 'error';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
    $full_name = $_POST['full_name'];
    $mobile_number = $_POST['mobile_number'];
    $bank_name = $_POST['bank_name'];
    $account_number = $_POST['account_number'];
    $ifsc_code = $_POST['ifsc_code'];
    $upi_id = $_POST['upi_id'];

    $stmt_update = $conn->prepare("UPDATE owners SET full_name=?, mobile_number=?, bank_name=?, account_number=?, ifsc_code=?, upi_id=? WHERE id=?");
    $stmt_update->bind_param("ssssssi", $full_name, $mobile_number, $bank_name, $account_number, $ifsc_code, $upi_id, $owner_id);
    if ($stmt_update->execute()) {
        $update_message = "Details updated successfully!";
        $update_message_type = 'success';
    } else {
        $update_message = "Error updating details. Please try again.";
    }
    $stmt_update->close();
}

// Fetch the latest owner data to display in the form
$stmt = $conn->prepare("SELECT * FROM owners WHERE id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$page = 'profile'; // Set the active page for the header
include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <h1 class="h2">Personal & Payment Details</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <?php if($update_message): ?>
            <div class="alert alert-<?php echo $update_message_type; ?>">
                <?php echo $update_message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="profile.php">
            <input type="hidden" name="update_details" value="1">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($owner['full_name']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="mobile_number" class="form-label">Mobile Number</label>
                    <input type="text" id="mobile_number" name="mobile_number" class="form-control" value="<?php echo htmlspecialchars($owner['mobile_number'] ?? ''); ?>">
                </div>
            </div>
            <hr>
            <h5 class="mb-3">Payment Information</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="bank_name" class="form-label">Bank Name</label>
                    <input type="text" id="bank_name" name="bank_name" class="form-control" value="<?php echo htmlspecialchars($owner['bank_name'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="account_number" class="form-label">Bank Account Number</label>
                    <input type="text" id="account_number" name="account_number" class="form-control" value="<?php echo htmlspecialchars($owner['account_number'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="ifsc_code" class="form-label">IFSC Code</label>
                    <input type="text" id="ifsc_code" name="ifsc_code" class="form-control" value="<?php echo htmlspecialchars($owner['ifsc_code'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="upi_id" class="form-label">UPI ID</label>
                    <input type="text" id="upi_id" name="upi_id" class="form-control" value="<?php echo htmlspecialchars($owner['upi_id'] ?? ''); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Details</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
