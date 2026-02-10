<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for referral code in URL
if (isset($_GET['ref'])) {
    $ref_code = sanitize_input($_GET['ref']);
    
    // Look up affiliate
    $affiliate = fetch_one("SELECT * FROM affiliates WHERE code = ? AND status = 'active' AND is_approved = 1", [$ref_code]);
    
    if ($affiliate) {
        // Store in session valid for 30 days (marketing standard)
        $_SESSION['affiliate'] = [
            'id' => $affiliate['id'],
            'code' => $affiliate['code'],
            'discount' => $affiliate['discount_percentage'],
            'commission' => $affiliate['commission_rate']
        ];
        
        // Optional: Set a cookie for 30 days
        setcookie('driyum_ref', $ref_code, time() + (86400 * 30), "/");
    }
} else if (!isset($_SESSION['affiliate']) && isset($_COOKIE['driyum_ref'])) {
    // Restore from cookie if session expired but cookie exists
    $ref_code = sanitize_input($_COOKIE['driyum_ref']);
    $affiliate = fetch_one("SELECT * FROM affiliates WHERE code = ? AND status = 'active' AND is_approved = 1", [$ref_code]);
    
    if ($affiliate) {
        $_SESSION['affiliate'] = [
            'id' => $affiliate['id'],
            'code' => $affiliate['code'],
            'discount' => $affiliate['discount_percentage'],
            'commission' => $affiliate['commission_rate']
        ];
    }
}
?>


