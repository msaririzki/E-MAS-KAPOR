
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
                        <label>Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-input" required>
                    </div>
                    <div class="form-group" id="edit_email_group">
                        <label>Gmail <span class="required">*</span></label>
                        <input type="email" name="email" id="edit_email" class="form-input" placeholder="Contoh: superadmin.kapor@gmail.com">
                    </div>
                    <div class="form-group" id="edit_nrp_group" style="display: none;">
                        <label>NRP / NIP <span class="required">*</span></label>
                        <input type="text" name="nrp_nip" id="edit_nrp_nip" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label>No. HP (WhatsApp)</label>
                    <input type="text" name="phone" id="edit_phone" class="form-input" placeholder="Contoh: 08123456789">
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
                        <div style="margin-top: 8px; font-size: 12px; color: #6B7280;">
                            Jika diubah, password minimal 8 karakter dan harus memakai huruf besar, huruf kecil, angka, dan simbol.
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
                                        <div class="option" onclick="setSelectValue('edit_role', '{{ $role->name }}', '{{ \Illuminate\Support\Str::headline($role->name) }}', this)"
                                             data-value="{{ $role->name }}">
                                            {{ \Illuminate\Support\Str::headline($role->name) }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="role" id="edit_role" required>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Satker (Untuk Akun Administratif)</label>
                        <div class="custom-select form-input" onclick="toggleDropdown(this)" id="editSatkerSelect">
                            <div class="select-trigger">
                                <span id="edit_satker_label">— Pilih Satker —</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                            <div class="custom-options" style="background: #fff !important;">
                                <div class="options-scroll">
                                    <div class="option" onclick="setSelectValue('edit_satker', '', '— Kosongkan —', this)" data-value="">— Kosongkan —</div>
                                    @foreach($satkers as $satker)
                                        <div class="option" onclick="setSelectValue('edit_satker', '{{ $satker->id }}', '{{ $satker->name }}', this)"
                                             data-value="{{ $satker->id }}">
                                            {{ $satker->name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="satker_id" id="edit_satker">
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

{{-- ═══ Modal Buat Admin Satker Massal ═══ --}}
<div class="modal-overlay" id="bulkAdminSatkerModal">
    <div class="modal" style="max-width: 520px; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.18);">
        <form method="POST" action="{{ route('admin.users.bulk-admin-satker') }}" onsubmit="showGenerateLoading(this)">
            @csrf

            {{-- Hero Header --}}
            <div style="background: linear-gradient(135deg, #1E1B4B 0%, #312E81 50%, #4338CA 100%); padding: 32px 28px 28px; position: relative; overflow: hidden;">
                {{-- Dekorasi lingkaran blur di background --}}
                <div style="position:absolute;top:-30px;right:-30px;width:140px;height:140px;background:rgba(255,255,255,0.06);border-radius:50%;"></div>
                <div style="position:absolute;bottom:-20px;left:-20px;width:100px;height:100px;background:rgba(255,255,255,0.04);border-radius:50%;"></div>

                {{-- Tombol tutup --}}
                <button type="button" onclick="closeModal('bulkAdminSatkerModal')"
                    style="position:absolute;top:16px;right:16px;width:30px;height:30px;border-radius:8px;border:none;background:rgba(255,255,255,0.12);color:rgba(255,255,255,0.8);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;transition:background 0.2s;z-index:1;"
                    onmouseover="this.style.background='rgba(255,255,255,0.22)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.12)'">&times;</button>

                {{-- Ikon utama --}}
                <div style="position:relative;display:inline-block;margin-bottom:16px;">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,rgba(255,255,255,0.25),rgba(255,255,255,0.1));border-radius:18px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);box-shadow:0 8px 24px rgba(0,0,0,0.2);">
                        <i class="ri-shield-user-line" style="font-size:30px;color:#fff;"></i>
                    </div>
                    <div style="position:absolute;bottom:-4px;right:-4px;width:22px;height:22px;background:#10B981;border-radius:50%;border:2px solid #312E81;display:flex;align-items:center;justify-content:center;">
                        <i class="ri-flashlight-line" style="font-size:11px;color:#fff;"></i>
                    </div>
                </div>

                <h3 style="color:#fff;font-size:19px;font-weight:800;margin-bottom:6px;letter-spacing:-0.3px;">Buat Admin Satker Massal</h3>
                <p style="color:rgba(255,255,255,0.65);font-size:13px;margin:0;line-height:1.5;">
                    Generate akun admin untuk semua satker sekaligus secara otomatis.
                </p>
            </div>

            {{-- Konten --}}
            <div style="padding: 24px 28px; background: #fff;">

                {{-- Step cards --}}
                <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">

                    <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 16px;background:#F5F3FF;border:1px solid #DDD6FE;border-radius:12px;">
                        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#7C3AED,#6D28D9);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 8px rgba(109,40,217,0.25);">
                            <i class="ri-user-add-line" style="font-size:17px;color:#fff;"></i>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#4C1D95;margin-bottom:2px;">Satu Akun per Satker</div>
                            <div style="font-size:12px;color:#5B21B6;line-height:1.5;">Satker yang sudah punya admin satker akan otomatis dilewati.</div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;">
                        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 8px rgba(16,185,129,0.25);">
                            <i class="ri-key-2-line" style="font-size:17px;color:#fff;"></i>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#065F46;margin-bottom:2px;">Password Otomatis</div>
                            <div style="font-size:12px;color:#047857;line-height:1.5;">Gmail & password dibuat otomatis. Hasil ditampilkan <strong>sekali</strong> setelah proses selesai.</div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 16px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;">
                        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#3B82F6,#2563EB);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 8px rgba(59,130,246,0.25);">
                            <i class="ri-edit-line" style="font-size:17px;color:#fff;"></i>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#1E3A8A;margin-bottom:2px;">Bisa Diubah Kapan Saja</div>
                            <div style="font-size:12px;color:#1D4ED8;line-height:1.5;">Password awal dapat diganti melalui menu <strong>Edit Pengguna</strong> kapan pun.</div>
                        </div>
                    </div>
                </div>

                {{-- Peringatan --}}
                <div style="display:flex;align-items:flex-start;gap:10px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:12px 14px;">
                    <i class="ri-error-warning-line" style="font-size:16px;color:#D97706;flex-shrink:0;margin-top:1px;"></i>
                    <p style="font-size:12px;color:#92400E;line-height:1.5;margin:0;">
                        Proses ini tidak dapat dibatalkan. Pastikan data satker sudah lengkap sebelum melanjutkan.
                    </p>
                </div>
            </div>

            {{-- Footer Tombol --}}
            <div style="padding:18px 28px;background:#F9FAFB;border-top:1px solid #F3F4F6;display:flex;gap:12px;">
                <button type="button" onclick="closeModal('bulkAdminSatkerModal')"
                    style="flex:1;padding:11px 20px;border:1.5px solid #D1D5DB;border-radius:10px;background:#fff;color:#374151;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit;transition:all 0.2s;"
                    onmouseover="this.style.background='#F3F4F6';this.style.borderColor='#9CA3AF';"
                    onmouseout="this.style.background='#fff';this.style.borderColor='#D1D5DB';">
                    <i class="ri-close-line" style="font-size:15px;"></i> Batal
                </button>
                <button type="submit" id="btnGenerateSatker"
                    style="flex:2;padding:11px 20px;border:none;border-radius:10px;background:linear-gradient(135deg,#4F46E5,#4338CA);color:#fff;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;font-family:inherit;box-shadow:0 4px 12px rgba(79,70,229,0.35);transition:all 0.2s;"
                    onmouseover="if(!this.disabled){this.style.background='linear-gradient(135deg,#4338CA,#3730A3)';this.style.boxShadow='0 6px 16px rgba(79,70,229,0.45)';}"
                    onmouseout="if(!this.disabled){this.style.background='linear-gradient(135deg,#4F46E5,#4338CA)';this.style.boxShadow='0 4px 12px rgba(79,70,229,0.35)';}">
                    <span id="btnGenerateText" style="display:flex;align-items:center;gap:8px;"><i class="ri-flashlight-line" style="font-size:15px;"></i> Generate Sekarang</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ Modal Hapus Admin Satker Massal ═══ --}}
<div class="modal-overlay" id="bulkDeleteAdminSatkerModal">
    <div class="modal" style="max-width: 440px; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.18);">
        <form id="bulkDeleteAdminSatkerForm" method="POST" action="{{ route('admin.users.bulk-delete-admin-satker') }}">
            @csrf
            @method('DELETE')

            {{-- Header bahaya --}}
            <div style="background: linear-gradient(135deg, #450A0A 0%, #7F1D1D 50%, #991B1B 100%); padding: 32px 28px 24px; position: relative; overflow: hidden;">
                <div style="position:absolute;top:-40px;right:-40px;width:160px;height:160px;background:rgba(255,255,255,0.04);border-radius:50%;"></div>
                <div style="position:absolute;bottom:-20px;left:-20px;width:100px;height:100px;background:rgba(255,255,255,0.03);border-radius:50%;"></div>

                <button type="button" onclick="closeModal('bulkDeleteAdminSatkerModal')"
                    style="position:absolute;top:16px;right:16px;width:30px;height:30px;border-radius:8px;border:none;background:rgba(255,255,255,0.12);color:rgba(255,255,255,0.8);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;transition:background 0.2s;z-index:1;"
                    onmouseover="this.style.background='rgba(255,255,255,0.22)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.12)'">&times;</button>

                <div style="position:relative;display:inline-block;margin-bottom:16px;">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,rgba(255,255,255,0.18),rgba(255,255,255,0.06));border-radius:18px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.15);box-shadow:0 8px 24px rgba(0,0,0,0.3);">
                        <i class="ri-user-unfollow-line" style="font-size:30px;color:#FCA5A5;"></i>
                    </div>
                    <div style="position:absolute;bottom:-4px;right:-4px;width:22px;height:22px;background:#EF4444;border-radius:50%;border:2px solid #7F1D1D;display:flex;align-items:center;justify-content:center;">
                        <i class="ri-close-line" style="font-size:13px;color:#fff;"></i>
                    </div>
                </div>

                <h3 style="color:#fff;font-size:19px;font-weight:800;margin-bottom:6px;letter-spacing:-0.3px;">Hapus Semua Admin Satker?</h3>
                <p style="color:rgba(255,255,255,0.6);font-size:13px;margin:0;line-height:1.5;">
                    Seluruh akun dengan peran <strong style="color:#FCA5A5;">admin satker</strong> akan dihapus permanen.
                </p>
            </div>

            {{-- Konten --}}
            <div style="padding: 20px 24px; background: #fff;">

                {{-- Daftar dampak --}}
                <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;">
                        <i class="ri-delete-bin-6-line" style="font-size:16px;color:#DC2626;flex-shrink:0;"></i>
                        <span style="font-size:12.5px;color:#991B1B;font-weight:500;">Semua akun admin satker dihapus permanen</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;">
                        <i class="ri-shield-cross-line" style="font-size:16px;color:#DC2626;flex-shrink:0;"></i>
                        <span style="font-size:12.5px;color:#991B1B;font-weight:500;">Akses admin satker ke sistem langsung dicabut</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;">
                        <i class="ri-checkbox-circle-line" style="font-size:16px;color:#16A34A;flex-shrink:0;"></i>
                        <span style="font-size:12.5px;color:#166534;font-weight:500;">Data personel & satker tidak terpengaruh</span>
                    </div>
                </div>

                {{-- Warning keras --}}
                <div style="display:flex;align-items:flex-start;gap:10px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:12px 14px;">
                    <i class="ri-alert-line" style="font-size:16px;color:#EA580C;flex-shrink:0;margin-top:1px;"></i>
                    <p style="font-size:12px;color:#7C2D12;line-height:1.5;margin:0;font-weight:500;">
                        Tindakan ini <strong>tidak dapat dibatalkan</strong>. Gunakan fitur <em>Generate Massal</em> untuk membuat ulang akun setelahnya.
                    </p>
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding:18px 24px;background:#F9FAFB;border-top:1px solid #F3F4F6;display:flex;gap:12px;">
                <button type="button" onclick="closeModal('bulkDeleteAdminSatkerModal')"
                    style="flex:1;padding:11px 20px;border:1.5px solid #D1D5DB;border-radius:10px;background:#fff;color:#374151;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit;transition:all 0.2s;"
                    onmouseover="this.style.background='#F3F4F6';this.style.borderColor='#9CA3AF';"
                    onmouseout="this.style.background='#fff';this.style.borderColor='#D1D5DB';">
                    <i class="ri-close-line" style="font-size:15px;"></i> Batal
                </button>
                <button type="submit"
                    style="flex:2;padding:11px 20px;border:none;border-radius:10px;background:linear-gradient(135deg,#DC2626,#B91C1C);color:#fff;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;font-family:inherit;box-shadow:0 4px 12px rgba(220,38,38,0.35);transition:all 0.2s;"
                    onmouseover="this.style.background='linear-gradient(135deg,#B91C1C,#991B1B)';this.style.boxShadow='0 6px 16px rgba(220,38,38,0.45)';"
                    onmouseout="this.style.background='linear-gradient(135deg,#DC2626,#B91C1C)';this.style.boxShadow='0 4px 12px rgba(220,38,38,0.35)';">
                    <i class="ri-user-unfollow-line" style="font-size:15px;"></i> Ya, Hapus Semua
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ Modal Konfirmasi Hapus ═══ --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width: 420px; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')

            {{-- Bagian atas dengan ikon peringatan --}}
            <div style="background: linear-gradient(135deg, #FEF2F2 0%, #FFF5F5 100%); padding: 36px 32px 24px; text-align: center; border-bottom: 1px solid #FEE2E2; position: relative;">
                {{-- Tombol tutup --}}
                <button type="button" onclick="closeModal('deleteModal')"
                    style="position: absolute; top: 16px; right: 16px; width: 30px; height: 30px; border-radius: 8px; border: none; background: rgba(239,68,68,0.1); color: #EF4444; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; line-height: 1; transition: background 0.2s;">
                    &times;
                </button>

                {{-- Ikon peringatan berlapis --}}
                <div style="position: relative; display: inline-block; margin-bottom: 20px;">
                    <div style="width: 72px; height: 72px; background: linear-gradient(135deg, #FCA5A5, #F87171); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(239,68,68,0.25);">
                        <i class="ri-delete-bin-6-line" style="font-size: 34px; color: #fff;"></i>
                    </div>
                    <div style="position: absolute; bottom: -2px; right: -2px; width: 24px; height: 24px; background: #EF4444; border-radius: 50%; border: 2px solid #FEF2F2; display: flex; align-items: center; justify-content: center;">
                        <i class="ri-alert-line" style="font-size: 12px; color: #fff;"></i>
                    </div>
                </div>

                <h3 style="font-size: 20px; font-weight: 800; color: #111827; margin-bottom: 8px; letter-spacing: -0.3px;">Hapus Akun?</h3>
                <p style="font-size: 13.5px; color: #6B7280; line-height: 1.6; margin: 0;">
                    Anda akan menghapus akun
                    <strong id="delete_user_name" style="color: #111827; font-weight: 700;"></strong>.
                    <br>Tindakan ini <strong style="color: #EF4444;">tidak dapat dibatalkan</strong>.
                </p>
            </div>

            {{-- Bagian info peringatan --}}
            <div style="padding: 16px 24px; background: #fff; border-bottom: 1px solid #F3F4F6;">
                <div style="display: flex; align-items: flex-start; gap: 10px; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 10px; padding: 12px 14px;">
                    <i class="ri-information-line" style="font-size: 16px; color: #D97706; flex-shrink: 0; margin-top: 1px;"></i>
                    <p style="font-size: 12.5px; color: #92400E; line-height: 1.5; margin: 0;">
                        Data personel yang terhubung dengan akun ini akan terputus. Pastikan Anda sudah memindahkan data yang diperlukan sebelum menghapus.
                    </p>
                </div>
            </div>

            {{-- Tombol aksi --}}
            <div style="padding: 20px 24px; background: #F9FAFB; display: flex; gap: 12px;">
                <button type="button" onclick="closeModal('deleteModal')"
                    style="flex: 1; padding: 11px 20px; border: 1.5px solid #D1D5DB; border-radius: 10px; background: #fff; color: #374151; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; font-family: inherit;"
                    onmouseover="this.style.background='#F3F4F6'; this.style.borderColor='#9CA3AF';"
                    onmouseout="this.style.background='#fff'; this.style.borderColor='#D1D5DB';">
                    <i class="ri-close-line" style="font-size: 15px;"></i> Batal
                </button>
                <button type="submit"
                    style="flex: 1; padding: 11px 20px; border: none; border-radius: 10px; background: linear-gradient(135deg, #EF4444, #DC2626); color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; font-family: inherit; box-shadow: 0 4px 12px rgba(239,68,68,0.3);"
                    onmouseover="this.style.background='linear-gradient(135deg, #DC2626, #B91C1C)'; this.style.boxShadow='0 6px 16px rgba(239,68,68,0.4)';"
                    onmouseout="this.style.background='linear-gradient(135deg, #EF4444, #DC2626)'; this.style.boxShadow='0 4px 12px rgba(239,68,68,0.3)';">
                    <i class="ri-delete-bin-6-line" style="font-size: 15px;"></i> Ya, Hapus
                </button>
            </div>
        </form>
    </div>
</div>


<script>
    function showGenerateLoading(form) {
        const btn = document.getElementById('btnGenerateSatker');
        const text = document.getElementById('btnGenerateText');
        
        btn.disabled = true;
        btn.style.cursor = 'not-allowed';
        btn.style.opacity = '0.8';
        
        text.innerHTML = '<i class="ri-loader-4-line ri-spin" style="font-size:15px;"></i> Sedang Memproses...';
    }

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
