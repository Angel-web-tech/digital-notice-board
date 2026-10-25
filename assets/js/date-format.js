(function (root) {
	const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

	function formatDisplayDate(value, includeTime) {
		const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?$/);
		if (!match) return '';
		const day = match[3];
		const formatted = day + ' ' + months[Number(match[2]) - 1] + ' ' + match[1];
		if (!includeTime || match[4] === undefined) return formatted;
		const hour = Number(match[4]);
		return formatted + ', ' + (hour % 12 || 12) + ':' + match[5] + ' ' + (hour >= 12 ? 'pm' : 'am');
	}

	function noticeCountdownLabel(days) {
		const remaining = Math.max(0, Number(days) || 0);
		return 'Ends in ' + remaining + (remaining === 1 ? ' day' : ' days');
	}

	root.formatDisplayDate = formatDisplayDate;
	root.noticeCountdownLabel = noticeCountdownLabel;
	if (typeof module !== 'undefined' && module.exports) module.exports = { formatDisplayDate, noticeCountdownLabel };
})(typeof window !== 'undefined' ? window : globalThis);