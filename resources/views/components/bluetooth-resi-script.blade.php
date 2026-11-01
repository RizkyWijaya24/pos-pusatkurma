<script>
    /**
     * Helper Direct Bluetooth Print untuk Resi Pengiriman & Surat Jalan Antar Cabang
     * Menggunakan Web Bluetooth API (WebBLE) & ESC/POS Command (58mm/80mm Thermal Printer)
     */
    window.printTransferResiBluetooth = async function(data) {
        if (!data) return;

        if (!navigator.bluetooth) {
            const msg = "Browser Anda belum mendukung Web Bluetooth. Silakan gunakan Google Chrome / Microsoft Edge di PC/Laptop dengan Bluetooth atau di HP Android.";
            if (typeof window.showToast === 'function') {
                window.showToast(msg, 'error');
            } else {
                alert(msg);
            }
            return;
        }

        try {
            if (typeof window.showToast === 'function') {
                window.showToast("Mencari printer thermal Bluetooth...", "info");
            }

            // 1. Scan Bluetooth Printers (BLE)
            const device = await navigator.bluetooth.requestDevice({
                filters: [
                    { namePrefix: 'Printer' },
                    { namePrefix: 'MTP' },
                    { namePrefix: 'PT-' },
                    { namePrefix: 'POS' },
                    { namePrefix: 'RPP' },
                    { namePrefix: 'RP' },
                    { namePrefix: 'MPT' },
                    { namePrefix: 'InnerPrinter' },
                    { namePrefix: 'Bluetooth' },
                    { namePrefix: 'Thermal' },
                    { namePrefix: 'ZJ' },
                    { namePrefix: 'XP' },
                    { namePrefix: '58' },
                    { namePrefix: '80' },
                    { namePrefix: 'BT' }
                ],
                optionalServices: [
                    '000018f0-0000-1000-8000-00805f9b34fb', // Standard BLE printer service UUID
                    '0000e914-0000-1000-8000-00805f9b34fb', // Custom BLE printer service UUID
                    '49535343-fe7d-4ae5-8fa9-9fafd205e455', // ISSC Transparent
                    '0000ffe0-0000-1000-8000-00805f9b34fb', // HM-10 / common BLE
                    '0000ae00-0000-1000-8000-00805f9b34fb'  // Rongta / Xprinter
                ]
            });

            if (typeof window.showToast === 'function') {
                window.showToast(`Menghubungkan ke ${device.name || 'Printer'}...`, "info");
            }

            // 2. Connect GATT Server
            const server = await device.gatt.connect();

            // 3. Find Primary Service
            let service;
            const candidateUuids = [
                '000018f0-0000-1000-8000-00805f9b34fb',
                '0000e914-0000-1000-8000-00805f9b34fb',
                '49535343-fe7d-4ae5-8fa9-9fafd205e455',
                '0000ffe0-0000-1000-8000-00805f9b34fb',
                '0000ae00-0000-1000-8000-00805f9b34fb'
            ];

            for (const uuid of candidateUuids) {
                try {
                    service = await server.getPrimaryService(uuid);
                    if (service) break;
                } catch (e) {}
            }

            if (!service) {
                const services = await server.getPrimaryServices();
                if (services && services.length > 0) {
                    service = services[0];
                } else {
                    throw new Error("Layanan cetak printer Bluetooth tidak ditemukan!");
                }
            }

            // 4. Get Write Characteristic
            const characteristics = await service.getCharacteristics();
            const writeCharacteristic = characteristics.find(c => c.properties.write || c.properties.writeWithoutResponse);
            if (!writeCharacteristic) {
                throw new Error("Karakteristik penulisan printer tidak ditemukan!");
            }

            // 5. Build ESC/POS Payload (Standard 32-character width for 58mm thermal printers)
            const ESC_INIT = '\x1b\x40';
            const ESC_ALIGN_CENTER = '\x1b\x61\x01';
            const ESC_ALIGN_LEFT = '\x1b\x61\x00';
            const ESC_BOLD_ON = '\x1b\x45\x01';
            const ESC_BOLD_OFF = '\x1b\x45\x00';
            const ESC_DOUBLE_SIZE = '\x1b\x21\x30';
            const ESC_NORMAL_SIZE = '\x1b\x21\x00';

            const pad2Col = (left, right, width = 32) => {
                left = String(left || '');
                right = String(right || '');
                const spaces = width - left.length - right.length;
                if (spaces >= 0) {
                    return left + ' '.repeat(spaces) + right + '\n';
                } else {
                    return left.substring(0, Math.max(0, width - right.length - 1)) + ' ' + right + '\n';
                }
            };

            let escpos = '';
            escpos += ESC_INIT;
            
            // Header
            escpos += ESC_ALIGN_CENTER;
            escpos += ESC_BOLD_ON + ESC_DOUBLE_SIZE + 'PUSAT KURMA\n' + ESC_NORMAL_SIZE;
            escpos += ESC_BOLD_ON + 'SURAT JALAN & RESI TRANSFER\n' + ESC_BOLD_OFF;
            escpos += 'Al Karim - Cianjur, Jabar\n';
            escpos += '================================\n';

            // Meta Info
            escpos += ESC_ALIGN_LEFT;
            escpos += `No. Resi  : ${data.code || '-'}\n`;
            escpos += `Tanggal   : ${data.created_at || '-'}\n`;
            escpos += `Status    : ${(data.status || 'PROSES').toUpperCase()}\n`;
            escpos += '--------------------------------\n';
            escpos += `PENGIRIM  : ${data.from_location || 'Gudang Pusat'}\n`;
            escpos += `TUJUAN    : ${data.to_location || '-'}\n`;
            if (data.sender_name) {
                escpos += `Petugas   : ${data.sender_name}\n`;
            }
            if (data.approver_name) {
                escpos += `Disetujui : ${data.approver_name}\n`;
            }
            escpos += '================================\n';
            
            // Items Section
            escpos += ESC_ALIGN_CENTER + ESC_BOLD_ON + 'DAFTAR BARANG YANG DIKIRIM' + ESC_BOLD_OFF + '\n';
            escpos += ESC_ALIGN_LEFT;
            escpos += '--------------------------------\n';

            let totalItems = 0;
            let totalQtySum = 0;

            if (Array.isArray(data.items) && data.items.length > 0) {
                data.items.forEach((it, idx) => {
                    totalItems++;
                    const qtyVal = parseFloat(it.qty) || 0;
                    totalQtySum += qtyVal;

                    escpos += ESC_BOLD_ON + `[${idx + 1}] ${it.name || 'Produk'}` + ESC_BOLD_OFF + '\n';
                    
                    let qtyDetail = `    Kirim : ${it.qty} ${it.unit || 'pcs'}`;
                    if (it.is_adjusted && it.requested_qty) {
                        qtyDetail += ` (Minta: ${it.requested_qty})`;
                    }
                    escpos += qtyDetail + '\n';
                });
            } else {
                escpos += '  (Tidak ada rincian item)\n';
            }

            escpos += '--------------------------------\n';
            escpos += pad2Col(`Total Item : ${totalItems} Produk`, '', 32);

            if (data.notes && data.notes !== '-' && data.notes.trim() !== '') {
                escpos += `Catatan:\n${data.notes}\n`;
            }
            escpos += '================================\n\n';

            // Signatures block
            escpos += ESC_ALIGN_CENTER;
            escpos += 'Tanda Tangan Serah Terima:\n\n';
            escpos += pad2Col(' Pengirim,', ' Penerima,', 32);
            escpos += '\n\n\n'; // Space for signature
            escpos += pad2Col('(          )', '(          )', 32);
            escpos += '\n';
            escpos += '*** RESI PENGIRIMAN VALID ***\n';
            escpos += 'Barang dicek saat serah terima\n\n\n\n\n'; // Feed lines

            // 6. Encode and send in chunks of 20 bytes
            const encoder = new TextEncoder();
            const dataBytes = encoder.encode(escpos);
            const chunkSize = 20;

            for (let i = 0; i < dataBytes.length; i += chunkSize) {
                const chunk = dataBytes.slice(i, i + chunkSize);
                if (writeCharacteristic.writeValueWithoutResponse) {
                    await writeCharacteristic.writeValueWithoutResponse(chunk);
                } else {
                    await writeCharacteristic.writeValue(chunk);
                }
                await new Promise(r => setTimeout(r, 20)); // Delay 20ms
            }

            if (typeof window.showToast === 'function') {
                window.showToast("Cetak Resi Pengiriman via Bluetooth Berhasil!", "success");
            } else {
                alert("Cetak Resi Pengiriman via Bluetooth Berhasil!");
            }
        } catch (error) {
            if (error.name === 'NotFoundError' || error.message.includes('User cancelled')) {
                // User cancelled printer selection dialog
                return;
            }
            console.error("Direct Bluetooth printing failed:", error);
            const errMsg = "Cetak Bluetooth gagal: " + (error.message || error);
            if (typeof window.showToast === 'function') {
                window.showToast(errMsg, "error");
            } else {
                alert(errMsg);
            }
        }
    };
</script>

