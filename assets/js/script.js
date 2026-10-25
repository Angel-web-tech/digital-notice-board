$(function () {
	const $form = $('#notice-filters');
	const $list = $('#notice-list');
	const $status = $('#notice-status');
	const $count = $('#result-count');
	const $loadMore = $('#load-more');
	let request = null;
	let delay = null;
	let page = 1;
	let total = 0;
	let loadingMore = false;

	function escapeHtml(value) {
		return String(value).replace(/[&<>"']/g, function (character) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
		});
	}

	function renderCard(notice, expiring) {
		const urgent = notice.priority === 'urgent' && notice.status === 'active';
		const formattedExpiry = escapeHtml(window.formatDisplayDate(notice.expiry_date));
		const expiryText = expiring ? window.noticeCountdownLabel(notice.expires_in_days) : (notice.status === 'expired' ? 'Expired ' + formattedExpiry : 'Expires ' + formattedExpiry + (notice.expires_in_days === 0 ? ' · today' : ' · in ' + notice.expires_in_days + ' days'));
		const $card = $('<article>').addClass('notice-card category-' + categorySlug(notice.category) + (notice.is_pinned ? ' is-pinned' : '')).attr('style', '--card-order:' + (window.noticeRenderIndex++ % 9));
		const $topline = $('<div>').addClass('card-topline').append(categoryIcon(notice.category)).append($('<span>').addClass('chip category-chip').text(notice.category));
		if (notice.is_new) $topline.append($('<span>').addClass('new-badge').text('NEW'));
		if (urgent) $topline.append($('<span>').addClass('priority-badge').text('Urgent'));
		const detailUrl = window.NOTICE_BOARD.detailsUrl + '?notice_id=' + encodeURIComponent(notice.notice_id);
		const $title = $('<h3>').append($('<a>').attr('href', detailUrl).text(notice.title));
		const $description = $('<p>').addClass('card-description').text(notice.description);
		const $expand = $('<button>').attr({ type: 'button', 'aria-expanded': 'false' }).addClass('expand-description').text('Read summary');
		const $footer = $('<div>').addClass('card-bottom').append($('<span>').addClass('chip department-chip').text(notice.department), $('<span>').addClass('notice-date').text(expiryText));
		const $link = $('<a>').addClass('card-read-more').attr('href', detailUrl).text('Open notice');
		return $card.append($topline, $title, $description, $expand, $footer, $link);
	}

	window.noticeRenderIndex = 0;
	function categorySlug(category) { return String(category || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''); }
	function categoryIcon(category) {
		const paths = {
			Academics: '<path d="M2 8 12 3l10 5-10 5L2 8Zm4 3v5c3 2 9 2 12 0v-5M21 9v6"/>',
			Examinations: '<path d="M7 3h10v18H7zM10 8h4m-4 4h4m-4 4h3"/>',
			Events: '<path d="M4 5h16v16H4zM8 3v4m8-4v4M4 10h16m-11 4h2m3 0h2"/>',
			Internships: '<path d="M3 8h18v12H3zM8 8V5h8v3m-13 5h18m-11-1v3h4v-3"/>',
			Placements: '<path d="M4 20V9l8-5 8 5v11M8 20v-7h8v7m-5-4h2"/>',
			Workshops: '<path d="m14 6 4-4 4 4-4 4m-4-4-9 9-3 5 5-2 9-9"/>',
			'General Announcements': '<path d="M4 10h4l10-5v14L8 14H4zM8 14l2 6h4l-3-7m11-5v6"/>'
		};
		return $('<svg>').attr({ viewBox: '0 0 24 24', 'aria-hidden': 'true', focusable: 'false' }).addClass('category-icon').append($(paths[category] || paths['General Announcements']));
	}

	function showSkeletons() {
		$list.html(Array.from({ length: 9 }, function () {
			return '<article class="notice-card skeleton-card"><span class="skeleton-line skeleton-chip"></span><span class="skeleton-line skeleton-title"></span><span class="skeleton-line"></span><span class="skeleton-line short"></span><span class="skeleton-line skeleton-footer"></span></article>';
		}).join(''));
	}

	function renderExtras(response) {
		const urgent = response.urgent || [];
		if (urgent.length) {
			$('#urgent-ticker-track').html(urgent.map(function (notice) {
				return '<a href="' + window.NOTICE_BOARD.detailsUrl + '?notice_id=' + encodeURIComponent(notice.notice_id) + '">' + escapeHtml(notice.title) + '</a>';
			}).join('<span class="ticker-separator" aria-hidden="true">•</span>'));
			$('#urgent-ticker').prop('hidden', false);
		} else $('#urgent-ticker').prop('hidden', true);

		const expiring = response.expiring_soon || [];
		if (expiring.length) {
			$('#expiring-list').empty().append(expiring.map(function (notice) { return renderCard(notice, true); }));
			$('#expiring-section').prop('hidden', false);
		} else $('#expiring-section').prop('hidden', true);
	}

	function loadNotices(append) {
		if (request && !append) request.abort();
		if (!append) {
			page = 1;
			total = 0;
			$loadMore.prop('hidden', true);
			$status.empty().removeClass('is-error');
			showSkeletons();
			$count.text('');
		}
		loadingMore = Boolean(append);
		$loadMore.prop('disabled', true).text('Loading...');
		const filters = Object.fromEntries(new FormData($form[0]).entries());
		filters.status = window.NOTICE_BOARD.status;
		filters.page = append ? page + 1 : 1;
		request = $.ajax({ url: window.NOTICE_BOARD.endpoint, method: 'GET', data: filters, dataType: 'json' })
			.done(function (response) {
				if (!response.success) throw new Error(response.message || 'Unable to load notices.');
				const data = response.data || {};
				const notices = data.notices || [];
				page = data.pagination.page;
				total = data.pagination.total;
				$count.text(total + (total === 1 ? ' notice' : ' notices'));
				if (!append && window.NOTICE_BOARD.status === 'active') renderExtras(data);
				if (!append && data.hero_stats) {
					$('#hero-active-count').text(Number(data.hero_stats.active || 0));
					$('#hero-urgent-count').text(Number(data.hero_stats.urgent || 0));
				}
				if (!notices.length) {
					if (append) return;
					$list.empty();
					$status.html('<div class="empty-state"><svg class="empty-illustration" viewBox="0 0 120 90" role="img" aria-label="An empty campus notice board"><path d="M16 12h88v58H16zM30 27h60M30 39h43M30 51h50M53 70v10m-18 0h52" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg><strong>' + (filters.search || filters.category || filters.department ? 'No notices match those filters' : 'Nothing to show here yet') + '</strong><span>Try another search or clear one of the filters.</span></div>');
					return;
				}
				$status.empty();
				window.noticeRenderIndex = append ? $list.children().length : 0;
				const cards = notices.map(function (notice) { return renderCard(notice, false); });
				if (append) $list.append(cards); else $list.empty().append(cards);
				$list.children('.notice-card').hide().each(function (index) { $(this).delay(index * 45).fadeIn(220); });
				$loadMore.prop('hidden', !data.pagination.has_more);
			})
			.fail(function (xhr, state) {
				if (state === 'abort') return;
				if (!append) {
					$list.empty();
					$status.addClass('is-error').html('<div class="empty-state"><svg class="empty-illustration" viewBox="0 0 120 90" role="img" aria-label="A notice board with a connection warning"><path d="M16 12h88v58H16zM30 27h60M30 39h43M30 51h50" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/><circle cx="88" cy="66" r="15" fill="var(--cream)" stroke="currentColor" stroke-width="4"/><path d="M88 57v10m0 6v1" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg><strong>We could not load notices.</strong><span>Check your connection and try again.</span></div>');
				}
				else $status.addClass('is-error').text('More notices could not be loaded. Try again.');
			})
			.always(function () {
				loadingMore = false;
				$loadMore.prop('disabled', false).text('Load more notices');
			});
	}

	$form.on('input', '#search', function () {
		this.value = this.value.slice(0, 100);
		clearTimeout(delay);
		delay = setTimeout(function () { loadNotices(false); }, 250);
	});
	$form.on('change', 'select', function () { loadNotices(false); });
	$('#category-chips').on('click', '.category-chip-button', function () {
		const category = $(this).data('category') || '';
		$form.find('[name="category"]').val(category);
		$('#category-chips .category-chip-button').removeClass('is-active').attr('aria-pressed', 'false');
		$(this).addClass('is-active').attr('aria-pressed', 'true');
		loadNotices(false);
	});
	$('.clear-filters').on('click', function () {
		$form[0].reset();
		$form.find('[name="category"]').val('');
		$('#category-chips .category-chip-button').removeClass('is-active').attr('aria-pressed', 'false').first().addClass('is-active').attr('aria-pressed', 'true');
		loadNotices(false);
	});
	$(document).on('click', '.expand-description', function () {
		const expanded = $(this).attr('aria-expanded') === 'true';
		$(this).attr('aria-expanded', String(!expanded)).text(expanded ? 'Read summary' : 'Show less');
		const $description = $(this).siblings('.card-description').stop(true, true);
		if (!expanded) $description.addClass('is-expanded').hide().slideDown(160);
		else $description.slideUp(160, function () { $(this).removeClass('is-expanded').show(); });
	});
	$loadMore.on('click', function () { if (!loadingMore) loadNotices(true); });
	loadNotices(false);
});
