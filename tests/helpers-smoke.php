<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: {$message}\n");
}

check(date_default_timezone_get() === 'Asia/Kolkata', 'PHP timezone is Asia/Kolkata');
check(display_date('2026-10-22 15:30:00') === '22 Oct 2026', 'PHP display date uses DD Mon YYYY');
check(display_date('2026-10-22 15:30:00', true) === '22 Oct 2026, 3:30 pm', 'PHP date and time use DD Mon YYYY');
check(notice_countdown_label(2) === 'Ends in 2 days', 'expiring countdown uses plural label');
check(notice_countdown_label(1) === 'Ends in 1 day', 'expiring countdown uses singular label');
check(parse_notice_datetime('2026-10-24T15:30') instanceof DateTimeImmutable, 'valid datetime-local value parses');
check(parse_notice_datetime('2026-02-30T12:00') === null, 'invalid calendar date is rejected');
[$activeSql] = status_sql('active');
[$scheduledSql] = status_sql('scheduled');
[$expiredSql] = status_sql('expired');
check(str_contains($activeSql, 'publish_date <= NOW()') && str_contains($activeSql, 'expiry_date > NOW()'), 'active SQL predicate follows shared rule');
check(str_contains($scheduledSql, 'publish_date > NOW()'), 'scheduled SQL predicate follows shared rule');
check(str_contains($expiredSql, 'expiry_date <= NOW()'), 'expired SQL predicate follows shared rule');

$publishDate = new DateTimeImmutable('+1 day', new DateTimeZone(APP_TIMEZONE));
$expiryDate = $publishDate->modify('+1 day');
$validNotice = [
    'title' => 'Sample campus notice',
    'description' => 'This is a sufficiently long notice description.',
    'category' => 'Events',
    'department' => 'CSE',
    'priority' => 'normal',
    'publish_date' => $publishDate->format('Y-m-d\TH:i'),
    'expiry_date' => $expiryDate->format('Y-m-d\TH:i'),
];
check(validate_notice_input($validNotice) === [], 'valid notice passes server validation');
$invalidNotice = $validNotice;
$invalidNotice['category'] = 'Made up category';
$invalidNotice['expiry_date'] = $publishDate->modify('-1 day')->format('Y-m-d\TH:i');
$errors = validate_notice_input($invalidNotice);
check(isset($errors['category']) && isset($errors['expiry_date']), 'invalid category and wrong date range are rejected');

foreach ([
    5 => false,
    10 => true,
    600 => true,
    2001 => false,
] as $length => $expectedValid) {
    $descriptionCase = $validNotice;
    $descriptionCase['description'] = str_repeat('a', $length);
    $descriptionErrors = validate_notice_input($descriptionCase);
    check(isset($descriptionErrors['description']) !== $expectedValid, "server description length {$length} has expected validity");
}

$now = new DateTimeImmutable('2026-10-24 12:00:00', new DateTimeZone(APP_TIMEZONE));
$recentActive = ['publish_date' => '2026-10-24 01:00:00', 'expiry_date' => '2026-10-25 12:00:00'];
$recentScheduled = ['publish_date' => '2026-10-24 12:01:00', 'expiry_date' => '2026-10-25 12:00:00'];
$recentExpired = ['publish_date' => '2026-10-24 01:00:00', 'expiry_date' => '2026-10-24 11:59:00'];
$olderActive = ['publish_date' => '2026-10-23 11:59:00', 'expiry_date' => '2026-10-25 12:00:00'];
check(notice_is_new($recentActive, $now), 'active notice published within 24 hours is NEW');
check(!notice_is_new($recentScheduled, $now), 'scheduled notice is not NEW');
check(!notice_is_new($recentExpired, $now), 'expired notice is not NEW');
check(!notice_is_new($olderActive, $now), 'active notice published over 24 hours ago is not NEW');

fwrite(STDOUT, "All helper smoke checks passed.\n");