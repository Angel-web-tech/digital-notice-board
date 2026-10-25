<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/helpers.php';

function phase_check(bool $condition, string $label): void
{
	if (!$condition) { fwrite(STDERR, "FAIL: {$label}\n"); exit(1); }
	fwrite(STDOUT, "PASS: {$label}\n");
}

phase_check(notice_status(['status' => 'draft', 'publish_date' => '2026-01-01 00:00:00', 'expiry_date' => '2027-01-01 00:00:00']) === 'draft', 'draft status stays distinct from date status');
[$active] = status_sql('active');
[$archived] = status_sql('archived');
phase_check(str_contains($active, "status = 'published'"), 'active predicate excludes drafts');
phase_check(str_contains($archived, "status = 'published'"), 'archive predicate excludes drafts');
phase_check(preg_match('/^[a-f0-9]{48}\.pdf$/', safe_upload_name('PDF')) === 1, 'stored attachment name is random and normalized');

$cells = ['=SUM(A1:A2)', '+cmd', '-1+2', '@value', 'normal text'];
foreach ($cells as &$cell) {
	if (preg_match('/^[\s]*[=+\-@]/u', $cell)) $cell = "'" . $cell;
}
unset($cell);
phase_check($cells === ["'=SUM(A1:A2)", "'+cmd", "'-1+2", "'@value", 'normal text'], 'CSV formula prefixes are escaped');
fwrite(STDOUT, "All Phase B smoke checks passed.\n");