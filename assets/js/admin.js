$(function () {
	if (window.DASHBOARD_STATS_URL) {
		$.getJSON(window.DASHBOARD_STATS_URL).done(function (response) {
			if (!response.success) { $('#dashboard-status').text(response.message); return; }
			const data = response.data;
			$('#dashboard-status').empty();
			$('#dashboard-content').prop('hidden', false);
			Object.keys(data.counts).forEach(function (key) { animateCount($('[data-count="' + key + '"]'), Number(data.counts[key])); });
			$('#department-list').html((data.departments || []).map(function (row) { return '<li><span>' + $('<span>').text(row.department).html() + '</span><strong>' + Number(row.total) + '</strong></li>'; }).join(''));
			$('#recent-list').html((data.recent || []).map(function (row) {
				return '<tr><td><a href="' + window.APP_BASE_URL + '/admin/edit-notice.php?notice_id=' + Number(row.notice_id) + '">' + $('<span>').text(row.title).html() + '</a></td><td>' + $('<span>').text(row.category).html() + '</td><td><span class="status-pill status-' + row.status + '">' + row.status + '</span></td></tr>';
			}).join(''));
			$('#urgent-list').html(data.urgent.length ? data.urgent.map(function (row) { return '<li><span class="urgent-dot"></span><div><strong>' + $('<span>').text(row.title).html() + '</strong><small>' + $('<span>').text(row.department).html() + ' · Expires ' + window.formatDisplayDate(row.expiry_date) + '</small></div></li>'; }).join('') : '<li class="no-urgent">No active urgent notices.</li>');
			$('#most-viewed-list').empty().append((data.most_viewed || []).map(function (item) {
				return $('<li>').append($('<a>').attr('href', window.APP_BASE_URL + '/notice-details.php?notice_id=' + Number(item.notice_id)).text(item.title), $('<span>').text(Number(item.views) + ' views'));
			}));
			drawCategoryChart(data.categories || []);
			drawMonthlyChart(data.monthly || []);
		}).fail(function () { $('#dashboard-status').addClass('is-error').text('Dashboard data could not be loaded.'); });
	}

	if (window.USER_API_URL) loadUsers();
	if (window.ACTIVITY_API_URL) loadActivity(false);

	function loadUsers() {
		$.getJSON(window.USER_API_URL).done(function (response) {
			if (!response.success) return showToast(response.message, 'error');
			$('#user-list').empty().append((response.data || []).map(function (user) {
				const $row = $('<tr>');
				$row.append($('<td>').text(user.full_name || user.username), $('<td>').append($('<span>').text(user.email || 'No email'), $('<small>').text(user.username)), $('<td>').append($('<span>').addClass('status-pill status-' + user.role).text(user.role)), $('<td>').append($('<span>').addClass('status-pill status-' + user.status).text(user.status)), $('<td>').text(window.formatDisplayDate(user.created_at)));
				const $action = $('<td>');
				if (user.status === 'pending') {
					$action.append(userActionButton('approve',user,'Approve'), userActionButton('reject',user,'Reject'));
				} else if (user.status === 'disabled') $action.append(userActionButton('enable',user,'Enable'));
				else if (user.role === 'faculty') $action.append(userActionButton('promote',user,'Promote'), userActionButton('disable',user,'Disable'));
				else if (Number(user.user_id) !== Number(window.CURRENT_USER_ID)) $action.append(userActionButton('disable',user,'Disable'));
				if (Number(user.user_id) !== Number(window.CURRENT_USER_ID)) $action.append(userActionButton('delete',user,'Delete'));
				return $row.append($action);
			}));
		}).fail(function () { showToast('User list could not be loaded.', 'error'); });
	}

	function userActionButton(action,user,label) {
		return $('<button>').addClass('button button-light user-action').attr({type:'button','data-action':action,'data-id':user.user_id,title:label}).text(label);
	}

	function loadActivity(append) {
		if (!window.ACTIVITY_API_URL) return;
		const $form = $('#activity-filters');
		const values = $form.length ? Object.fromEntries(new FormData($form[0]).entries()) : {};
		values.page = append ? Number($('#activity-more').data('page') || 1) + 1 : 1;
		$.getJSON(window.ACTIVITY_API_URL, values).done(function (response) {
			if (!response.success) return;
			const $rows = (response.data.items || []).map(function (item) {
				return $('<tr>').append($('<td>').text(window.formatDisplayDate(item.created_at, true)), $('<td>').text(item.username), $('<td>').append($('<span>').addClass('status-pill').text(item.action.replaceAll('_',' '))), $('<td>').text(item.notice_title || ''));
			});
			if (!append) $('#activity-list').empty();
			$('#activity-list').append($rows);
			$('#activity-more').data('page', response.data.pagination.page).prop('hidden', !response.data.pagination.has_more);
		});
	}

	$('#user-form').on('submit', function (event) {
		event.preventDefault();
		const form = this;
		const data = new FormData(form);
		$('.field-error').text('');
		$.ajax({url:window.USER_API_URL,method:'POST',data:data,processData:false,contentType:false,dataType:'json'})
			.done(function(response){if(!response.success){showToast(response.message,'error');return;}showToast(response.message,'success');form.reset();$('#password-strength').text('Password strength: weak');loadUsers();})
			.fail(function(xhr){const response=xhr.responseJSON;if(response&&response.errors)Object.keys(response.errors).forEach(function(field){$('#error-'+field).text(response.errors[field]);});showToast(response&&response.message?response.message:'Could not create user.','error');});
	});
	$(document).on('click','.user-action',function(){const button=this;const action=button.dataset.action;if(!window.confirm(action.charAt(0).toUpperCase()+action.slice(1)+' this account?'))return;$.post(window.USER_API_URL,{action:action,user_id:button.dataset.id,csrf_token:window.CSRF_TOKEN},null,'json').done(function(response){if(response.success){showToast(response.message,'success');loadUsers();}else showToast(response.message,'error');}).fail(function(xhr){showToast(xhr.responseJSON?.message||'Could not update this account.','error');});});
	$(document).on('click','.show-password',function(){const targetId=this.dataset.target;const input=targetId?document.getElementById(targetId):this.parentElement.querySelector('input');if(!input)return;input.type=input.type==='password'?'text':'password';const shown=input.type==='text';this.textContent=shown?'Hide':'Show';this.setAttribute('aria-pressed',String(shown));this.setAttribute('aria-label',(shown?'Hide':'Show')+' password');});
	function updateStrength(input){const value=input.value;let score=0;if(value.length>=8)score++;if(/[A-Za-z]/.test(value))score++;if(/[0-9]/.test(value))score++;if(/[^A-Za-z0-9]/.test(value))score++;const level=score>=4?'strong':score>=3?'medium':'weak';$('#password-strength').text('Password strength: '+level).attr('data-strength',level);$('#strength-bar').attr('data-strength',level);}
	$('#new-password,#register-password,#reset-password,#change-new-password').on('input',function(){updateStrength(this);});
	$('#register-form').on('submit',function(event){const form=this;const name=form.elements.full_name.value.trim();const email=form.elements.email.value.trim();const username=form.elements.username.value.trim();const password=form.elements.password.value;const confirm=form.elements.confirm_password.value;let valid=true;const checks={full_name: /^[\p{L} .]{3,60}$/u.test(name),email:/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email),username:/^[A-Za-z0-9_]{3,30}$/.test(username),password:password.length>=8&&/[A-Za-z]/.test(password)&&/[0-9]/.test(password),confirm_password:password===confirm};const messages={full_name:'Enter 3 to 60 letters, spaces or dots.',email:'Enter a valid email address.',username:'Use 3 to 30 letters, numbers or underscores.',password:'Use at least 8 characters, including a letter and a number.',confirm_password:'Passwords do not match.'};Object.keys(checks).forEach(function(field){$('#error-'+field).text(checks[field]?'':messages[field]);if(!checks[field])valid=false;});if(!valid)event.preventDefault();});
	$(document).on('click','.dismiss-prompt',function(){$(this).closest('.profile-prompt').slideUp(150,function(){$(this).remove();});});
	$('#activity-filters').on('submit',function(event){event.preventDefault();loadActivity(false);});
	$('#activity-more').on('click',function(){loadActivity(true);});

	function drawCategoryChart(categories) {
		const canvas = document.getElementById('category-chart');
		if (!canvas) return;
		const context = canvas.getContext('2d');
		const width = canvas.clientWidth;
		const height = canvas.height;
		const colors = ['#234735', '#C5A45D', '#68856f', '#b9412e', '#849487', '#d1b879', '#476657'];
		const max = Math.max(1, ...categories.map(function (item) { return Number(item.total); }));
		canvas.width = width * window.devicePixelRatio;
		canvas.height = height * window.devicePixelRatio;
		context.scale(window.devicePixelRatio, window.devicePixelRatio);
		const barWidth = Math.max(12, (width - 30) / Math.max(1, categories.length) - 14);
		const gap = (width - 30) / Math.max(1, categories.length);
		context.font = '11px Arial';
		categories.forEach(function (item, index) {
			const barHeight = (Number(item.total) / max) * (height - 50);
			const x = 15 + index * gap + (gap - barWidth) / 2;
			const y = height - 28 - barHeight;
			context.fillStyle = colors[index % colors.length];
			context.fillRect(x, y, barWidth, barHeight);
			context.fillStyle = '#26332B';
			context.textAlign = 'center';
			context.fillText(String(item.total), x + barWidth / 2, y - 6);
		});
		$('#category-legend').html(categories.map(function (item, index) { return '<span><i style="background:' + colors[index % colors.length] + '"></i>' + $('<span>').text(item.category).html() + ' (' + Number(item.total) + ')</span>'; }).join(''));
	}

	function animateCount($element, target) {
		const duration = 700;
		const start = performance.now();
		window.setTimeout(function () { $element.text(target); }, duration + 100);
		function frame(now) {
			const progress = Math.min(1, (now - start) / duration);
			$element.text(Math.round(target * (1 - Math.pow(1 - progress, 3))));
			if (progress < 1) requestAnimationFrame(frame);
		}
		requestAnimationFrame(frame);
	}

	function drawMonthlyChart(rows) {
		const canvas = document.getElementById('monthly-chart');
		if (!canvas) return;
		const now = new Date();
		const months = Array.from({ length: 12 }, function (_, index) {
			const date = new Date(now.getFullYear(), now.getMonth() - 11 + index, 1);
			return { key: date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0'), label: date.toLocaleDateString('en', { month: 'short' }), total: 0 };
		});
		const counts = new Map(rows.map(function (row) { return [row.month_key, Number(row.total)]; }));
		months.forEach(function (month) { month.total = counts.get(month.key) || 0; });
		const context = canvas.getContext('2d');
		const width = canvas.clientWidth;
		const height = canvas.height;
		const ratio = window.devicePixelRatio || 1;
		canvas.width = width * ratio;
		canvas.height = height * ratio;
		context.scale(ratio, ratio);
		const padding = { left: 30, right: 14, top: 18, bottom: 32 };
		const chartWidth = width - padding.left - padding.right;
		const chartHeight = height - padding.top - padding.bottom;
		const max = Math.max(1, ...months.map(function (month) { return month.total; }));
		context.strokeStyle = '#dfe3dc';
		context.lineWidth = 1;
		for (let grid = 0; grid <= 3; grid++) {
			const y = padding.top + chartHeight * grid / 3;
			context.beginPath(); context.moveTo(padding.left, y); context.lineTo(width - padding.right, y); context.stroke();
		}
		context.strokeStyle = '#234735';
		context.fillStyle = '#C5A45D';
		context.lineWidth = 2.5;
		context.beginPath();
		months.forEach(function (month, index) {
			const x = padding.left + chartWidth * index / (months.length - 1);
			const y = padding.top + chartHeight - chartHeight * month.total / max;
			if (index === 0) context.moveTo(x, y); else context.lineTo(x, y);
		});
		context.stroke();
		context.font = '10px Arial'; context.textAlign = 'center'; context.fillStyle = '#68736b';
		months.forEach(function (month, index) {
			const x = padding.left + chartWidth * index / (months.length - 1);
			const y = padding.top + chartHeight - chartHeight * month.total / max;
			context.beginPath(); context.arc(x, y, 3.5, 0, Math.PI * 2); context.fill();
			context.fillText(month.label, x, height - 9);
		});
	}

	function showToast(message, kind) {
		const $toast = $('<div>').addClass('toast ' + (kind || 'success')).attr('role', 'status').text(message);
		$('#toast-region').empty().append($toast);
		setTimeout(function () { $toast.fadeOut(250, function () { $(this).remove(); }); }, 4200);
	}

	function validateForm(form) {
		const values = Object.fromEntries(new FormData(form).entries());
		let valid = true;
		['title', 'description', 'category', 'department', 'priority', 'publish_date', 'expiry_date'].forEach(function (field) {
			if (!validateField(form, field, values)) valid = false;
		});
		return valid;
	}

	function validateField(form, field, values) {
		const value = values[field] || '';
		const characterCount = Array.from(value.trim()).length;
		let message = '';
		if (field === 'title' && (characterCount < 5 || characterCount > 150)) message = 'Title must be between 5 and 150 characters.';
		if (field === 'description' && !window.isDescriptionLengthValid(value)) message = 'Description must be between 10 and 2000 characters.';
		if (field === 'category' && !['Academics', 'Examinations', 'Events', 'Internships', 'Placements', 'Workshops', 'General Announcements'].includes(value)) message = 'Choose a valid category.';
		if (field === 'department' && !['All Departments', 'CSE', 'ECE', 'EEE', 'Mechanical', 'Civil'].includes(value)) message = 'Choose a valid department.';
		if (field === 'priority' && !['normal', 'urgent'].includes(value)) message = 'Choose a valid priority.';
		if (field === 'publish_date') {
			const publish = new Date(value);
			if (!value || Number.isNaN(publish.getTime())) message = 'Enter a valid publish date.';
		}
		if (field === 'expiry_date') {
			const publish = new Date(values.publish_date);
			const expiry = new Date(value);
			if (!value || Number.isNaN(expiry.getTime())) message = 'Enter a valid expiry date.';
			else if (Number.isNaN(publish.getTime()) || expiry <= publish) message = 'Expiry must be after the publish date.';
			else if (!values.notice_id && expiry <= new Date()) message = 'Expiry date must be in the future.';
		}
		$('#error-' + field).text(message);
		const control = form.elements[field];
		if (control) control.setAttribute('aria-invalid', message ? 'true' : 'false');
		return message === '';
	}

	$('#notice-form').on('input change blur', 'input[name], textarea[name], select[name]', function () {
		const form = this.form;
		const values = Object.fromEntries(new FormData(form).entries());
		validateField(form, this.name, values);
		if (this.name === 'publish_date') validateField(form, 'expiry_date', values);
		if (this.name === 'publish_date' || this.name === 'expiry_date') {
			const publish = new Date(values.publish_date);
			const expiry = new Date(values.expiry_date);
			$('#date-feedback').text(!Number.isNaN(publish.getTime()) && !Number.isNaN(expiry.getTime()) && expiry > publish ? 'Date range looks good.' : 'Choose an expiry date after the publish date.');
		}
	});

	$('#notice-form').on('submit', function (event) {
		event.preventDefault();
		const form = this;
		if (!validateForm(form)) return;
		const $button = $(form).find('[type="submit"]');
		$button.addClass('is-saving').prop('disabled', true).find('.button-label').text('Saving...');
		$.ajax({ url: form.dataset.saveUrl, method: 'POST', data: new FormData(form), processData: false, contentType: false, dataType: 'json' })
			.done(function (response) {
				if (!response.success) { showToast(response.message || 'Could not save notice.', 'error'); return; }
				showToast(response.message, 'success');
				setTimeout(function () { window.location.href = form.dataset.listUrl; }, 700);
			})
			.fail(function (xhr) {
				const response = xhr.responseJSON;
				if (response && response.errors) Object.keys(response.errors).forEach(function (field) { $('#error-' + field).text(response.errors[field]); });
				showToast(response && response.message ? response.message : 'Could not save notice. Check the connection and try again.', 'error');
			})
			.always(function () { $button.removeClass('is-saving').prop('disabled', false).find('.button-label').text(form.elements.notice_id.value ? 'Save changes' : 'Publish notice'); });
	});

	$('#notice-form [name="title"], #notice-form [name="description"]').each(function () {
		const input = this;
		const limit = Number(input.getAttribute('maxlength'));
		const label = input.name === 'title' ? 'title' : 'description';
		const $counter = $('#' + (input.name === 'title' ? 'title-counter' : 'description-counter'));
		function updateCounter() {
			const count = Array.from(input.value).length;
			$counter.text(count + ' / ' + limit + ' ' + label).toggleClass('is-limit', count >= limit);
		}
		$(input).on('input', updateCounter);
		updateCounter();
	});
	$('#notice-form [name="attachment"]').on('change', function(){
		const file=this.files[0];$('#error-attachment').text('');$('#attachment-feedback').text('');if(!file)return;
		const validTypes=['application/pdf','image/jpeg','image/png'];
		if(!validTypes.includes(file.type)||!(/\.(pdf|jpe?g|png)$/i).test(file.name))$('#error-attachment').text('Choose a PDF, JPG, or PNG file.');
		else if(file.size>2*1024*1024)$('#error-attachment').text('Attachments must be 2 MB or smaller.');
		else $('#attachment-feedback').text(file.name+' · '+(file.size/1024/1024).toFixed(2)+' MB');
	});

	$(document).on('click', '.delete-notice', function () {
		const button = this;
		if (!window.confirm('Delete this notice? This cannot be undone.')) return;
		$.ajax({ url: window.APP_BASE_URL + '/api/delete-notice.php', method: 'POST', dataType: 'json', data: { notice_id: button.dataset.id, csrf_token: button.dataset.csrf } })
			.done(function (response) { if (response.success) { $(button).closest('tr').remove(); showToast(response.message, 'success'); } else showToast(response.message, 'error'); })
			.fail(function (xhr) { showToast(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Could not delete notice.', 'error'); });
	});
});
