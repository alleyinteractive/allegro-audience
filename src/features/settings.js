(function () {
	var cfg = window.allegroAudienceSettings || {};
	var btn = document.getElementById('allegro-save-btn');
	var input = document.getElementById('allegro-tenant-url');
	var stepsEl = document.getElementById('allegro-steps');
	var stepHealth = document.getElementById('allegro-step-health');
	var stepCors = document.getElementById('allegro-step-cors');
	var noticeEl = document.getElementById('allegro-notice');
	var badge = document.getElementById('allegro-badge');

	if (!btn || !input) {
		return;
	}

	function setBadge(state) {
		badge.className = 'badge-' + state;
		badge.textContent = cfg.l10n[state === 'connected' ? 'connected' : state === 'corsWarning' ? 'corsWarning' : 'notConfigured'];
	}

	function setStep(el, status) {
		el.style.display = 'flex';
		var icon = el.querySelector('.allegro-step-icon');
		if (status === 'pending') {
			icon.innerHTML = '<span class="spinner is-active allegro-spinner"></span>';
		} else if (status === 'success') {
			icon.innerHTML = '<span class="allegro-step-success">&#10003;</span>';
		} else {
			icon.innerHTML = '<span class="allegro-step-error">&#10007;</span>';
		}
	}

	function escapeHtml(str) {
		return String(str).replace(/[&<>"']/g, function (ch) {
			return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
		});
	}

	function showNotice(type, message, linkHtml) {
		var html = escapeHtml(message) + (linkHtml ? ' ' + linkHtml : '');
		noticeEl.innerHTML = '<div class="notice notice-' + type + ' inline"><p>' + html + '</p></div>';
	}

	function resetSteps() {
		stepsEl.style.display = 'none';
		stepHealth.style.display = 'none';
		stepCors.style.display = 'none';
		noticeEl.innerHTML = '';
	}

	// Show initial badge; auto-run CORS check if a URL is already saved.
	if (cfg.tenantUrl) {
		setBadge('notConfigured');
		fetch(cfg.tenantUrl + '/client.js', { mode: 'cors', cache: 'no-store' })
			.then(function () { setBadge('connected'); })
			.catch(function () { setBadge('corsWarning'); });
	} else {
		setBadge('notConfigured');
	}

	btn.addEventListener('click', function () {
		var url = input.value.trim().replace(/\/+$/, '');
		if (!url) return;

		btn.disabled = true;
		btn.textContent = cfg.l10n.saving;
		resetSteps();
		stepsEl.style.display = 'block';
		setStep(stepHealth, 'pending');

		fetch(cfg.restUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body: JSON.stringify({ tenant_url: url }),
		})
		.then(function (res) {
			return res.json().then(function (data) { return { ok: res.ok, data: data }; });
		})
		.then(function (result) {
			if (!result.ok) {
				setStep(stepHealth, 'error');
				showNotice('error', result.data.message || cfg.l10n.networkError);
				btn.disabled = false;
				btn.textContent = cfg.l10n.saveVerify;
				return;
			}

			setStep(stepHealth, 'success');
			setStep(stepCors, 'pending');

			fetch(url + '/client.js', { mode: 'cors', cache: 'no-store' })
				.then(function () {
					setStep(stepCors, 'success');
					setBadge('connected');
					showNotice('success', cfg.l10n.successMessage);
				})
				.catch(function () {
					setStep(stepCors, 'error');
					setBadge('corsWarning');
					showNotice(
						'warning',
						cfg.l10n.corsMessage,
						'<a href="' + cfg.docsUrl + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(cfg.l10n.docsLinkText) + '</a>.'
					);
				})
				.finally(function () {
					btn.disabled = false;
					btn.textContent = cfg.l10n.saveVerify;
				});
		})
		.catch(function () {
			setStep(stepHealth, 'error');
			showNotice('error', cfg.l10n.networkError);
			btn.disabled = false;
			btn.textContent = cfg.l10n.saveVerify;
		});
	});
}());
