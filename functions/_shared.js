/**
 * Shared HTML rendering + email-body building for the Cloudflare Pages
 * version of the TRARC membership form. Mirrors includes/functions.php and
 * the render_*()/build_email_bodies() helpers in submit.php so the Cloudflare
 * and IONOS deployments produce the same form, errors, and email content.
 */

export function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

export function cleanHeaderValue(value) {
  return String(value ?? '').replace(/[\r\n]/g, '').trim();
}

function errorSpan(errors, key) {
  return errors[key] ? `<span class="field-error">${escapeHtml(errors[key])}</span>` : '';
}

function errorClass(errors, key) {
  return errors[key] ? 'has-error' : '';
}

function checked(old, key, value) {
  return old[key] === value ? 'checked' : '';
}

function familyRow(old, i) {
  const m = (old.family && old.family[i]) || {};
  return `
            <div class="family-row">
                <p class="family-row-label">Family member ${i}</p>
                <div class="form-row four-col">
                    <div class="field">
                        <label for="family_${i}_name">Name</label>
                        <input type="text" id="family_${i}_name" name="family[${i}][name]" value="${escapeHtml(m.name || '')}">
                    </div>
                    <div class="field">
                        <label for="family_${i}_callsign">Call Sign</label>
                        <input type="text" id="family_${i}_callsign" name="family[${i}][callsign]" value="${escapeHtml(m.callsign || '')}">
                    </div>
                    <div class="field">
                        <label for="family_${i}_license_class">License Class</label>
                        <input type="text" id="family_${i}_license_class" name="family[${i}][license_class]" value="${escapeHtml(m.license_class || '')}">
                    </div>
                    <div class="field radio-group">
                        <span class="group-label">ARRL Member</span>
                        <label class="inline"><input type="radio" name="family[${i}][arrl]" value="Yes" ${m.arrl === 'Yes' ? 'checked' : ''}> Yes</label>
                        <label class="inline"><input type="radio" name="family[${i}][arrl]" value="No" ${m.arrl === 'No' ? 'checked' : ''}> No</label>
                    </div>
                </div>
            </div>`;
}

export function renderFormFields(old = {}, errors = {}) {
  return `
<form action="/submit" method="post" novalidate>

    <!-- Honeypot: real users never see or fill this in. -->
    <div class="hp-field" aria-hidden="true">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="">
    </div>

    <fieldset>
        <legend>Contact Info</legend>

        <div class="form-row two-col">
            <div class="field ${errorClass(errors, 'name')}">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" value="${escapeHtml(old.name || '')}" required>
                ${errorSpan(errors, 'name')}
            </div>
            <div class="field ${errorClass(errors, 'callsign')}">
                <label for="callsign">Call Sign *</label>
                <input type="text" id="callsign" name="callsign" value="${escapeHtml(old.callsign || '')}" required>
                ${errorSpan(errors, 'callsign')}
            </div>
        </div>

        <div class="form-row">
            <div class="field ${errorClass(errors, 'address')}">
                <label for="address">Address *</label>
                <input type="text" id="address" name="address" value="${escapeHtml(old.address || '')}" required>
                ${errorSpan(errors, 'address')}
            </div>
        </div>

        <div class="form-row three-col">
            <div class="field ${errorClass(errors, 'city')}">
                <label for="city">City *</label>
                <input type="text" id="city" name="city" value="${escapeHtml(old.city || '')}" required>
                ${errorSpan(errors, 'city')}
            </div>
            <div class="field ${errorClass(errors, 'state')}">
                <label for="state">State *</label>
                <input type="text" id="state" name="state" maxlength="20" value="${escapeHtml(old.state || '')}" required>
                ${errorSpan(errors, 'state')}
            </div>
            <div class="field ${errorClass(errors, 'zip')}">
                <label for="zip">Zip Code *</label>
                <input type="text" id="zip" name="zip" pattern="^\\d{5}(-\\d{4})?$" title="5-digit US zip, optionally with a 4-digit extension" value="${escapeHtml(old.zip || '')}" required>
                ${errorSpan(errors, 'zip')}
            </div>
        </div>

        <div class="form-row three-col">
            <div class="field">
                <label for="home_phone">Home Phone</label>
                <input type="tel" id="home_phone" name="home_phone" value="${escapeHtml(old.home_phone || '')}">
            </div>
            <div class="field">
                <label for="work_phone">Work Phone</label>
                <input type="tel" id="work_phone" name="work_phone" value="${escapeHtml(old.work_phone || '')}">
            </div>
            <div class="field">
                <label for="cell_phone">Cell Phone</label>
                <input type="tel" id="cell_phone" name="cell_phone" value="${escapeHtml(old.cell_phone || '')}">
            </div>
        </div>

        <div class="form-row">
            <div class="field ${errorClass(errors, 'email')}">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="${escapeHtml(old.email || '')}">
                ${errorSpan(errors, 'email')}
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>License &amp; Membership Type</legend>

        <div class="form-row three-col">
            <div class="field ${errorClass(errors, 'license_class')}">
                <label for="license_class">License Class *</label>
                <input type="text" id="license_class" name="license_class" placeholder="e.g. Technician, General, Amateur Extra" value="${escapeHtml(old.license_class || '')}" required>
                ${errorSpan(errors, 'license_class')}
            </div>
            <div class="field">
                <label for="license_expires">License Expires</label>
                <input type="date" id="license_expires" name="license_expires" value="${escapeHtml(old.license_expires || '')}">
            </div>
            <div class="field">
                <label for="birthday">Birthday</label>
                <input type="date" id="birthday" name="birthday" value="${escapeHtml(old.birthday || '')}">
            </div>
        </div>

        <div class="field radio-group ${errorClass(errors, 'membership_type')}">
            <span class="group-label">I am applying for membership type: *</span>
            <label class="inline"><input type="radio" name="membership_type" value="Regular" ${checked(old, 'membership_type', 'Regular')} required> Regular</label>
            <label class="inline"><input type="radio" name="membership_type" value="Associate" ${checked(old, 'membership_type', 'Associate')}> Associate</label>
            <label class="inline"><input type="radio" name="membership_type" value="Student" ${checked(old, 'membership_type', 'Student')}> Student</label>
            ${errorSpan(errors, 'membership_type')}
        </div>

        <div class="field checkbox-group">
            <span class="group-label">I am a member of:</span>
            <label class="inline"><input type="checkbox" id="arrl_member" name="arrl_member" value="1" ${old.arrl_member ? 'checked' : ''}> ARRL</label>
            <label class="inline"><input type="checkbox" name="ares_member" value="1" ${old.ares_member ? 'checked' : ''}> ARES</label>
        </div>

        <div class="form-row" id="arrl-expires-row">
            <div class="field">
                <label for="arrl_expires">ARRL Membership Expires</label>
                <input type="text" id="arrl_expires" name="arrl_expires" placeholder="MM/YYYY" value="${escapeHtml(old.arrl_expires || '')}">
            </div>
        </div>

        <div class="field radio-group ${errorClass(errors, 'participate')}">
            <span class="group-label">Will you participate in club activities? *</span>
            <label class="inline"><input type="radio" name="participate" value="Yes" ${checked(old, 'participate', 'Yes')} required> Yes</label>
            <label class="inline"><input type="radio" name="participate" value="No" ${checked(old, 'participate', 'No')}> No</label>
            ${errorSpan(errors, 'participate')}
        </div>
    </fieldset>

    <fieldset>
        <legend>Club Participation &amp; Interests</legend>
        <div class="field">
            <label for="interests">List your interests in Amateur Radio</label>
            <textarea id="interests" name="interests" rows="3">${escapeHtml(old.interests || '')}</textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>Additional Family Members Who Are or Want to Be Members of TRARC</legend>
        ${familyRow(old, 1)}
        ${familyRow(old, 2)}
    </fieldset>

    <fieldset>
        <legend>Agreement / Signature</legend>

        <p class="agreement-statement">
            Applicant agrees to conduct themselves in accordance with the TRARC Constitution when
            participating in any TRARC function, meeting, or sanctioned activity.
        </p>

        <div class="field checkbox-group ${errorClass(errors, 'agree')}">
            <label class="inline">
                <input type="checkbox" name="agree" value="1" ${old.agree ? 'checked' : ''} required>
                I agree to the statement above
            </label>
            ${errorSpan(errors, 'agree')}
        </div>

        <div class="field ${errorClass(errors, 'signature')}">
            <label for="signature">Typed Signature (full legal name) *</label>
            <input type="text" id="signature" name="signature" value="${escapeHtml(old.signature || '')}" required>
            ${errorSpan(errors, 'signature')}
            <p class="field-hint">The submission date and time will be recorded automatically.</p>
        </div>

        <p class="disclaimer-text">
            The Officers and Members of TRARC will NOT assume parental responsibilities for any minor
            member at any time. Therefore, all minors must be accompanied by their parent or guardian
            while observing, attending, or participating in any TRARC function, meeting, or sanctioned
            activity.
        </p>

        <div class="field radio-group ${errorClass(errors, 'under18')}">
            <span class="group-label">Are you under 18? *</span>
            <label class="inline"><input type="radio" id="under18_yes" name="under18" value="Yes" ${checked(old, 'under18', 'Yes')} required> Yes</label>
            <label class="inline"><input type="radio" id="under18_no" name="under18" value="No" ${checked(old, 'under18', 'No')}> No</label>
            ${errorSpan(errors, 'under18')}
        </div>

        <div class="form-row" id="parent-signature-row">
            <div class="field ${errorClass(errors, 'parent_signature')}">
                <label for="parent_signature">Parent/Guardian Typed Signature</label>
                <input type="text" id="parent_signature" name="parent_signature" value="${escapeHtml(old.parent_signature || '')}">
                ${errorSpan(errors, 'parent_signature')}
                <p class="field-hint">Required if the applicant is under 18.</p>
            </div>
        </div>
    </fieldset>

    <div class="submit-row">
        <button type="submit">Submit Application</button>
    </div>
</form>

<script>
(function () {
    var arrlCheckbox = document.getElementById('arrl_member');
    var arrlExpiresRow = document.getElementById('arrl-expires-row');
    var under18Yes = document.getElementById('under18_yes');
    var under18No = document.getElementById('under18_no');
    var parentRow = document.getElementById('parent-signature-row');
    var form = document.querySelector('form');

    function refreshArrl() {
        if (!arrlCheckbox || !arrlExpiresRow) { return; }
        arrlExpiresRow.style.display = arrlCheckbox.checked ? '' : 'none';
    }

    function refreshUnder18() {
        if (!parentRow) { return; }
        parentRow.style.display = (under18Yes && under18Yes.checked) ? '' : 'none';
    }

    if (arrlCheckbox) {
        arrlCheckbox.addEventListener('change', refreshArrl);
        refreshArrl();
    }
    if (under18Yes && under18No) {
        under18Yes.addEventListener('change', refreshUnder18);
        under18No.addEventListener('change', refreshUnder18);
        refreshUnder18();
    }
    if (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Submitting…';
            }
        });
    }
})();
</script>`;
}

function renderPage({ header = '', main }, title = 'TRARC Membership Application') {
  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${escapeHtml(title)}</title>
<link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="page">
    <header class="page-header">
        <h1>Two Rivers Amateur Radio Club of McKeesport, PA</h1>
        ${header}
    </header>
    ${main}
    <footer class="page-footer">
        <p>Questions about membership? Contact the TRARC board.</p>
    </footer>
</div>
</body>
</html>`;
}

export function renderFormPage(old = {}, errors = {}) {
  const hasErrors = Object.keys(errors).length > 0;
  return renderPage({
    header: `
        <h2>Membership Application</h2>
        <p class="required-note">Please fill in the required (*) fields for membership.</p>
        ${hasErrors ? '<p class="form-level-error">Please correct the highlighted fields below and resubmit.</p>' : ''}`,
    main: renderFormFields(old, errors),
  });
}

export function renderMessage(title, message) {
  return renderPage(
    {
      main: `
    <div class="message-box">
        <h2>${escapeHtml(title)}</h2>
        <p>${escapeHtml(message)}</p>
        <p><a href="/">Return to the membership application</a></p>
    </div>`,
    },
    `${title} - TRARC Membership Application`
  );
}

export function buildEmailBodies(old, submittedAt) {
  const lines = [];
  lines.push(['Name', old.name]);
  lines.push(['Call Sign', old.callsign]);
  lines.push(['Address', old.address]);
  lines.push(['City', old.city]);
  lines.push(['State', old.state]);
  lines.push(['Zip Code', old.zip]);
  if (old.home_phone) lines.push(['Home Phone', old.home_phone]);
  if (old.work_phone) lines.push(['Work Phone', old.work_phone]);
  if (old.cell_phone) lines.push(['Cell Phone', old.cell_phone]);
  if (old.email) lines.push(['Email', old.email]);
  lines.push(['License Class', old.license_class]);
  if (old.license_expires) lines.push(['License Expires', old.license_expires]);
  if (old.birthday) lines.push(['Birthday', old.birthday]);
  lines.push(['Membership Type', old.membership_type]);

  const arrlAres = [];
  if (old.arrl_member) arrlAres.push('ARRL');
  if (old.ares_member) arrlAres.push('ARES');
  lines.push(['Member of ARRL/ARES', arrlAres.length ? arrlAres.join(', ') : 'None']);
  if (old.arrl_member && old.arrl_expires) lines.push(['ARRL Membership Expires', old.arrl_expires]);

  lines.push(['Will Participate in Club Activities', old.participate]);

  if (old.interests) lines.push(['Interests in Amateur Radio', old.interests]);

  const familyLines = [];
  for (const i of [1, 2]) {
    const m = (old.family && old.family[i]) || {};
    if (!m.name && !m.callsign && !m.license_class && !m.arrl) continue;
    const parts = [];
    if (m.name) parts.push('Name: ' + m.name);
    if (m.callsign) parts.push('Call Sign: ' + m.callsign);
    if (m.license_class) parts.push('License Class: ' + m.license_class);
    if (m.arrl) parts.push('ARRL Member: ' + m.arrl);
    familyLines.push(`Family member ${i} — ` + parts.join(', '));
  }

  lines.push(['Agreement to TRARC Constitution', old.agree ? 'Agreed' : 'Not agreed']);
  lines.push(['Typed Signature', old.signature]);
  lines.push(['Under 18', old.under18]);
  if (old.under18 === 'Yes') lines.push(['Parent/Guardian Typed Signature', old.parent_signature]);
  lines.push(['Submitted', submittedAt]);

  let plain = 'New TRARC Membership Application\n\n';
  for (const [label, value] of lines) {
    plain += `${label}: ${value}\n`;
  }
  if (familyLines.length) {
    plain += '\nAdditional Family Members:\n';
    for (const fl of familyLines) plain += `${fl}\n`;
  }

  let html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#222;">';
  html += '<h2 style="margin-bottom:4px;">New TRARC Membership Application</h2>';
  for (const [label, value] of lines) {
    html += `<p style="margin:4px 0;"><strong>${escapeHtml(label)}:</strong> ${escapeHtml(value).replace(/\n/g, '<br>')}</p>`;
  }
  if (familyLines.length) {
    html += '<h3 style="margin-top:16px;margin-bottom:4px;">Additional Family Members</h3>';
    for (const fl of familyLines) html += `<p style="margin:4px 0;">${escapeHtml(fl)}</p>`;
  }
  html += '</div>';

  return { html, plain };
}
