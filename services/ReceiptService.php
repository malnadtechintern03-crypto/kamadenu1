<?php
/**
 * Kamadenu Goushala Platform - Receipt Service
 * Generates and fetches 80G donation receipts and digital adoption certificates
 */

declare(strict_types=1);

class ReceiptService {
    /**
     * Fetch complete receipt record by receipt number or donation number.
     */
    public static function getReceipt(string $identifier): ?array {
        $sql = "
            SELECT r.*, d.donation_number, d.donor_email, d.donor_phone, d.donor_address,
                   d.donor_city, d.donor_state, d.donor_pincode, d.purpose, d.created_at AS donation_date,
                   p.gateway, p.gateway_payment_id, p.transaction_id,
                   c.name AS cow_name, c.cow_code,
                   sp.title AS seva_program_title
            FROM receipts r
            LEFT JOIN donations d ON r.reference_type = 'donation' AND r.reference_id = d.id
            LEFT JOIN payments p ON r.payment_id = p.id
            LEFT JOIN cows c ON d.cow_id = c.id
            LEFT JOIN seva_programs sp ON d.seva_program_id = sp.id
            WHERE r.receipt_number = ? OR d.donation_number = ?
        ";
        return Database::fetchOne($sql, [$identifier, $identifier]);
    }
}
