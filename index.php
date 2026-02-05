<?php
// --- ส่วนของ PHP API สำหรับดึงข้อมูล ---
if (isset($_GET['action']) && $_GET['action'] == 'search') {
    header('Content-Type: application/json; charset=utf-8');
    require_once 'condb.php';

    $id_card = $_GET['id_card'] ?? '';

    // SQL ดึงข้อมูลจาก tax_records และ tax_reports
    $sql = "SELECT r.prefix, r.first_name, r.last_name, r.amount_paid, r.tax_withheld, 
                   p.new_file_name, p.report_month 
            FROM tax_records r 
            LEFT JOIN tax_reports p ON r.id_card = p.id_card 
            WHERE r.id_card = :id_card
            ORDER BY p.report_month ASC"; // เรียงตามเดือน

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_card' => $id_card]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $thai_months = [
        1 => "มกราคม",
        2 => "กุมภาพันธ์",
        3 => "มีนาคม",
        4 => "เมษายน",
        5 => "พฤษภาคม",
        6 => "มิถุนายน",
        7 => "กรกฎาคม",
        8 => "สิงหาคม",
        9 => "กันยายน",
        10 => "ตุลาคม",
        11 => "พฤศจิกายน",
        12 => "ธันวาคม"
    ];

    $data_list = [];
    foreach ($results as $row) {
        if (!empty($row['new_file_name'])) {
            // แยกส่วนชื่อไฟล์ด้วยเครื่องหมายขีด (-)
            $parts = explode('-', $row['new_file_name']);

            // ตามโครงสร้าง: เลขบัตร(0) - วันที่(1) - เดือน(2) - ปี(3)
            $day = isset($parts[1]) ? (int)$parts[1] : '';
            $month_num = isset($parts[2]) ? (int)$parts[2] : 0;

            // สร้างข้อความแสดงผล เช่น "วันที่ 15 เดือนมกราคม"
            $row['date_display'] = ($day ? "วันที่ " . $day . " " : "") . "เดือน" . ($thai_months[$month_num] ?? "ไม่ระบุเดือน");

            // ส่งชื่อไฟล์ไปให้ view_pdf.php จัดการประทับลายเซ็น
            $row['file_url'] = "view_pdf.php?file=" . $row['new_file_name'];
            $data_list[] = $row;
        }
    }
    echo json_encode(['status' => 'success', 'results' => $data_list, 'personal' => $results[0] ?? null]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบค้นหาข้อมูลผู้เสียภาษี</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('images/bg.jpg');
            /* ปรับ Path ตามจริง */
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
            position: relative;
            font-family: 'Sarabun', sans-serif;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            /* ดรอปสีพื้นหลังลง */
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            /* พื้นหลังมัว */
            z-index: -1;
        }

        .search-container {
            max-width: 900px;
            width: 95%;
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            margin: 20px auto;
        }

        .info-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            text-align: left;
        }

        .info-header {
            font-size: 1.3rem;
            color: #0056b3;
            font-weight: 700;
            border-bottom: 2px solid #f1f3f5;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .pdf-list-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            transition: 0.3s;
        }

        .pdf-list-item:hover {
            background: #f1f3f5;
            transform: translateX(5px);
        }

        .info-label {
            color: #6c757d;
            font-size: 0.85rem;
            display: block;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #212529;
        }
    </style>
</head>

<body>

    <div class="search-container text-center">
        <h2 class="mb-4 fw-bold">ระบบค้นหาข้อมูลหนังสือรับรองหักภาษี ณ ที่จ่าย (ภ.ง.ด.3)</h2>
        <div class="row g-3 align-items-end justify-content-center text-start">
            <div class="col-md-8">
                <label class="form-label fw-bold">เลขบัตรประชาชน 13 หลัก:</label>
                <input type="text" id="id_card" class="form-control form-control-lg" placeholder="กรอกเลขบัตรประชาชน" maxlength="13">
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-lg w-100" onclick="doSearch()">ค้นหาข้อมูล</button>
                    <button class="btn btn-outline-secondary btn-lg w-50" onclick="location.reload()">รีเซ็ต</button>
                </div>
            </div>
        </div>

        <div id="result_area">
            <p class="text-muted mt-5">กรุณาระบุเลขบัตรประชาชนเพื่อดูรายละเอียด</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function doSearch() {
            const idCard = document.getElementById('id_card').value;
            if (idCard.length !== 13) {
                Swal.fire('แจ้งเตือน', 'กรุณากรอกเลขบัตรให้ครบ 13 หลัก', 'warning');
                return;
            }
            document.getElementById('result_area').innerHTML = '<div class="mt-5 spinner-border text-primary"></div>';

            fetch(`index.php?action=search&id_card=${idCard}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.personal) {
                        renderResult(data);
                    } else {
                        document.getElementById('result_area').innerHTML = '<p class="mt-5 text-danger fw-bold">ไม่พบข้อมูลในระบบ</p>';
                    }
                });
        }

        function renderResult(data) {
            const personal = data.personal;
            const files = data.results;
            const fullName = `${personal.prefix}${personal.first_name} ${personal.last_name}`;

            let html = `
    <div class="info-card shadow-sm">
        <div class="info-header">รายละเอียดข้อมูลผู้เสียภาษี</div>
        <div class="row mb-4">
            <div class="col-md-4"><span class="info-label">ชื่อ-นามสกุล</span><span class="info-value">${fullName}</span></div>
            <div class="col-md-4"><span class="info-label">รายได้รวมสะสม</span><span class="info-value">${Number(personal.amount_paid).toLocaleString()} บาท</span></div>
            <div class="col-md-4"><span class="info-label">ภาษีที่หักรวมสะสม</span><span class="info-value text-danger">${Number(personal.tax_withheld).toLocaleString()} บาท</span></div>
        </div>
        
        <h6 class="fw-bold mt-4 mb-3">รายการเอกสาร PDF (ประทับลายเซ็นอัตโนมัติ):</h6>
        <div class="pdf-list-container">`;

            if (files.length > 0) {
                files.forEach(item => {
                    // แก้ไขบรรทัดนี้: ให้เรียกไปที่ view_pdf.php แทนการเปิดไฟล์ตรงๆ
                    const viewUrl = `view_pdf.php?file=${encodeURIComponent(item.new_file_name)}`;

                    html += `
            <div class="pdf-list-item d-flex justify-content-between align-items-center p-3 border rounded mb-2">
                <div>
                    <span class="fw-bold text-dark">${item.date_display}</span>
                    <div class="text-muted small">ไฟล์ต้นฉบับ: ${item.new_file_name}</div>
                </div>
                <button class="btn btn-warning text-white fw-bold" onclick="window.open('${viewUrl}', '_blank')">
                    📄 เปิดดูเอกสาร
                </button>
            </div>`;
                });
            } else {
                html += '<p class="text-center text-muted">--- ไม่พบไฟล์เอกสาร PDF ---</p>';
            }

            html += `</div></div>`;
            document.getElementById('result_area').innerHTML = html;
        }
    </script>

</body>

</html>