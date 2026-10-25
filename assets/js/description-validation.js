(function (root) {
	function isDescriptionLengthValid(value) {
		const length = Array.from(String(value || '').trim()).length;
		return length >= 10 && length <= 2000;
	}

	root.isDescriptionLengthValid = isDescriptionLengthValid;
	if (typeof module !== 'undefined' && module.exports) {
		module.exports = { isDescriptionLengthValid };
	}
})(typeof window !== 'undefined' ? window : globalThis);