<?php
/**
 * Kamadenu Goushala Platform - Payment Service
 * Server-side payment signature verification and gateway gateway adapter
 */

declare(strict_types=1);

class PaymentService {
    /**
     * Verify Razorpay payment signature server-side.
     * Never trust client-side success without HMAC SHA256 verification.
     */
    public static function verifyRazorpaySignature(string $orderId, string $paymentId, string $signature): bool {
        $secret = get_setting('razorpay_key_secret', 'KamadenuSecretKeyTesting2026');
        if (empty($secret)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);
        return hash_equals($expectedSignature, $signature);
    }
}
