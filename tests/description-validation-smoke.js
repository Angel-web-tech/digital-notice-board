const assert = require('node:assert/strict');
const { isDescriptionLengthValid } = require('../assets/js/description-validation.js');
const { formatDisplayDate, noticeCountdownLabel } = require('../assets/js/date-format.js');

for (const [length, expected] of [[5, false], [10, true], [600, true], [2001, false]]) {
	assert.equal(isDescriptionLengthValid('a'.repeat(length)), expected, `${length} characters`);
}

assert.equal(isDescriptionLengthValid(`  ${'a'.repeat(10)}  `), true, 'trimmed 10-character value');
assert.equal(isDescriptionLengthValid('😀'.repeat(10)), true, 'Unicode code points match PHP mb_strlen');
assert.equal(formatDisplayDate('2026-10-22 15:30:00'), '22 Oct 2026', 'date-only display format');
assert.equal(formatDisplayDate('2026-10-22T15:30', true), '22 Oct 2026, 3:30 pm', 'date-time display format');
assert.equal(noticeCountdownLabel(2), 'Ends in 2 days', 'expiring countdown plural');
assert.equal(noticeCountdownLabel(1), 'Ends in 1 day', 'expiring countdown singular');
console.log('All client description validation checks passed.');