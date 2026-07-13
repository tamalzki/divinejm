{{-- Manage Packers modal — add/rename/remove packer names.
     Renames cascade to existing packer_packs/session_logs so reports stay attributed correctly.
     Removals are soft (deactivate) so historical sheets keep working. --}}
<style>
    .mp-row { display: flex; align-items: center; gap: .5rem; margin-bottom: .5rem; }
    .mp-row input.form-control { font-size: .82rem; }
    .mp-remove-btn {
        background: none; border: 1px solid var(--border); border-radius: 5px;
        color: #dc2626; width: 32px; height: 32px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
    }
    .mp-remove-btn:hover { background: #fef2f2; border-color: #dc2626; }
    .mp-add-btn {
        display: inline-flex; align-items: center; gap: .35rem;
        background: none; border: 1px dashed var(--accent); color: var(--accent);
        border-radius: 6px; padding: .4rem .75rem; font-size: .8rem; font-weight: 600; cursor: pointer;
    }
    .mp-add-btn:hover { background: var(--accent-light); }
</style>

<div class="modal fade" id="managePackersModal" tabindex="-1" aria-labelledby="managePackersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border)">
            <div class="modal-header">
                <h5 class="modal-title" id="managePackersModalLabel">
                    <i class="bi bi-people me-2" style="color:var(--accent)"></i>Edit Packers
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('packers.sync') }}" id="managePackersForm">
                @csrf
                <div class="modal-body">
                    <p style="font-size:.76rem;color:var(--text-secondary);margin-bottom:.85rem">
                        Rename or remove packers below, or add new ones. Renames update existing reports to the new name;
                        removed packers stop appearing on new sheets but stay visible on reports they already packed.
                    </p>
                    <div id="mpRowsContainer">
                        @foreach($packersForModal as $packer)
                            <div class="mp-row" data-packer-row>
                                <input type="hidden" data-field="id" value="{{ $packer->id }}">
                                <input type="text" class="form-control form-control-sm" data-field="name" value="{{ $packer->name }}" maxlength="64" required>
                                <button type="button" class="mp-remove-btn" title="Remove" onclick="removePackerRow(this)">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="mp-add-btn" onclick="addPackerRow()">
                        <i class="bi bi-plus-lg"></i> Add packer
                    </button>
                    <div id="mpHiddenInputs"></div>
                </div>
                <div class="modal-footer border-top" style="background:var(--bg-page)">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm" id="mpSaveBtn">
                        <i class="bi bi-check-lg me-1"></i>Save Packers
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var mpRemovedIds = [];

function addPackerRow() {
    var container = document.getElementById('mpRowsContainer');
    var row = document.createElement('div');
    row.className = 'mp-row';
    row.setAttribute('data-packer-row', '');
    row.innerHTML =
        '<input type="hidden" data-field="id" value="">' +
        '<input type="text" class="form-control form-control-sm" data-field="name" value="" maxlength="64" placeholder="New packer name" required>' +
        '<button type="button" class="mp-remove-btn" title="Remove" onclick="removePackerRow(this)"><i class="bi bi-x-lg"></i></button>';
    container.appendChild(row);
    row.querySelector('input[data-field="name"]').focus();
}

function removePackerRow(btn) {
    var row = btn.closest('[data-packer-row]');
    var idInput = row.querySelector('input[data-field="id"]');
    if (idInput && idInput.value) {
        mpRemovedIds.push(idInput.value);
    }
    row.remove();
}

document.getElementById('managePackersForm').addEventListener('submit', function() {
    var hidden = document.getElementById('mpHiddenInputs');
    hidden.innerHTML = '';

    var rows = document.querySelectorAll('#mpRowsContainer [data-packer-row]');
    rows.forEach(function(row, i) {
        var id = row.querySelector('input[data-field="id"]').value;
        var name = row.querySelector('input[data-field="name"]').value;

        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'packers[' + i + '][id]';
        idInput.value = id;
        hidden.appendChild(idInput);

        var nameInput = document.createElement('input');
        nameInput.type = 'hidden';
        nameInput.name = 'packers[' + i + '][name]';
        nameInput.value = name;
        hidden.appendChild(nameInput);
    });

    mpRemovedIds.forEach(function(id, i) {
        var removedInput = document.createElement('input');
        removedInput.type = 'hidden';
        removedInput.name = 'removed_ids[' + i + ']';
        removedInput.value = id;
        hidden.appendChild(removedInput);
    });
});
</script>
