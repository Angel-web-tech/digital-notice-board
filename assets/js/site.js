$(function () {
	const $menu = $('.site-nav');
	const $menuButton = $('.mobile-menu-toggle');
	function updateClock() {
		const text = new Intl.DateTimeFormat('en-IN', { timeZone: 'Asia/Kolkata', hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date());
		$('#campus-clock').text(text + ' IST');
	}
	updateClock();
	window.setInterval(updateClock, 60000);

	$menuButton.on('click', function () {
		const open = $(this).attr('aria-expanded') !== 'true';
		$(this).attr({ 'aria-expanded': String(open), 'aria-label': open ? 'Close navigation menu' : 'Open navigation menu' });
		$menu.toggleClass('is-open', open);
	});
	const $adminSidebar = $('.admin-sidebar');
	const $adminOverlay = $('.admin-sidebar-overlay');
	const $adminMenuButton = $('.admin-menu-toggle');
	if ($adminSidebar.length && !$adminSidebar.attr('id')) $adminSidebar.attr('id', 'admin-sidebar');
	const adminIconPaths = [
		['dashboard.php', 'M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z'],
		['manage-notices.php', 'M4 5h16M4 12h16M4 19h16'],
		['add-notice.php', 'M12 5v14M5 12h14'],
		['public', 'M14 4h6v6m0-6-9 9M19 13v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h6']
	];
	$adminSidebar.find('a').each(function () {
		const href = this.getAttribute('href') || '';
		const icon = adminIconPaths.find(function (entry) { return entry[0] === 'public' ? href === '/' || href.endsWith('/digital-notice-board/') : href.includes(entry[0]); });
		if (!icon || this.querySelector('.admin-nav-icon')) return;
		const $svg = $('<svg>').addClass('admin-nav-icon').attr({ viewBox: '0 0 24 24', 'aria-hidden': 'true', focusable: 'false' });
		$svg.append($('<path>').attr('d', icon[1]));
		$(this).prepend($svg);
	});
	if (window.APP_USER_ROLE === 'admin') {
		[
			['manage-users.php', 'Manage users', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8'],
			['activity-log.php', 'Activity log', 'M4 19V5m0 14h17M8 15l3-4 3 2 5-7']
		].forEach(function (item) {
			if ($adminSidebar.find('a[href*="' + item[0] + '"]').length) return;
			const $link = $('<a>').attr('href', window.APP_BASE_URL + '/admin/' + item[0]);
			const $icon = $('<svg>').addClass('admin-nav-icon').attr({viewBox:'0 0 24 24','aria-hidden':'true'}).append($('<path>').attr('d',item[2]));
			$link.append($icon, $('<span>').text(item[1]));
			$adminSidebar.append($link);
		});
	}
	function closeAdminMenu() {
		$adminSidebar.removeClass('is-open');
		$adminOverlay.removeClass('is-visible');
		$adminMenuButton.removeClass('is-open').attr({ 'aria-expanded': 'false', 'aria-label': 'Open admin navigation' });
	}
	$adminMenuButton.on('click', function () {
		const open = !$(this).hasClass('is-open');
		$(this).toggleClass('is-open', open).attr({ 'aria-expanded': String(open), 'aria-label': open ? 'Close admin navigation' : 'Open admin navigation' });
		$adminSidebar.toggleClass('is-open', open);
		$adminOverlay.toggleClass('is-visible', open);
	});
	$adminOverlay.on('click', closeAdminMenu);
	$(document).on('keydown', function (event) {
		if (event.key === 'Escape') {
			$menu.removeClass('is-open');
			$menuButton.attr({ 'aria-expanded': 'false', 'aria-label': 'Open navigation menu' });
			closeAdminMenu();
		}
	});
	let scrollFrame = false;
	window.addEventListener('scroll', function () {
		if (scrollFrame) return;
		scrollFrame = true;
		window.requestAnimationFrame(function () {
			document.body.classList.toggle('has-scrolled', window.scrollY > 24);
			$('.back-to-top').toggleClass('is-visible', window.scrollY > 350);
			scrollFrame = false;
		});
	}, { passive: true });
	$('.back-to-top').on('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
	$('.filter-panel-toggle').on('click', function () {
		const $button = $(this);
		const expanded = $button.attr('aria-expanded') === 'true';
		$button.attr('aria-expanded', String(!expanded));
		$('#notice-filters').stop(true, true).slideToggle(180);
	});
});