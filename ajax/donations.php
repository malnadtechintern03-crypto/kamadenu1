<?php
/**
 * Kamadenu Goushala Platform - AJAX Donation Handler
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
}

verify_csrf_or_die();

try {
    $amount = (float)($_POST['amount'] ?? 0);
    $donorName = sanitize_input($_POST['donor_name'] ?? '');
    $donorEmail = sanitize_input($_POST['donor_email'] ?? '');
    $donorPhone = sanitize_input($_POST['donor_phone'] ?? '');
    $donorPan = sanitize_input($_POST['donor_pan'] ?? '');
    $purpose = sanitize_input($_POST['purpose'] ?? 'General Gau Seva');
    $cowId = !empty($_POST['cow_id']) ? (int)$_POST['cow_id'] : null;
    $sevaId = !empty($_POST['seva_id']) ? (int)$_POST['seva_id'] : null;

    if ($amount < 50) {
        json_response(['success' => false, 'message' => 'Minimum donation is ₹ 50.'], 400);
    }
    if (empty($donorName)) {
        json_response(['success' => false, 'message' => 'Please provide your full name.'], 400);
    }
    if (empty($donorEmail) || !filter_var($donorEmail, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Please provide a valid email address.'], 400);
    }

    $donationNumber = Donation::create([
        'seva_program_id' => $sevaId,
        'cow_id'          => $cowId,
        'donor_name'      => $donorName,
        'donor_email'     => $donorEmail,
        'donor_phone'     => $donorPhone,
        'donor_pan'       => $donorPan,
        'amount'          => $amount,
        'purpose'         => $purpose
    ]);

    // Record server-side simulated payment success
    $paymentRef = 'PAY-' . strtoupper(bin2hex(random_bytes(5)));
    Donation::markSuccess($donationNumber, $paymentRef, 'razorpay');

    json_response([
        'success'         => true,
        'donation_number' => $donationNumber,
        'receipt_url'     => BASE_URL . '/receipt.php?num=' . urlencode($donationNumber),
        'message'         => 'Donation recorded successfully. Thank you for your blessed contribution!'
    ]);

} catch (Throwable $t) {
    error_log('AJAX Donation Error: ' . $t->getMessage());
    json_response(['success' => false, 'message' => 'Failed to record donation.'], 500);
}
