async function doSearch() {
    const id = document.getElementById('id_card').value;
    const mon = document.getElementById('month').value;
    const area = document.getElementById('result_area');

    if (id.length !== 13) {
        // เปลี่ยน alert เป็น SweetAlert
        Swal.fire({
            icon: 'warning',
            title: 'แจ้งเตือน',
            text: 'กรุณากรอกเลขบัตรประชาชนให้ครบ 13 หลัก',
            confirmButtonText: 'ตกลง'
        });
        return;
    }

    area.innerHTML = '<p class="no-data">กำลังค้นหา...</p>';

    try {
        // ส่งไปที่ fetch_data.php
        const response = await fetch(`fetch_data.php?id_card=${id}&month=${mon}`);
        const data = await response.json();

        area.innerHTML = '';

        if (data.length > 0) {
            // เรียงลำดับข้อมูลตามเดือน (มกราคม -> ธันวาคม)
            data.sort((a, b) => {
                const getMonth = (fname) => {
                    const p = fname.split('-');
                    let m = parseInt(p[2]);
                    if (isNaN(m)) m = parseInt(p[3]); // กรณีชื่อไฟล์มี --
                    return (isNaN(m) ? 0 : m);
                };
                return getMonth(a.new_file_name) - getMonth(b.new_file_name);
            });

            const thaiMonths = [
                '', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 
                'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
            ];
            data.forEach(item => {
                const parts = item.new_file_name.split('-');
                let m = parseInt(parts[2]);
                if (isNaN(m)) m = parseInt(parts[3]);
                const monthName = thaiMonths[m] || 'ไม่ระบุ';

                area.innerHTML += `
                <div class="result-box">
                    <div class="result-item"><span class="label">ชื่อ-นามสกุล:</span> ${item.prefix}${item.first_name} ${item.last_name}</div>
                    <div class="result-item"><span class="label">เดือน:</span> ${monthName}</div>
                    <div class="result-item"><span class="label">รายได้รวม:</span> ${Number(item.amount_paid).toLocaleString()} บาท</div>
                    <div class="result-item"><span class="label">ภาษีที่หักรวม:</span> ${Number(item.tax_withheld).toLocaleString()} บาท</div>
                    <div style="font-size: 12px; color: #888; margin-top: 5px;">ไฟล์: ${item.new_file_name}</div>
                    <a href="${item.file_url}" target="_blank" class="btn-pdf">📄 เปิดดูเอกสาร PDF</a>
                </div>
            `;
            });
        } else {
            area.innerHTML = '<p class="no-data">❌ ไม่พบข้อมูลสำหรับเงื่อนไขที่ระบุ</p>';
        }
    } catch (e) {
        area.innerHTML = '<p class="no-data">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
    }
}

function doReset() {
    document.getElementById('id_card').value = '';
    document.getElementById('month').value = 'all';
    document.getElementById('result_area').innerHTML = '<p class="no-data">กรุณากรอกเลขบัตรและกดปุ่มค้นหา</p>';
}