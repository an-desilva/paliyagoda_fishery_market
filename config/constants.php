<?php
// config/constants.php
// Constants and App Configuration for Peliyagoda Wholesale Fish Market System

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Peliyagoda Bill Fish Trade System');
}

if (!defined('APP_VERSION')) {
    define('APP_VERSION', '2.0.4');
}

if (!defined('DEFAULT_COMMISSION_RATE')) {
    define('DEFAULT_COMMISSION_RATE', 6.00); // 6% Mudiyala Commission
}

// Sri Lankan Fishery Harbors
const SRI_LANKA_HARBORS = [
    'Beruwala',
    'Galle',
    'Negombo',
    'Trincomalee',
    'Mirissa',
    'Kalpitiya',
    'Tangalle',
    'Dondra',
    'Oluvil',
    'Jaffna/Karainagar'
];

// Pelagic Fish Species List (Peliyagoda Market Major Trade Items)
const FISH_SPECIES = [
    'Yellowfin Tuna (කෙලවල්ලා)' => 'Yellowfin Tuna',
    'Sailfish (මලින්)' => 'Sailfish',
    'Marlin (කොප්පරා)' => 'Marlin',
    'Swordfish (තලපත්)' => 'Swordfish',
    'Skipjack Tuna (බලයා)' => 'Skipjack Tuna',
    'Narrow-barred Spanish Mackerel (තෝරා)' => 'Spanish Mackerel',
    'Bigeye Tuna (ලොකු ඇස් කෙලවල්ලා)' => 'Bigeye Tuna'
];

// Meat Grades
const MEAT_GRADES = [
    'A_export'  => 'Grade A (Export / Sashimi Quality)',
    'B_local'   => 'Grade B (Local Supermarket & Hotel)',
    'C_canning' => 'Grade C (Local Market / Canning)'
];

// Payment Modes
const PAYMENT_MODES = [
    'cash'   => 'Cash Payment (තනි මුදලින්)',
    'credit' => 'Credit Account (ණය පොතට)'
];
