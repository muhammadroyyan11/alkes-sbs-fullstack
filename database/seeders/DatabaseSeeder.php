<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceive;
use App\Models\PurchaseReceiveItem;
use App\Models\StockMutation;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
            MenuSeeder::class,
        ]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@alkessbs.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'phone' => '082231311799',
                'address' => 'Jl. Raya Kesehatan No. 1, Jakarta',
                'is_active' => true,
                'role' => 'admin',
            ]
        );

        // ── 5 Admin Users ──
        $admins = [
            ['name' => 'Budi Santoso', 'email' => 'budi@alkessbs.com', 'phone' => '081234567890', 'address' => 'Jl. Sudirman No. 10, Jakarta', 'role' => 'admin'],
            ['name' => 'Siti Rahayu', 'email' => 'siti@alkessbs.com', 'phone' => '081234567891', 'address' => 'Jl. Thamrin No. 20, Jakarta', 'role' => 'admin'],
            ['name' => 'Andi Wijaya', 'email' => 'andi@alkessbs.com', 'phone' => '081234567892', 'address' => 'Jl. Gatot Subroto No. 30, Bandung', 'role' => 'admin'],
            ['name' => 'Maya Putri', 'email' => 'maya@alkessbs.com', 'phone' => '081234567893', 'address' => 'Jl. Asia Afrika No. 40, Bandung', 'role' => 'admin'],
            ['name' => 'Rizky Pratama', 'email' => 'rizky@alkessbs.com', 'phone' => '081234567894', 'address' => 'Jl. Pemuda No. 50, Surabaya', 'role' => 'admin'],
        ];

        foreach ($admins as $data) {
            $u = User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['password' => Hash::make('password'), 'is_active' => true])
            );
            $u->assignRole('admin');
        }

        // ── Warehouses ──
        Warehouse::firstOrCreate(['code' => 'GD01'], ['name' => 'Gudang Pusat', 'code' => 'GD01', 'address' => 'Jl. Industri No. 1, Jakarta', 'is_active' => true]);
        Warehouse::firstOrCreate(['code' => 'GD02'], ['name' => 'Gudang Bandung', 'code' => 'GD02', 'address' => 'Jl. Cibatu No. 5, Bandung', 'is_active' => true]);

        // ── 10 Suppliers ──
        $suppliers = [
            ['name' => 'PT Medika Jaya', 'contact_person' => 'Hendra Wijaya', 'phone' => '021-5551234', 'email' => 'hendra@medikajaya.co.id', 'address' => 'Jl. Kesehatan No. 10, Jakarta'],
            ['name' => 'PT Sehat Abadi', 'contact_person' => 'Linda Sari', 'phone' => '021-5552345', 'email' => 'linda@sehatabadi.co.id', 'address' => 'Jl. Damai No. 20, Jakarta'],
            ['name' => 'CV Alkes Berkah', 'contact_person' => 'Ahmad Fauzi', 'phone' => '022-7771234', 'email' => 'ahmad@alkesberkah.co.id', 'address' => 'Jl. Buah Batu No. 30, Bandung'],
            ['name' => 'PT Sinar Medika', 'contact_person' => 'Rina Melati', 'phone' => '021-5553456', 'email' => 'rina@sinarmedika.co.id', 'address' => 'Jl. Kramat Raya No. 40, Jakarta'],
            ['name' => 'PT Prima Alkes', 'contact_person' => 'Dedi Kurniawan', 'phone' => '031-8881234', 'email' => 'dedi@primaalkes.co.id', 'address' => 'Jl. Pemuda No. 50, Surabaya'],
            ['name' => 'CV Bina Sehat', 'contact_person' => 'Eka Putri', 'phone' => '021-5554567', 'email' => 'eka@binasehat.co.id', 'address' => 'Jl. Mangga Besar No. 60, Jakarta'],
            ['name' => 'PT Mitra Kesehatan', 'contact_person' => 'Fajar Nugroho', 'phone' => '022-7772345', 'email' => 'fajar@mitrakesehatan.co.id', 'address' => 'Jl. Dago No. 70, Bandung'],
            ['name' => 'PT Global Medika', 'contact_person' => 'Gita Salsabila', 'phone' => '021-5555678', 'email' => 'gita@globalmedika.co.id', 'address' => 'Jl. Rasuna Said No. 80, Jakarta'],
            ['name' => 'CV Sejahtera Alkes', 'contact_person' => 'Hadi Santoso', 'phone' => '031-8882345', 'email' => 'hadi@sejahteraalkes.co.id', 'address' => 'Jl. Basuki Rachmat No. 90, Surabaya'],
            ['name' => 'PT Dinamika Medika', 'contact_person' => 'Indra Permana', 'phone' => '021-5556789', 'email' => 'indra@dinamikamedika.co.id', 'address' => 'Jl. TB Simatupang No. 100, Jakarta'],
        ];

        $supplierModels = [];
        foreach ($suppliers as $s) {
            $supplierModels[] = Supplier::firstOrCreate(['name' => $s['name']], $s);
        }

        // ── 50 Products ──
        $productData = [
            ['name' => 'Stetoskop Littmann Classic III', 'sku' => 'PROD-001', 'price' => 3500000, 'stock' => 25, 'unit' => 'pcs', 'description' => 'Stetoskop profesional untuk diagnosa jantung dan paru-paru'],
            ['name' => 'Tensimeter Digital Omron HEM-7120', 'sku' => 'PROD-002', 'price' => 850000, 'stock' => 40, 'unit' => 'pcs', 'description' => 'Tensimeter digital otomatis dengan teknologi Intellisense'],
            ['name' => 'Termometer Infrared Frontal', 'sku' => 'PROD-003', 'price' => 275000, 'stock' => 60, 'unit' => 'pcs', 'description' => 'Termometer tanpa kontak untuk pengukuran suhu cepat'],
            ['name' => 'Pulse Oxymeter Fingertip', 'sku' => 'PROD-004', 'price' => 195000, 'stock' => 55, 'unit' => 'pcs', 'description' => 'Alat ukur saturasi oksigen dan detak jari'],
            ['name' => 'Glukometer Accu-Chek Guide', 'sku' => 'PROD-005', 'price' => 650000, 'stock' => 30, 'unit' => 'pcs', 'description' => 'Sistem monitoring gula darah akurat'],
            ['name' => 'Nebulizer Portable Omron CompAir', 'sku' => 'PROD-006', 'price' => 1200000, 'stock' => 15, 'unit' => 'pcs', 'description' => 'Nebulizer kompresor portabel untuk terapi pernapasan'],
            ['name' => 'Kursi Roda Standar', 'sku' => 'PROD-007', 'price' => 1800000, 'stock' => 10, 'unit' => 'pcs', 'description' => 'Kursi roda standar lipat dengan sandaran tangan'],
            ['name' => 'Tongkat Ketiga (Tripod Cane)', 'sku' => 'PROD-008', 'price' => 185000, 'stock' => 35, 'unit' => 'pcs', 'description' => 'Tongkat penyangga tiga kaki anti slip'],
            ['name' => 'Masker Medis 3 Ply (Box 50)', 'sku' => 'PROD-009', 'price' => 65000, 'stock' => 200, 'unit' => 'box', 'description' => 'Masker medis sekali pakai 3 lapis'],
            ['name' => 'Sarung Tangan Latex (Box 100)', 'sku' => 'PROD-010', 'price' => 85000, 'stock' => 150, 'unit' => 'box', 'description' => 'Sarung tangan medis lateks sekali pakai'],
            ['name' => 'Hand Sanitizer 500ml', 'sku' => 'PROD-011', 'price' => 35000, 'stock' => 300, 'unit' => 'botol', 'description' => 'Hand sanitizer antiseptik 70% alcohol'],
            ['name' => 'Cairan Desinfektan 1L', 'sku' => 'PROD-012', 'price' => 45000, 'stock' => 180, 'unit' => 'liter', 'description' => 'Cairan disinfektan untuk sterilisasi permukaan'],
            ['name' => 'Kursi Pijat Recliner', 'sku' => 'PROD-013', 'price' => 4500000, 'stock' => 5, 'unit' => 'pcs', 'description' => 'Kursi pijat elektrik dengan fungsi recliner'],
            ['name' => 'Tabung Oksigen 6L', 'sku' => 'PROD-014', 'price' => 2200000, 'stock' => 8, 'unit' => 'pcs', 'description' => 'Tabung oksigen medis 6 liter dengan regulator'],
            ['name' => 'Regulator Oksigen', 'sku' => 'PROD-015', 'price' => 450000, 'stock' => 12, 'unit' => 'pcs', 'description' => 'Regulator aliran oksigen dengan flowmeter'],
            ['name' => 'Infus Stand', 'sku' => 'PROD-016', 'price' => 385000, 'stock' => 18, 'unit' => 'pcs', 'description' => 'Stand infus portable dengan roda'],
            ['name' => 'Bed Pasien Manual', 'sku' => 'PROD-017', 'price' => 7500000, 'stock' => 3, 'unit' => 'pcs', 'description' => 'Tempat tidur pasien 3 engkol dengan rem'],
            ['name' => 'Meja Perawat Mobile', 'sku' => 'PROD-018', 'price' => 2800000, 'stock' => 7, 'unit' => 'pcs', 'description' => 'Meja perawat dengan 4 roda dan laci'],
            ['name' => 'Lampu Bed Exam 50W', 'sku' => 'PROD-019', 'price' => 1500000, 'stock' => 6, 'unit' => 'pcs', 'description' => 'Lampu pemeriksaan fleksibel 50 watt'],
            ['name' => 'Suction Pump Portable', 'sku' => 'PROD-020', 'price' => 3200000, 'stock' => 4, 'unit' => 'pcs', 'description' => 'Mesin hisap portable untuk drainase'],
            ['name' => 'Defibrillator Manual', 'sku' => 'PROD-021', 'price' => 25000000, 'stock' => 2, 'unit' => 'pcs', 'description' => 'Defibrillator manual untuk gawat darurat'],
            ['name' => 'ECG 12 Lead', 'sku' => 'PROD-022', 'price' => 18000000, 'stock' => 2, 'unit' => 'pcs', 'description' => 'Alat rekam jantung 12 lead digital'],
            ['name' => 'CTG Monitor', 'sku' => 'PROD-023', 'price' => 15000000, 'stock' => 3, 'unit' => 'pcs', 'description' => 'Monitor detak jantung janin'],
            ['name' => 'Ventilator Mekanik', 'sku' => 'PROD-024', 'price' => 50000000, 'stock' => 1, 'unit' => 'pcs', 'description' => 'Ventilator mekanik untuk ICU'],
            ['name' => 'Autoclave Portable 23L', 'sku' => 'PROD-025', 'price' => 4500000, 'stock' => 5, 'unit' => 'pcs', 'description' => 'Autoclave sterilisasi portabel 23 liter'],
            ['name' => 'Mikroskop Binokuler', 'sku' => 'PROD-026', 'price' => 8500000, 'stock' => 3, 'unit' => 'pcs', 'description' => 'Mikroskop binokuler untuk laboratorium'],
            ['name' => 'Centrifuge 4000RPM', 'sku' => 'PROD-027', 'price' => 5200000, 'stock' => 4, 'unit' => 'pcs', 'description' => 'Mesin sentrifuge laboratorium 4000 RPM'],
            ['name' => 'pH Meter Digital', 'sku' => 'PROD-028', 'price' => 1800000, 'stock' => 8, 'unit' => 'pcs', 'description' => 'Alat ukur pH digital portabel'],
            ['name' => 'Laringoskop Set', 'sku' => 'PROD-029', 'price' => 3500000, 'stock' => 6, 'unit' => 'set', 'description' => 'Set laringoskop dengan 4 blade'],
            ['name' => 'OT Set 18 pcs', 'sku' => 'PROD-030', 'price' => 2800000, 'stock' => 10, 'unit' => 'set', 'description' => 'Set instrumen operasi 18 pcs stainless'],
            ['name' => 'Surgical Gown (Box 20)', 'sku' => 'PROD-031', 'price' => 250000, 'stock' => 40, 'unit' => 'box', 'description' => 'Gaun bedah sekali pakai box 20 pcs'],
            ['name' => 'Shoe Cover (Box 100)', 'sku' => 'PROD-032', 'price' => 75000, 'stock' => 100, 'unit' => 'box', 'description' => 'Pelindung sepatu sekali pakai'],
            ['name' => 'Hair Cover (Box 100)', 'sku' => 'PROD-033', 'price' => 45000, 'stock' => 120, 'unit' => 'box', 'description' => 'Penutup rambut sekali pakai'],
            ['name' => 'Apron Plastic (Box 50)', 'sku' => 'PROD-034', 'price' => 55000, 'stock' => 80, 'unit' => 'box', 'description' => 'Celemek plastik sekali pakai'],
            ['name' => 'Wheelchair Electric', 'sku' => 'PROD-035', 'price' => 8500000, 'stock' => 3, 'unit' => 'pcs', 'description' => 'Kursi roda elektrik dengan remote'],
            ['name' => 'Walker Folding', 'sku' => 'PROD-036', 'price' => 350000, 'stock' => 20, 'unit' => 'pcs', 'description' => 'Alat bantu jalan lipat adjustable'],
            ['name' => 'Crutch Aluminium', 'sku' => 'PROD-037', 'price' => 175000, 'stock' => 25, 'unit' => 'pcs', 'description' => 'Kruk aluminium ringan adjustable'],
            ['name' => 'Corset Lumbal', 'sku' => 'PROD-038', 'price' => 225000, 'stock' => 15, 'unit' => 'pcs', 'description' => 'Korset penyangga punggung bawah'],
            ['name' => 'Neck Collar Hard', 'sku' => 'PROD-039', 'price' => 150000, 'stock' => 18, 'unit' => 'pcs', 'description' => 'Collar leher keras untuk imobilisasi'],
            ['name' => 'Arm Sling', 'sku' => 'PROD-040', 'price' => 45000, 'stock' => 30, 'unit' => 'pcs', 'description' => 'Sling tangan untuk penyangga lengan'],
            ['name' => 'Bandage Elastic (Roll)', 'sku' => 'PROD-041', 'price' => 25000, 'stock' => 200, 'unit' => 'roll', 'description' => 'Perban elastis untuk kompresi'],
            ['name' => 'Gauze Pad 10x10 (Box 100)', 'sku' => 'PROD-042', 'price' => 55000, 'stock' => 150, 'unit' => 'box', 'description' => 'Kassa steril 10x10 cm kotak 100'],
            ['name' => 'Plaster Hypoallergenic (Roll)', 'sku' => 'PROD-043', 'price' => 18000, 'stock' => 250, 'unit' => 'roll', 'description' => 'Plester hypoallergenic gulungan'],
            ['name' => 'Cotton Roll 500g', 'sku' => 'PROD-044', 'price' => 42000, 'stock' => 100, 'unit' => 'pcs', 'description' => 'Kapas gulungan 500 gram'],
            ['name' => 'Syringe 3ml (Box 100)', 'sku' => 'PROD-045', 'price' => 75000, 'stock' => 300, 'unit' => 'box', 'description' => 'Suntik disposable 3ml kotak 100'],
            ['name' => 'Syringe 5ml (Box 100)', 'sku' => 'PROD-046', 'price' => 85000, 'stock' => 280, 'unit' => 'box', 'description' => 'Suntik disposable 5ml kotak 100'],
            ['name' => 'IV Cannula 22G (Box 50)', 'sku' => 'PROD-047', 'price' => 125000, 'stock' => 90, 'unit' => 'box', 'description' => 'Infus canula 22G kotak 50 pcs'],
            ['name' => 'IV Tubing (Box 50)', 'sku' => 'PROD-048', 'price' => 150000, 'stock' => 85, 'unit' => 'box', 'description' => 'Selang infus kotak 50 pcs'],
            ['name' => 'Nasal Cannula (Box 20)', 'sku' => 'PROD-049', 'price' => 95000, 'stock' => 60, 'unit' => 'box', 'description' => 'Kanula hidung oksigen kotak 20 pcs'],
            ['name' => 'Oxygen Mask Adult (Box 20)', 'sku' => 'PROD-050', 'price' => 110000, 'stock' => 55, 'unit' => 'box', 'description' => 'Masker oksigen dewasa kotak 20 pcs'],
        ];

        $productModels = [];
        foreach ($productData as $pd) {
            $productModels[] = Product::firstOrCreate(['sku' => $pd['sku']], $pd);
        }

        // ── 30 Variants ──
        $variantData = [
            ['product_id' => $productModels[0]->id, 'name' => 'Classic III Tune', 'sku' => 'VAR-001', 'price' => 3750000, 'stock' => 10],
            ['product_id' => $productModels[0]->id, 'name' => 'Classic III Smoke', 'sku' => 'VAR-002', 'price' => 3500000, 'stock' => 15],
            ['product_id' => $productModels[1]->id, 'name' => 'HEM-7120 Black', 'sku' => 'VAR-003', 'price' => 850000, 'stock' => 20],
            ['product_id' => $productModels[1]->id, 'name' => 'HEM-7120 White', 'sku' => 'VAR-004', 'price' => 850000, 'stock' => 20],
            ['product_id' => $productModels[4]->id, 'name' => 'Accu-Chek Guide Me', 'sku' => 'VAR-005', 'price' => 550000, 'stock' => 15],
            ['product_id' => $productModels[4]->id, 'name' => 'Accu-Chek Guide USB', 'sku' => 'VAR-006', 'price' => 750000, 'stock' => 15],
            ['product_id' => $productModels[5]->id, 'name' => 'CompAir NE-C28P', 'sku' => 'VAR-007', 'price' => 1100000, 'stock' => 8],
            ['product_id' => $productModels[5]->id, 'name' => 'CompAir NE-C106 Plus', 'sku' => 'VAR-008', 'price' => 1350000, 'stock' => 7],
            ['product_id' => $productModels[6]->id, 'name' => 'Kursi Roda Lipat A', 'sku' => 'VAR-009', 'price' => 1600000, 'stock' => 5],
            ['product_id' => $productModels[6]->id, 'name' => 'Kursi Roda Lipat B', 'sku' => 'VAR-010', 'price' => 2000000, 'stock' => 5],
            ['product_id' => $productModels[8]->id, 'name' => 'Masker Medis Biru', 'sku' => 'VAR-011', 'price' => 60000, 'stock' => 100],
            ['product_id' => $productModels[8]->id, 'name' => 'Masker Medis Hijau', 'sku' => 'VAR-012', 'price' => 65000, 'stock' => 100],
            ['product_id' => $productModels[9]->id, 'name' => 'Sarung Tangan S', 'sku' => 'VAR-013', 'price' => 80000, 'stock' => 50],
            ['product_id' => $productModels[9]->id, 'name' => 'Sarung Tangan M', 'sku' => 'VAR-014', 'price' => 85000, 'stock' => 50],
            ['product_id' => $productModels[9]->id, 'name' => 'Sarung Tangan L', 'sku' => 'VAR-015', 'price' => 85000, 'stock' => 50],
            ['product_id' => $productModels[13]->id, 'name' => 'Tabung Oksigen 6L Blue', 'sku' => 'VAR-016', 'price' => 2200000, 'stock' => 4],
            ['product_id' => $productModels[13]->id, 'name' => 'Tabung Oksigen 6L Green', 'sku' => 'VAR-017', 'price' => 2200000, 'stock' => 4],
            ['product_id' => $productModels[24]->id, 'name' => 'Autoclave 23L Manual', 'sku' => 'VAR-018', 'price' => 4200000, 'stock' => 3],
            ['product_id' => $productModels[24]->id, 'name' => 'Autoclave 23L Digital', 'sku' => 'VAR-019', 'price' => 4800000, 'stock' => 2],
            ['product_id' => $productModels[29]->id, 'name' => 'OT Set Premium', 'sku' => 'VAR-020', 'price' => 3200000, 'stock' => 4],
            ['product_id' => $productModels[29]->id, 'name' => 'OT Set Standard', 'sku' => 'VAR-021', 'price' => 2500000, 'stock' => 6],
            ['product_id' => $productModels[34]->id, 'name' => 'Wheelchair Electric Std', 'sku' => 'VAR-022', 'price' => 8000000, 'stock' => 2],
            ['product_id' => $productModels[34]->id, 'name' => 'Wheelchair Electric Pro', 'sku' => 'VAR-023', 'price' => 9500000, 'stock' => 1],
            ['product_id' => $productModels[37]->id, 'name' => 'Corset Size M', 'sku' => 'VAR-024', 'price' => 200000, 'stock' => 8],
            ['product_id' => $productModels[37]->id, 'name' => 'Corset Size L', 'sku' => 'VAR-025', 'price' => 225000, 'stock' => 7],
            ['product_id' => $productModels[44]->id, 'name' => 'Syringe 3ml Dispenser', 'sku' => 'VAR-026', 'price' => 70000, 'stock' => 150],
            ['product_id' => $productModels[44]->id, 'name' => 'Syringe 3ml Luer Lock', 'sku' => 'VAR-027', 'price' => 80000, 'stock' => 150],
            ['product_id' => $productModels[46]->id, 'name' => 'IV Cannula 22G Blue', 'sku' => 'VAR-028', 'price' => 120000, 'stock' => 45],
            ['product_id' => $productModels[46]->id, 'name' => 'IV Cannula 20G Pink', 'sku' => 'VAR-029', 'price' => 130000, 'stock' => 45],
            ['product_id' => $productModels[49]->id, 'name' => 'Oxygen Mask Adult Clear', 'sku' => 'VAR-030', 'price' => 100000, 'stock' => 30],
        ];

        $variantModels = [];
        foreach ($variantData as $vd) {
            $variantModels[] = Variant::firstOrCreate(['sku' => $vd['sku']], $vd);
        }

        // ── Stock Records ──
        $warehouse1 = Warehouse::where('code', 'GD01')->first();
        $warehouse2 = Warehouse::where('code', 'GD02')->first();

        foreach ($productModels as $pm) {
            Stock::firstOrCreate(
                ['product_id' => $pm->id, 'variant_id' => null, 'warehouse_id' => $warehouse1->id],
                ['quantity' => $pm->stock]
            );
        }

        foreach ($variantModels as $vm) {
            Stock::firstOrCreate(
                ['product_id' => $vm->product_id, 'variant_id' => $vm->id, 'warehouse_id' => $warehouse1->id],
                ['quantity' => $vm->stock]
            );
        }

        // ── 5 Purchase Orders with items ──
        $statuses = ['draft', 'sent', 'received', 'draft', 'sent'];
        $poModels = [];
        for ($i = 0; $i < 5; $i++) {
            $po = PurchaseOrder::firstOrCreate(
                ['po_number' => 'PO-2026-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)],
                [
                    'supplier_id' => $supplierModels[$i]->id,
                    'user_id' => $admin->id,
                    'status' => $statuses[$i],
                    'total' => 0,
                    'notes' => 'Pesanan bulanan ' . ($i + 1),
                ]
            );
            $poModels[] = $po;
        }

        // Add items to POs
        $poItems = [
            ['purchase_order_id' => $poModels[0]->id, 'product_id' => $productModels[0]->id, 'variant_id' => $variantModels[0]->id, 'quantity' => 10, 'price' => 3500000, 'subtotal' => 35000000],
            ['purchase_order_id' => $poModels[0]->id, 'product_id' => $productModels[1]->id, 'variant_id' => $variantModels[2]->id, 'quantity' => 20, 'price' => 800000, 'subtotal' => 16000000],
            ['purchase_order_id' => $poModels[1]->id, 'product_id' => $productModels[8]->id, 'variant_id' => $variantModels[10]->id, 'quantity' => 100, 'price' => 55000, 'subtotal' => 5500000],
            ['purchase_order_id' => $poModels[1]->id, 'product_id' => $productModels[9]->id, 'variant_id' => $variantModels[12]->id, 'quantity' => 50, 'price' => 75000, 'subtotal' => 3750000],
            ['purchase_order_id' => $poModels[2]->id, 'product_id' => $productModels[4]->id, 'variant_id' => $variantModels[4]->id, 'quantity' => 15, 'price' => 600000, 'subtotal' => 9000000],
            ['purchase_order_id' => $poModels[3]->id, 'product_id' => $productModels[13]->id, 'variant_id' => $variantModels[15]->id, 'quantity' => 5, 'price' => 2100000, 'subtotal' => 10500000],
            ['purchase_order_id' => $poModels[4]->id, 'product_id' => $productModels[44]->id, 'variant_id' => $variantModels[25]->id, 'quantity' => 200, 'price' => 65000, 'subtotal' => 13000000],
            ['purchase_order_id' => $poModels[4]->id, 'product_id' => $productModels[46]->id, 'variant_id' => $variantModels[27]->id, 'quantity' => 50, 'price' => 115000, 'subtotal' => 5750000],
        ];

        foreach ($poItems as $poi) {
            PurchaseOrderItem::firstOrCreate(
                ['purchase_order_id' => $poi['purchase_order_id'], 'product_id' => $poi['product_id'], 'variant_id' => $poi['variant_id']],
                $poi
            );
        }

        // Update PO totals
        foreach ($poModels as $po) {
            $total = $po->items()->sum('subtotal');
            $po->update(['total' => $total]);
        }

        // ── 2 Purchase Receives ──
        $pr1 = PurchaseReceive::firstOrCreate(
            ['receive_number' => 'PR-2026-0001'],
            [
                'purchase_order_id' => $poModels[2]->id,
                'user_id' => $admin->id,
                'status' => 'received',
                'notes' => 'Penerimaan lengkap',
            ]
        );

        PurchaseReceiveItem::firstOrCreate(
            ['purchase_receive_id' => $pr1->id, 'product_id' => $productModels[4]->id, 'variant_id' => $variantModels[4]->id],
            ['quantity_received' => 15]
        );

        $pr2 = PurchaseReceive::firstOrCreate(
            ['receive_number' => 'PR-2026-0002'],
            [
                'purchase_order_id' => $poModels[1]->id,
                'user_id' => $admin->id,
                'status' => 'pending',
                'notes' => 'Menunggu konfirmasi',
            ]
        );

        PurchaseReceiveItem::firstOrCreate(
            ['purchase_receive_id' => $pr2->id, 'product_id' => $productModels[8]->id, 'variant_id' => $variantModels[10]->id],
            ['quantity_received' => 50]
        );

        // ── Stock Mutations ──
        foreach ($poModels as $po) {
            if (in_array($po->status, ['received'])) {
                foreach ($po->items as $item) {
                    $stock = Stock::where('product_id', $item->product_id)
                        ->where('variant_id', $item->variant_id)
                        ->where('warehouse_id', $warehouse1->id)
                        ->first();

                    if ($stock) {
                        StockMutation::firstOrCreate(
                            [
                                'stock_id' => $stock->id,
                                'type' => 'in',
                                'quantity' => $item->quantity,
                                'reference_type' => PurchaseOrder::class,
                                'reference_id' => $po->id,
                            ],
                            [
                                'note' => 'PO masuk: ' . $po->po_number,
                                'created_by' => $admin->id,
                            ]
                        );
                    }
                }
            }
        }

        $this->command->info('✅ Sample data berhasil di-seed!');
        $this->command->info('   - ' . count($admins) . ' admin users');
        $this->command->info('   - ' . count($productModels) . ' products');
        $this->command->info('   - ' . count($variantModels) . ' variants');
        $this->command->info('   - ' . count($supplierModels) . ' suppliers');
        $this->command->info('   - 5 purchase orders');
        $this->command->info('   - 2 purchase receives');
    }
}
