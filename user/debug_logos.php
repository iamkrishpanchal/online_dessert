<?php
// debug logo resolution within user directory context using real vendor data
$conn = mysqli_connect('localhost','root','','online_dessert');
$res = mysqli_query($conn, "SELECT vendor_id, shop_name, logo_path FROM tbl_vendors WHERE logo_path IS NOT NULL LIMIT 5");
while ($vend = mysqli_fetch_assoc($res)) {
    $raw = $vend['logo_path'];
    $logo = 'https://via.placeholder.com/80?text=Shop';
    echo "\nVendor {$vend['vendor_id']} ({$vend['shop_name']}) raw=\"$raw\"\n";
    if (!empty($raw)) {
        if (preg_match('#^(https?:)?//#i', $raw)) {
            $logo = $raw;
        } else {
            $candidates = [
                ['path'=>$raw,'url'=>$raw],
                ['path'=>'uploads/'.$raw,'url'=>'uploads/'.$raw],
                ['path'=>'uploads/vendors/'.$raw,'url'=>'uploads/vendors/'.$raw],
                ['path'=>'../uploads/'.$raw,'url'=>'../uploads/'.$raw],
                ['path'=>'../uploads/vendors/'.$raw,'url'=>'..//uploads/vendors/'.$raw],
                ['path'=>'../admin/uploads/vendors/'.$raw,'url'=>'../admin/uploads/vendors/'.$raw],
                ['path'=>'../'.$raw,'url'=>'../'.$raw],
            ];
            if (strpos($raw, 'admin/') === 0) {
                $alt = substr($raw, strlen('admin/'));
                $candidates[] = ['path'=>$alt,'url'=>$alt];
                $candidates[] = ['path'=>'../'.$alt,'url'=>'../'.$alt];
            }
            foreach($candidates as $cand) {
                $exists1 = file_exists(__DIR__.'/'.$cand['path']);
                $exists2 = file_exists(__DIR__.'/../'.$cand['path']);
                echo "  checking {$cand['path']} -> __DIR__/".__DIR__.'/'.$cand['path'].' exists='.($exists1?'yes':'no')."; ../".__DIR__.'/../'.$cand['path'].' exists='.($exists2?'yes':'no')."\n";
                if ($exists1 || $exists2) {
                    $logo = $cand['url'];
                    echo "    selected {$cand['url']}\n";
                    break;
                }
            }
            if ($logo === 'https://via.placeholder.com/80?text=Shop' && !empty($raw)) {
                $logo = $raw;
            }
        }
    }
    echo "Resolved logo: $logo\n";
}
