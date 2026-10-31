<?php

namespace Database\Seeders;

use App\Models\Affiliate;
use App\Models\KomisiLedger;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class AffiliateSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tour Leader Utama (Arminareka Perdana)
        $tl1 = Affiliate::updateOrCreate(
            ['code' => 'TL-001'],
            [
                'name'                       => 'Ustadz Ahmad Fauzi',
                'phone'                      => '081234567890',
                'agency_name'                => 'Arminareka Perdana Umroh',
                'commission_rate_percentage' => 7.50, // Rate dinamis 7.5%
                'bank_name'                  => 'BCA Syariah',
                'bank_account_number'        => '0987654321',
                'bank_account_name'          => 'Ahmad Fauzi',
                'status'                     => 'active',
                'notes'                      => 'Tour leader rombongan Jawa Barat, rutin membawa 2-3 bus per bulan.',
            ]
        );

        // 2. Tour Leader Kedua (Khalifah Tour)
        $tl2 = Affiliate::updateOrCreate(
            ['code' => 'TL-002'],
            [
                'name'                       => 'H. Ridwan Kamiludin',
                'phone'                      => '082198765432',
                'agency_name'                => 'Khalifah Tour & Travel',
                'commission_rate_percentage' => 5.00, // Rate dinamis 5.0%
                'bank_name'                  => 'Bank Syariah Indonesia (BSI)',
                'bank_account_number'        => '7123456789',
                'bank_account_name'          => 'Ridwan Kamiludin',
                'status'                     => 'active',
                'notes'                      => 'Mitra B2B spesialis oleh-oleh kurma ajwa dan sukari VIP.',
            ]
        );

        // 3. Tour Leader Ketiga (Maktour Travel)
        $tl3 = Affiliate::updateOrCreate(
            ['code' => 'TL-003'],
            [
                'name'                       => 'Ustadzah Siti Maryam',
                'phone'                      => '085712348765',
                'agency_name'                => 'Maktour Umrah & Hajj',
                'commission_rate_percentage' => 10.00, // Rate dinamis 10.0%
                'bank_name'                  => 'Bank Mandiri',
                'bank_account_number'        => '1330012345678',
                'bank_account_name'          => 'Siti Maryam',
                'status'                     => 'active',
                'notes'                      => 'Grup jamaah pengajian majelis taklim.',
            ]
        );

        // Buat sample ledger jika belum ada
        if (KomisiLedger::where('affiliate_id', $tl1->id)->count() === 0) {
            // Contoh komisi masuk (Credit)
            KomisiLedger::create([
                'affiliate_id'               => $tl1->id,
                'type'                       => 'credit',
                'transaction_amount'         => 12500000,
                'commission_rate_percentage' => 7.50,
                'amount'                     => 937500,
                'status'                     => 'approved',
                'description'                => 'Komisi 7.5% belanja rombongan Bus 1 (50 Pax)',
                'notes'                      => 'Pembelian kurma ajwa, sukari, dan air zamzam',
                'created_at'                 => now()->subDays(3),
            ]);

            KomisiLedger::create([
                'affiliate_id'               => $tl1->id,
                'type'                       => 'credit',
                'transaction_amount'         => 8000000,
                'commission_rate_percentage' => 7.50,
                'amount'                     => 600000,
                'status'                     => 'approved',
                'description'                => 'Komisi 7.5% belanja rombongan Bus 2 (45 Pax)',
                'notes'                      => 'Paket oleh-oleh kurma medjool dan cokelat kerikil',
                'created_at'                 => now()->subDays(1),
            ]);

            // Contoh pencairan komisi (Debit)
            KomisiLedger::create([
                'affiliate_id'               => $tl1->id,
                'type'                       => 'debit',
                'transaction_amount'         => 0,
                'commission_rate_percentage' => 0,
                'amount'                     => 500000,
                'status'                     => 'paid',
                'reference_no'               => 'PO-20261025-BCAS',
                'description'                => 'Pencairan komisi ke BCA Syariah 0987654321',
                'notes'                      => 'Transfer berhasil diselesaikan oleh admin',
                'created_at'                 => now()->subDay(),
            ]);
        }
    }
}

