<?php
// Test the status functions
function normalizeVendorStatus($rawStatus) {
    if ($rawStatus === null || $rawStatus === '') {
        return 'active';
    }
    
    $status = strtolower(trim($rawStatus));
    
    if ($status === '') {
        return 'active';
    }

    if (in_array($status, ['1', 'true', 'yes', 'active', 'approved'], true)) {
        return 'active';
    }

    if (in_array($status, ['0', 'false', 'no', 'inactive', 'rejected'], true)) {
        return 'inactive';
    }

    return $status;
}

function getEffectiveVendorStatus($vendorRow) {
    $rawStatus = $vendorRow['status'] ?? null;
    
    if ($rawStatus === null || $rawStatus === '') {
        return 'active';
    }
    
    $status = normalizeVendorStatus($rawStatus);

    if ($status === 'inactive') {
        return 'inactive';
    }

    if ($status === 'pending') {
        return 'pending';
    }

    if ($status === 'suspended') {
        return 'suspended';
    }

    return 'active';
}

// Test cases
$testCases = ['pending', 'active', 'inactive', 'suspended', 'approved', 'rejected', '1', '0'];

foreach ($testCases as $status) {
    $normalized = normalizeVendorStatus($status);
    $effective = getEffectiveVendorStatus(['status' => $status]);
    $canLogin = ($effective === 'active') ? 'YES' : 'NO';
    echo "Status: '$status' -> Normalized: '$normalized' -> Effective: '$effective' -> Can Login: $canLogin\n";
}
?>