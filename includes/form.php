<?php
/**
 * Membership application form.
 * Expects $old (array of previously submitted values) and $errors
 * (array of field => message) to already be defined by the includer.
 */
if (!isset($old)) {
    $old = [];
}
if (!isset($errors)) {
    $errors = [];
}
?>
<form action="submit.php" method="post" novalidate>

    <!-- Honeypot: real users never see or fill this in. -->
    <div class="hp-field" aria-hidden="true">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="">
    </div>

    <fieldset>
        <legend>Contact Info</legend>

        <div class="form-row two-col">
            <div class="field <?= trarc_error_class($errors, 'name') ?>">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" value="<?= trarc_e(trarc_old($old, 'name')) ?>" required>
                <?= trarc_error($errors, 'name') ?>
            </div>
            <div class="field <?= trarc_error_class($errors, 'callsign') ?>">
                <label for="callsign">Call Sign *</label>
                <input type="text" id="callsign" name="callsign" value="<?= trarc_e(trarc_old($old, 'callsign')) ?>" required>
                <?= trarc_error($errors, 'callsign') ?>
            </div>
        </div>

        <div class="form-row">
            <div class="field <?= trarc_error_class($errors, 'address') ?>">
                <label for="address">Address *</label>
                <input type="text" id="address" name="address" value="<?= trarc_e(trarc_old($old, 'address')) ?>" required>
                <?= trarc_error($errors, 'address') ?>
            </div>
        </div>

        <div class="form-row three-col">
            <div class="field <?= trarc_error_class($errors, 'city') ?>">
                <label for="city">City *</label>
                <input type="text" id="city" name="city" value="<?= trarc_e(trarc_old($old, 'city')) ?>" required>
                <?= trarc_error($errors, 'city') ?>
            </div>
            <div class="field <?= trarc_error_class($errors, 'state') ?>">
                <label for="state">State *</label>
                <input type="text" id="state" name="state" maxlength="20" value="<?= trarc_e(trarc_old($old, 'state')) ?>" required>
                <?= trarc_error($errors, 'state') ?>
            </div>
            <div class="field <?= trarc_error_class($errors, 'zip') ?>">
                <label for="zip">Zip Code *</label>
                <input type="text" id="zip" name="zip" pattern="^\d{5}(-\d{4})?$" title="5-digit US zip, optionally with a 4-digit extension" value="<?= trarc_e(trarc_old($old, 'zip')) ?>" required>
                <?= trarc_error($errors, 'zip') ?>
            </div>
        </div>

        <div class="form-row three-col">
            <div class="field">
                <label for="home_phone">Home Phone</label>
                <input type="tel" id="home_phone" name="home_phone" value="<?= trarc_e(trarc_old($old, 'home_phone')) ?>">
            </div>
            <div class="field">
                <label for="work_phone">Work Phone</label>
                <input type="tel" id="work_phone" name="work_phone" value="<?= trarc_e(trarc_old($old, 'work_phone')) ?>">
            </div>
            <div class="field">
                <label for="cell_phone">Cell Phone</label>
                <input type="tel" id="cell_phone" name="cell_phone" value="<?= trarc_e(trarc_old($old, 'cell_phone')) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="field <?= trarc_error_class($errors, 'email') ?>">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= trarc_e(trarc_old($old, 'email')) ?>">
                <?= trarc_error($errors, 'email') ?>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>License &amp; Membership Type</legend>

        <div class="form-row three-col">
            <div class="field <?= trarc_error_class($errors, 'license_class') ?>">
                <label for="license_class">License Class *</label>
                <input type="text" id="license_class" name="license_class" placeholder="e.g. Technician, General, Amateur Extra" value="<?= trarc_e(trarc_old($old, 'license_class')) ?>" required>
                <?= trarc_error($errors, 'license_class') ?>
            </div>
            <div class="field">
                <label for="license_expires">License Expires</label>
                <input type="date" id="license_expires" name="license_expires" value="<?= trarc_e(trarc_old($old, 'license_expires')) ?>">
            </div>
            <div class="field">
                <label for="birthday">Birthday</label>
                <input type="date" id="birthday" name="birthday" value="<?= trarc_e(trarc_old($old, 'birthday')) ?>">
            </div>
        </div>

        <div class="field radio-group <?= trarc_error_class($errors, 'membership_type') ?>">
            <span class="group-label">I am applying for membership type: *</span>
            <label class="inline"><input type="radio" name="membership_type" value="Regular" <?= trarc_checked($old, 'membership_type', 'Regular') ?> required> Regular</label>
            <label class="inline"><input type="radio" name="membership_type" value="Associate" <?= trarc_checked($old, 'membership_type', 'Associate') ?>> Associate</label>
            <label class="inline"><input type="radio" name="membership_type" value="Student" <?= trarc_checked($old, 'membership_type', 'Student') ?>> Student</label>
            <?= trarc_error($errors, 'membership_type') ?>
        </div>

        <div class="field checkbox-group">
            <span class="group-label">I am a member of:</span>
            <label class="inline"><input type="checkbox" id="arrl_member" name="arrl_member" value="1" <?= !empty($old['arrl_member']) ? 'checked' : '' ?>> ARRL</label>
            <label class="inline"><input type="checkbox" name="ares_member" value="1" <?= !empty($old['ares_member']) ? 'checked' : '' ?>> ARES</label>
        </div>

        <div class="form-row" id="arrl-expires-row">
            <div class="field">
                <label for="arrl_expires">ARRL Membership Expires</label>
                <input type="text" id="arrl_expires" name="arrl_expires" placeholder="MM/YYYY" value="<?= trarc_e(trarc_old($old, 'arrl_expires')) ?>">
            </div>
        </div>

        <div class="field radio-group <?= trarc_error_class($errors, 'participate') ?>">
            <span class="group-label">Will you participate in club activities? *</span>
            <label class="inline"><input type="radio" name="participate" value="Yes" <?= trarc_checked($old, 'participate', 'Yes') ?> required> Yes</label>
            <label class="inline"><input type="radio" name="participate" value="No" <?= trarc_checked($old, 'participate', 'No') ?>> No</label>
            <?= trarc_error($errors, 'participate') ?>
        </div>
    </fieldset>

    <fieldset>
        <legend>Club Participation &amp; Interests</legend>
        <div class="field">
            <label for="interests">List your interests in Amateur Radio</label>
            <textarea id="interests" name="interests" rows="3"><?= trarc_e(trarc_old($old, 'interests')) ?></textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>Additional Family Members Who Are or Want to Be Members of TRARC</legend>
        <?php for ($i = 1; $i <= 2; $i++): ?>
            <div class="family-row">
                <p class="family-row-label">Family member <?= $i ?></p>
                <div class="form-row four-col">
                    <div class="field">
                        <label for="family_<?= $i ?>_name">Name</label>
                        <input type="text" id="family_<?= $i ?>_name" name="family[<?= $i ?>][name]" value="<?= trarc_e($old['family'][$i]['name'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="family_<?= $i ?>_callsign">Call Sign</label>
                        <input type="text" id="family_<?= $i ?>_callsign" name="family[<?= $i ?>][callsign]" value="<?= trarc_e($old['family'][$i]['callsign'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="family_<?= $i ?>_license_class">License Class</label>
                        <input type="text" id="family_<?= $i ?>_license_class" name="family[<?= $i ?>][license_class]" value="<?= trarc_e($old['family'][$i]['license_class'] ?? '') ?>">
                    </div>
                    <div class="field radio-group">
                        <span class="group-label">ARRL Member</span>
                        <label class="inline"><input type="radio" name="family[<?= $i ?>][arrl]" value="Yes" <?= (($old['family'][$i]['arrl'] ?? '') === 'Yes') ? 'checked' : '' ?>> Yes</label>
                        <label class="inline"><input type="radio" name="family[<?= $i ?>][arrl]" value="No" <?= (($old['family'][$i]['arrl'] ?? '') === 'No') ? 'checked' : '' ?>> No</label>
                    </div>
                </div>
            </div>
        <?php endfor; ?>
    </fieldset>

    <fieldset>
        <legend>Agreement / Signature</legend>

        <p class="agreement-statement">
            Applicant agrees to conduct themselves in accordance with the TRARC Constitution when
            participating in any TRARC function, meeting, or sanctioned activity.
        </p>

        <div class="field checkbox-group <?= trarc_error_class($errors, 'agree') ?>">
            <label class="inline">
                <input type="checkbox" name="agree" value="1" <?= !empty($old['agree']) ? 'checked' : '' ?> required>
                I agree to the statement above
            </label>
            <?= trarc_error($errors, 'agree') ?>
        </div>

        <div class="field <?= trarc_error_class($errors, 'signature') ?>">
            <label for="signature">Typed Signature (full legal name) *</label>
            <input type="text" id="signature" name="signature" value="<?= trarc_e(trarc_old($old, 'signature')) ?>" required>
            <?= trarc_error($errors, 'signature') ?>
            <p class="field-hint">The submission date and time will be recorded automatically.</p>
        </div>

        <p class="disclaimer-text">
            The Officers and Members of TRARC will NOT assume parental responsibilities for any minor
            member at any time. Therefore, all minors must be accompanied by their parent or guardian
            while observing, attending, or participating in any TRARC function, meeting, or sanctioned
            activity.
        </p>

        <div class="field radio-group <?= trarc_error_class($errors, 'under18') ?>">
            <span class="group-label">Are you under 18? *</span>
            <label class="inline"><input type="radio" id="under18_yes" name="under18" value="Yes" <?= trarc_checked($old, 'under18', 'Yes') ?> required> Yes</label>
            <label class="inline"><input type="radio" id="under18_no" name="under18" value="No" <?= trarc_checked($old, 'under18', 'No') ?>> No</label>
            <?= trarc_error($errors, 'under18') ?>
        </div>

        <div class="form-row" id="parent-signature-row">
            <div class="field <?= trarc_error_class($errors, 'parent_signature') ?>">
                <label for="parent_signature">Parent/Guardian Typed Signature</label>
                <input type="text" id="parent_signature" name="parent_signature" value="<?= trarc_e(trarc_old($old, 'parent_signature')) ?>">
                <?= trarc_error($errors, 'parent_signature') ?>
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
})();
</script>
