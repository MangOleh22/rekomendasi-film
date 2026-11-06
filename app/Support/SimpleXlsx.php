<?php

namespace App\Support;

use ZipArchive;

/**
 * Penulis file Excel (.xlsx) sederhana tanpa library tambahan (cukup ext-zip).
 * Baris pertama = header (tebal, berlatar, terkunci saat digulir, ada filter).
 */
class SimpleXlsx
{
    /** @param array<int, array<int, scalar|null>> $rows */
    public static function write(string $path, array $rows, string $sheetName = 'Dataset'): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::esc($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . (count($rows) > 0 ? '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">\'' . self::esc($sheetName) . '\'!$A$1:$' . self::col(count($rows[0]) - 1) . '$' . count($rows) . '</definedName></definedNames>' : '')
            . '</workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        // Gaya: 0 = biasa, 1 = header (tebal, putih di atas merah, tengah)
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE11D48"/></patternFill></fill></fills>'
            . '<borders count="2"><border/><border><left style="thin"><color rgb="FFD4D4D8"/></left><right style="thin"><color rgb="FFD4D4D8"/></right>'
            . '<top style="thin"><color rgb="FFD4D4D8"/></top><bottom style="thin"><color rgb="FFD4D4D8"/></bottom></border></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2"><xf borderId="1" applyBorder="1"/>'
            . '<xf fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs>'
            . '</styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet($rows));
        $zip->close();
    }

    private static function sheet(array $rows): string
    {
        $widths = [];
        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $v) {
                $widths[$i] = max($widths[$i] ?? 0, mb_strlen((string) $v));
            }
        }
        $cols = '';
        foreach ($widths as $i => $w) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . min(max($w + 3, 8), 50) . '" customWidth="1"/>';
        }

        $data = '';
        foreach ($rows as $r => $row) {
            $n = $r + 1;
            $data .= '<row r="' . $n . '"' . ($r === 0 ? ' ht="22" customHeight="1"' : '') . '>';
            foreach (array_values($row) as $i => $v) {
                $ref = self::col($i) . $n;
                $style = $r === 0 ? ' s="1"' : '';
                if (is_int($v) || is_float($v)) {
                    $data .= '<c r="' . $ref . '"' . $style . '><v>' . $v . '</v></c>';
                } else {
                    $data .= '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t>' . self::esc((string) $v) . '</t></is></c>';
                }
            }
            $data .= '</row>';
        }

        $last = count($rows) ? self::col(count($rows[0]) - 1) . count($rows) : 'A1';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . ($cols ? '<cols>' . $cols . '</cols>' : '')
            . '<sheetData>' . $data . '</sheetData>'
            . (count($rows) ? '<autoFilter ref="A1:' . $last . '"/>' : '')
            . '</worksheet>';
    }

    /** 0 -> A, 25 -> Z, 26 -> AA */
    public static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }

        return $s;
    }

    private static function esc(string $s): string
    {
        // buang karakter kontrol yang tidak sah di XML
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
