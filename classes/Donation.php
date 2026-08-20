<?php
/**
 * Kamadenu Goushala Platform - Donation Model & Processing
 */

declare(strict_types=1);

class Donation {
    /**
     * Create a new donation record.
     */
    public static function create(array $data): string {
        $donationNumber = 'DON-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $sql = "
            INSERT INTO donations (
                donation_number, seva_program_id, cow_id, donor_name, donor_email,
                donor_phone, donor_pan, donor_address, donor_city, donor_state,
                donor_pincode, amount, purpose, is_80g_claimed, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ";

        Database::insert($sql, [
            $donationNumber,
            $data['seva_program_id'] ?? null,
            $data['cow_id'] ?? null,
            $data['donor_name'],
            $data['donor_email'],
            $data['donor_phone'],
            $data['donor_pan'] ?? null,
            $data['donor_address'] ?? null,
            $data['donor_city'] ?? null,
            $data['donor_state'] ?? null,
            $data['donor_pincode'] ?? null,
            $data['amount'],
            $data['purpose'] ?? 'General Gau Seva',
            !empty($data['is_80g_claimed']) ? 1 : 0
        ]);

        return $donationNumber;
    }

    /**
     * Mark donation as successful and generate receipt.
     */
    public static function markSuccess(string $donationNumber, string $paymentId, string $gateway = 'razorpay'): bool {
        $donation = Database::fetchOne("SELECT * FROM donations WHERE donation_number = ?", [$donationNumber]);
        if (!$donation) {
            return false;
        }

        Database::beginTransaction();
        try {
            // Update donation status
            Database::execute("UPDATE donations SET status = 'success' WHERE id = ?", [$donation['id']]);

            // Record payment transaction
            $paySql = "
                INSERT INTO payments (
                    transaction_id, reference_type, reference_id, gateway,
                    gateway_payment_id, amount, status, paid_at
                ) VALUES (?, 'donation', ?, ?, ?, ?, 'captured', NOW())
            ";
            $paymentDbId = Database::insert($paySql, [
                'TXN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))),
                $donation['id'],
                $gateway,
                $paymentId,
                $donation['amount']
            ]);

            // Generate 80G Receipt Number
            $receiptNo = 'REC-80G-' . date('Y') . '-' . str_pad((string)$donation['id'], 5, '0', STR_PAD_LEFT);
            Database::insert("
                INSERT INTO receipts (receipt_number, reference_type, reference_id, payment_id, donor_name, donor_pan, amount, tax_exemption_80g)
                VALUES (?, 'donation', ?, ?, ?, ?, ?, 1)
            ", [
                $receiptNo,
                $donation['id'],
                $paymentDbId,
                $donation['donor_name'],
                $donation['donor_pan'],
                $donation['amount']
            ]);

            Database::commit();
            return true;
        } catch (Throwable $t) {
            Database::rollBack();
            error_log('Failed to complete donation success workflow: ' . $t->getMessage());
            return false;
        }
    }
}
