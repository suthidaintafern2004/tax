<?php
// view_pdf.php
require_once 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;

if (isset($_GET['file'])) {
    // กำหนด Path ของไฟล์ต้นฉบับและรูปลายเซ็น
    $fileName = basename($_GET['file']);
    $filePath = 'processed_PDFs/' . $fileName;
    $signaturePath = 'images/wm.png';

    if (file_exists($filePath)) {
        try {
            $pdf = new Fpdi();

            // อ่านไฟล์ PDF ต้นฉบับ
            $pageCount = $pdf->setSourceFile($filePath);
            $templateId = $pdf->importPage(1); // ดึงหน้า 1
            $size = $pdf->getTemplateSize($templateId);

            // สร้างหน้า PDF ใหม่ตามขนาดเดิม
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // --- ส่วนการวางลายเซ็น ---
            // ปรับค่า x (ซ้าย-ขวา) และ y (บน-ลง) ตามต้องการ (หน่วยเป็นมิลลิเมตร)
            $x = 113;
            $y = 220;
            $width = 70;

            if (file_exists($signaturePath)) {
                $pdf->Image($signaturePath, $x, $y, $width);
            }

            // ส่งออกไฟล์ไปยัง Browser
            header('Content-Type: application/pdf');
            $pdf->Output('I', 'signed_' . $fileName);
            exit;
        } catch (Exception $e) {
            die("เกิดข้อผิดพลาด: " . $e->getMessage());
        }
    } else {
        die("ไม่พบไฟล์เอกสารต้นฉบับในโฟลเดอร์ processed_PDFs");
    }
} else {
    die("ไม่ระบุชื่อไฟล์");
}
