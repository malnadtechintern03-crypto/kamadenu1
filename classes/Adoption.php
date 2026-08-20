<?php
/**
 * Kamadenu Goushala Platform - Adoption Model & Digital Certificate Generator
 */

declare(strict_types=1);

class Adoption {
    /**
     * Create a new adoption record and return adoption & certificate numbers.
     */
    public static function create(array $data): array {
        $adoptionNumber = 'ADP-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $certificateNumber = 'KG-CERT-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));

        $monthlyAmount = (float)($data['monthly_amount'] ?? 3000.00);
        $durationMonths = (int)($data['duration_months'] ?? 1);
        $totalAmount = $monthlyAmount * $durationMonths;

        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$durationMonths} months"));

        $sql = "
            INSERT INTO adoptions (
                adoption_number, cow_id, adopter_name, adopter_email, adopter_phone,
                adopter_address, duration_months, monthly_amount, total_amount,
                start_date, end_date, certificate_number, certificate_issued_at,
                status, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'active', ?)
        ";

        Database::insert($sql, [
            $adoptionNumber,
            $data['cow_id'],
            $data['adopter_name'],
            $data['adopter_email'],
            $data['adopter_phone'],
            $data['adopter_address'] ?? null,
            $durationMonths,
            $monthlyAmount,
            $totalAmount,
            $startDate,
            $endDate,
            $certificateNumber,
            $data['notes'] ?? null
        ]);

        // Also record in sponsors table
        Database::insert("
            INSERT INTO sponsors (cow_id, sponsor_name, sponsor_email, sponsor_phone, amount, frequency, start_date, end_date, status)
            VALUES (?, ?, ?, ?, ?, 'monthly', ?, ?, 'active')
        ", [
            $data['cow_id'],
            $data['adopter_name'],
            $data['adopter_email'],
            $data['adopter_phone'],
            $monthlyAmount,
            $startDate,
            $endDate
        ]);

        return [
            'adoption_number'    => $adoptionNumber,
            'certificate_number' => $certificateNumber,
            'total_amount'       => $totalAmount
        ];
    }

    /**
     * Fetch adoption details with cow information by certificate number or adoption number.
     */
    public static function findByCertificate(string $certNumber): ?array {
        $sql = "
            SELECT a.*, c.name AS cow_name, c.cow_code, c.gender, c.health_status, c.main_image,
                   b.name AS breed_name
            FROM adoptions a
            JOIN cows c ON a.cow_id = c.id
            JOIN cow_breeds b ON c.breed_id = b.id
            WHERE a.certificate_number = ? OR a.adoption_number = ?
        ";
        return Database::fetchOne($sql, [$certNumber, $certNumber]);
    }
}
