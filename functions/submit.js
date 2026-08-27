/**
 * Cloudflare Pages Function: POST /submit
 *
 * JS port of submit.php for deployment on Cloudflare Pages (no PHP runtime
 * available there). Same fields, same validation, same email content as the
 * IONOS/PHP version, but sends mail via the Resend HTTP API instead of SMTP
 * (Workers can't open raw SMTP sockets).
 *
 * Required environment variables (set as Cloudflare Pages secrets):
 *   RESEND_API_KEY              - Resend API key
 *   FROM_EMAIL                  - verified sending address, e.g. noreply@yourclub.org
 *   FROM_NAME                   - optional, defaults to "TRARC Membership Application"
 *   BOARD_RECIPIENTS            - comma-separated board email addresses
 *   SEND_APPLICANT_CONFIRMATION - "true" to also email applicants a confirmation
 */
import { renderFormPage, renderMessage, buildEmailBodies, cleanHeaderValue } from './_shared.js';

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function htmlResponse(html, status = 200) {
  return new Response(html, {
    status,
    headers: { 'content-type': 'text/html; charset=utf-8' },
  });
}

async function sendEmail(env, { to, subject, html, text, replyTo }) {
  const res = await fetch('https://api.resend.com/emails', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${env.RESEND_API_KEY}`,
      'content-type': 'application/json',
    },
    body: JSON.stringify({
      from: `${env.FROM_NAME || 'TRARC Membership Application'} <${env.FROM_EMAIL}>`,
      to,
      reply_to: replyTo || undefined,
      subject,
      html,
      text,
    }),
  });
  if (!res.ok) {
    const body = await res.text().catch(() => '');
    throw new Error(`Resend API error ${res.status}: ${body}`);
  }
}

export async function onRequestPost({ request, env }) {
  const form = await request.formData();
  const field = (key) => String(form.get(key) ?? '').trim();

  // --- Honeypot -------------------------------------------------------------
  if (field('website') !== '') {
    return htmlResponse(
      renderMessage(
        'Application Received',
        'Thank you — your application has been received and sent to the TRARC board for review.'
      )
    );
  }

  // --- Collect input ----------------------------------------------------------
  const old = {
    name: field('name'),
    callsign: field('callsign'),
    address: field('address'),
    city: field('city'),
    state: field('state'),
    zip: field('zip'),
    home_phone: field('home_phone'),
    work_phone: field('work_phone'),
    cell_phone: field('cell_phone'),
    email: field('email'),
    license_class: field('license_class'),
    license_expires: field('license_expires'),
    birthday: field('birthday'),
    membership_type: field('membership_type'),
    arrl_member: form.get('arrl_member') != null,
    ares_member: form.get('ares_member') != null,
    arrl_expires: field('arrl_expires'),
    participate: field('participate'),
    interests: field('interests'),
    agree: form.get('agree') != null,
    signature: field('signature'),
    under18: field('under18'),
    parent_signature: field('parent_signature'),
    family: {},
  };

  for (const i of [1, 2]) {
    old.family[i] = {
      name: String(form.get(`family[${i}][name]`) ?? '').trim(),
      callsign: String(form.get(`family[${i}][callsign]`) ?? '').trim(),
      license_class: String(form.get(`family[${i}][license_class]`) ?? '').trim(),
      arrl: String(form.get(`family[${i}][arrl]`) ?? '').trim(),
    };
  }

  // --- Validate -----------------------------------------------------------
  const errors = {};
  if (!old.name) errors.name = 'Name is required.';
  if (!old.callsign) errors.callsign = 'Call sign is required.';
  if (!old.address) errors.address = 'Address is required.';
  if (!old.city) errors.city = 'City is required.';
  if (!old.state) errors.state = 'State is required.';
  if (!old.zip) {
    errors.zip = 'Zip code is required.';
  } else if (!/^\d{5}(-\d{4})?$/.test(old.zip)) {
    errors.zip = 'Enter a valid US zip code (e.g. 15132 or 15132-1234).';
  }
  if (!old.license_class) errors.license_class = 'License class is required.';
  if (!['Regular', 'Associate', 'Student'].includes(old.membership_type)) {
    errors.membership_type = 'Please choose a membership type.';
  }
  if (!['Yes', 'No'].includes(old.participate)) {
    errors.participate = 'Please answer this question.';
  }
  if (old.email && !EMAIL_RE.test(old.email)) {
    errors.email = 'Enter a valid email address.';
  }
  if (!old.agree) errors.agree = 'You must agree to the statement above.';
  if (!old.signature) errors.signature = 'Typed signature is required.';
  if (!['Yes', 'No'].includes(old.under18)) {
    errors.under18 = 'Please answer this question.';
  }
  if (old.under18 === 'Yes' && !old.parent_signature) {
    errors.parent_signature = 'Parent/guardian signature is required for applicants under 18.';
  }

  if (Object.keys(errors).length > 0) {
    return htmlResponse(renderFormPage(old, errors));
  }

  // --- Build and send email -------------------------------------------------
  const submittedAt = new Date().toISOString().replace('T', ' ').slice(0, 19) + ' UTC';
  const { html, plain } = buildEmailBodies(old, submittedAt);

  const boardRecipients = (env.BOARD_RECIPIENTS || '')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);

  let replyTo;
  if (old.email) {
    const cleanEmail = cleanHeaderValue(old.email);
    if (EMAIL_RE.test(cleanEmail)) {
      replyTo = `${cleanHeaderValue(old.name)} <${cleanEmail}>`;
    }
  }

  try {
    await sendEmail(env, {
      to: boardRecipients,
      subject: `New TRARC Membership Application — ${old.name} (${old.callsign})`,
      html,
      text: plain,
      replyTo,
    });
  } catch (err) {
    console.error('TRARC membership application email failed:', err);
    return htmlResponse(
      renderMessage(
        'Something went wrong',
        'We were unable to send your application right now. Please try again in a few minutes, or contact the TRARC board directly.'
      ),
      502
    );
  }

  // Best-effort applicant confirmation. Never blocks success even if it fails.
  if (env.SEND_APPLICANT_CONFIRMATION === 'true' && old.email && EMAIL_RE.test(old.email)) {
    try {
      await sendEmail(env, {
        to: [old.email],
        subject: 'TRARC Membership Application Received',
        html: '<p>Thanks for applying to TRARC — the board has received your application and will follow up.</p>',
        text: 'Thanks for applying to TRARC - the board has received your application and will follow up.',
      });
    } catch (err) {
      console.error('TRARC applicant confirmation email failed:', err);
    }
  }

  return htmlResponse(
    renderMessage(
      'Application Received',
      'Thank you — your application has been received and sent to the TRARC board for review.'
    )
  );
}

export async function onRequestGet() {
  return Response.redirect('/', 302);
}
