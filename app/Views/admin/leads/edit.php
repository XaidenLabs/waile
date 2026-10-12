<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
<div class="col-xl-8">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= site_url('panel/leads/'.$lead['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="fw-bold mb-0"><?= esc(lang('Leads.editLead')) ?></h5>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">

        <?php if ($errors = session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3 small"><?php foreach($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
        </div>
        <?php endif ?>

        <?= form_open('/panel/leads/update/'.$lead['id']) ?>
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Leads.fFullName')) ?></label>
                <input type="text" name="name" class="form-control"
                       value="<?= esc(old('name', $lead['name'])) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Leads.fPhoneShort')) ?> <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                    <input type="text" name="phone" class="form-control"
                           value="<?= esc(old('phone', $lead['phone'])) ?>" required>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Leads.fEmail')) ?></label>
                <input type="email" name="email" class="form-control"
                       value="<?= esc(old('email', $lead['email'])) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small"><?= esc(lang('Leads.fSource')) ?></label>
                <select name="source" class="form-select">
                    <?php foreach($sources as $src): ?>
                    <option value="<?= $src ?>" <?= old('source', $lead['source']) === $src ? 'selected':'' ?>>
                        <?= ucfirst($src) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small"><?= esc(lang('Leads.fStatus')) ?></label>
                <select name="status" class="form-select">
                    <?php foreach($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= old('status', $lead['status']) === $s ? 'selected':'' ?>>
                        <?= ucfirst($s) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Leads.fTags')) ?> <?= esc(lang('Leads.fTagsHint')) ?></label>
                <input type="text" name="tags" id="tagsInput" class="form-control"
                       value="<?= esc(old('tags', implode(', ', $tags))) ?>">
                <div id="tagPreview" class="mt-1 d-flex flex-wrap gap-1">
                    <?php foreach($tags as $t): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= esc($t) ?></span>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Leads.fScore')) ?> <?= esc(lang('Leads.fScoreRange')) ?></label>
                <div class="d-flex align-items-center gap-2">
                    <input type="range" name="score" class="form-range flex-grow-1"
                           min="0" max="100" value="<?= old('score', $lead['score']) ?>"
                           oninput="document.getElementById('scoreVal').textContent=this.value">
                    <span id="scoreVal" class="badge bg-secondary u-minw-36">
                        <?= old('score', $lead['score']) ?>
                    </span>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold small"><?= esc(lang('Leads.fNotes')) ?></label>
                <textarea name="notes" class="form-control" rows="2"><?= esc(old('notes', $lead['notes'])) ?></textarea>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-2"></i><?= esc(lang('Leads.saveChanges')) ?>
                </button>
                <a href="<?= site_url('panel/leads/'.$lead['id']) ?>" class="btn btn-outline-secondary"><?= esc(lang('Leads.cancel')) ?></a>
            </div>
        </div>
        <?= form_close() ?>
    </div>
</div>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/leads-edit.js') ?>"></script>
<?= $this->endSection() ?>
