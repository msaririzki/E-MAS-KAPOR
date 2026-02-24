

{{-- ═══ Modal Edit User ═══ --}}
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header" style="background: linear-gradient(to right, #B91C1C, #991B1B); padding: 20px 24px; border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <h3 style="color: #ffffff; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                <i class="ri-user-settings-line"></i> Edit Akun Pengguna
            </h3>
            <button class="modal-close" onclick="closeModal('editModal')" style="color: rgba(255,255,255,0.8); background: rgba(255,255,255,0.1); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: all 0.2s;">&times;</button>
        </div>
        <form method="POST" id="editForm">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>NRP / NIP <span class="required">*</span></label>
                        <input type="text" name="nrp_nip" id="edit_nrp_nip" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>No. HP (WhatsApp)</label>
                    <input type="text" name="phone" id="edit_phone" class="form-input" placeholder="Contoh: 08123456789">
                    <input type="hidden" name="email" id="edit_email"> {{-- Hidden for logic compatibility --}}
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Kata Sandi (Kosongkan jika tidak ganti)</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="edit_password" class="form-input">
                            <button type="button" class="password-toggle" onclick="togglePassword('edit_password', this)">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Peran <span class="required">*</span></label>
                        <div class="custom-select form-input" onclick="toggleDropdown(this)" id="editRoleSelect">
                            <div class="select-trigger">
                                <span id="edit_role_label">— Pilih Peran —</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                            <div class="custom-options" style="background: #fff !important;">
                                <div class="options-scroll">
                                    <div class="option" onclick="setSelectValue('edit_role', '', '— Pilih Peran —', this)">— Pilih Peran —</div>
                                    @foreach($roles as $role)
                                        <div class="option" onclick="setSelectValue('edit_role', '{{ $role->name }}', '{{ ucfirst($role->name) }}', this)"
                                             data-value="{{ $role->name }}">
                                            {{ ucfirst($role->name) }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="role" id="edit_role" required>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin-top:10px;">
                    <label class="checkbox-container">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <span class="checkmark"></span>
                        <span style="font-weight:600;">Status Aktif</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer" style="background: #F9FAFB; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn-modal btn-modal-outline" onclick="closeModal('editModal')">Batal</button>
                <button type="submit" class="btn-modal btn-modal-maroon"><i class="ri-check-line"></i> Perbarui Data</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ Modal Import User ═══ --}}
<div class="modal-overlay" id="importModal">
    <div class="modal">
        <div class="modal-header" style="background: linear-gradient(to right, #111827, #1f2937); padding: 20px 24px; border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <h3 style="color: #ffffff; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                <i class="ri-file-upload-line" style="color: #f87171;"></i> Import Data CSV
            </h3>
            <button class="modal-close" onclick="closeModal('importModal')" style="color: rgba(255,255,255,0.8); background: rgba(255,255,255,0.1); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: all 0.2s;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div style="background: #FFF7ED; border: 1px solid #FFEDD5; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                    <div style="display: flex; gap: 12px;">
                        <i class="ri-information-line" style="font-size: 20px; color: #F97316;"></i>
                        <div>
                            <h4 style="font-size: 14px; font-weight: 700; color: #9A3412; margin-bottom: 4px;">Instruksi Import</h4>
                            <p style="font-size: 13px; color: #C2410C; line-height: 1.5;"> Pastikan format CSV sesuai dengan templat. Impor ini hanya untuk akun administratif (Superadmin, Admin, Admin Satker).</p>
                            <a href="{{ route('admin.users.template') }}" class="btn-template" style="display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; color: #B91C1C; font-weight: 700; text-decoration: none; font-size: 13px;">
                                <i class="ri-download-cloud-2-line"></i> Unduh Templat CSV
                            </a>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Pilih File CSV <span class="required">*</span></label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="file" id="import_file" accept=".csv" required style="display: none;" onchange="updateFileName(this)">
                        <label for="import_file" class="file-upload-label">
                            <i class="ri-upload-2-fill"></i>
                            <span id="file_name_label">Klik untuk pilih file atau seret ke sini</span>
                            <small>Format: .CSV (Maks. 2MB)</small>
                        </label>
                    </div>
                </div>

                <div style="margin-top: 16px; padding: 12px; background: #F9FAFB; border-radius: 10px; border: 1px dashed #E5E7EB;">
                    <p style="font-size: 12px; color: #6B7280; font-weight: 500;">
                        <strong>Kolom yang dibutuhkan:</strong><br>
                        nrp_nip, name, phone, role, password
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-modal-outline" onclick="closeModal('importModal')">Batal</button>
                <button type="submit" class="btn-modal btn-modal-maroon"><i class="ri-upload-line"></i> Mulai Import</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ Modal Konfirmasi Hapus ═══ --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-body" style="padding: 32px 24px; text-align: center;">
            <div style="width: 64px; height: 64px; background: #FEF2F2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="ri-error-warning-fill" style="font-size: 32px; color: #EF4444;"></i>
            </div>
            <h3 style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px;">Hapus Akun?</h3>
            <p style="font-size: 14px; color: #6B7280; line-height: 1.5; margin-bottom: 24px;">
                Apakah Anda yakin ingin menghapus akun <strong id="delete_user_name"></strong>? Data yang telah dihapus tidak dapat dikembalikan.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn-modal btn-modal-outline" style="flex: 1;" onclick="closeModal('deleteModal')">Batal</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-modal btn-modal-maroon" style="flex: 1; background: #EF4444; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);">
                        Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Modal overrides for consistency */
    .modal-overlay {
        display: flex; position: fixed; inset: 0; background: rgba(0,0,0,0);
        z-index: 2000; align-items: center; justify-content: center;
        backdrop-filter: blur(0px);
        visibility: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
    }
    .modal-overlay.open { 
        visibility: visible;
        background: rgba(0,0,0,.5);
        backdrop-filter: blur(4px);
        pointer-events: auto;
    }
    .modal {
        background: #fff; border-radius: 16px; width: 95%; max-width: 650px;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); 
        overflow: visible;
        transform: scale(0.9) translateY(20px);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .modal-overlay.open .modal {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
    .modal-header { padding: 16px 20px; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 24px; }
    .modal-footer { padding: 20px 24px; border-top: 1px solid #F3F4F6; display: flex; justify-content: flex-end; gap: 12px; }
    
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; }
    .form-group { margin-bottom: 16px; }

    /* File Upload Styling */
    .file-upload-wrapper { width: 100%; }
    .file-upload-label {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 30px 20px; border: 2px dashed #E5E7EB; border-radius: 12px;
        background: #F9FAFB; cursor: pointer; transition: all 0.2s ease;
        text-align: center; gap: 8px;
    }
    .file-upload-label:hover { border-color: #B91C1C; background: #FEF2F2; }
    .file-upload-label i { font-size: 32px; color: #9CA3AF; transition: color 0.2s; }
    .file-upload-label:hover i { color: #B91C1C; }
    .file-upload-label span { font-size: 14px; color: #4B5563; font-weight: 600; }
    .file-upload-label small { font-size: 11px; color: #9CA3AF; }

    /* Button Styling */
    .btn-modal {
        padding: 0 24px;
        height: 44px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        border: none;
    }
    .btn-modal-maroon {
        background: #B91C1C;
        color: #fff;
        box-shadow: 0 4px 6px -1px rgba(185, 28, 28, 0.2);
    }
    .btn-modal-maroon:hover {
        background: #991B1B;
        transform: translateY(-1px);
        box-shadow: 0 6px 10px -1px rgba(185, 28, 28, 0.3);
    }
    .btn-modal-outline {
        background: #fff;
        color: #374151;
        border: 1px solid #E5E7EB;
    }
    .btn-modal-outline:hover {
        background: #F9FAFB;
        border-color: #D1D5DB;
        color: #111827;
    }

    /* Password Toggle Styling */
    .password-wrapper { position: relative; display: flex; align-items: center; }
    .password-toggle {
        position: absolute; right: 10px;
        background: none; border: none;
        color: #9CA3AF; cursor: pointer;
        padding: 5px; font-size: 18px;
        display: flex; align-items: center; justify-content: center;
        transition: color 0.2s;
    }
    .password-toggle:hover { color: #4B5563; }

    @media (max-width: 640px) {
        .form-row { grid-template-columns: 1fr; gap: 16px; }
        .modal { width: 92%; max-width: none; margin: 10px; }
    }
</style>

<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('ri-eye-line');
            icon.classList.add('ri-eye-off-line');
        } else {
            input.type = 'password';
            icon.classList.remove('ri-eye-off-line');
            icon.classList.add('ri-eye-line');
        }
    }
    function updateFileName(input) {
        const label = document.getElementById('file_name_label');
        if (input.files && input.files.length > 0) {
            label.innerText = input.files[0].name;
            label.style.color = '#B91C1C';
        } else {
            label.innerText = 'Klik untuk pilih file atau seret ke sini';
            label.style.color = '#4B5563';
        }
    }
</script>
