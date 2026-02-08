<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

new #[Layout('layouts::dashboard')] #[Title('Dashboard')] class extends Component {
    use WithPagination;

    public $search = '';
    public $userId = null;

    #[Validate('required|min:3')]
    public $name = '';

    #[Validate('required|email|unique:users,email')]
    public $email = '';

    #[Validate('required|min:6')]
    public $password = '';

    #[Validate('nullable|min:6')]
    public $new_password = '';

    #[Validate('required|in:admin,viewer')]
    public $role = 'viewer';

    public $isEdit = false;
    protected $paginationTheme = 'bootstrap';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function users()
    {
        $query = User::withCount('documents');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
        }

        return $query->latest()->paginate(10);
    }

    public function openCreateModal()
    {
        $this->reset(['userId', 'name', 'email', 'password', 'new_password', 'role', 'isEdit']);
        $this->role = 'viewer';
        $this->resetValidation();
        $this->dispatch('openModal');
    }

    public function openEditModal($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->isEdit = true;
        $this->new_password = '';
        $this->resetValidation();
        $this->dispatch('openModal');
    }

    public function save()
    {
        if ($this->isEdit) {
            // Validation untuk edit
            $this->validate([
                'name' => 'required|min:3',
                'email' => 'required|email|unique:users,email,' . $this->userId,
                'role' => 'required|in:admin,viewer',
                'new_password' => 'nullable|min:6',
            ]);

            $user = User::findOrFail($this->userId);

            // Cek jika user mencoba mengubah role dirinya sendiri
            if ($user->id === auth()->id() && $user->role !== $this->role) {
                $this->dispatch('showAlert', [
                    'message' => 'Anda tidak dapat mengubah role Anda sendiri!',
                    'type' => 'error',
                ]);
                return;
            }

            $userData = [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
            ];

            // Update password jika diisi
            if ($this->new_password) {
                $userData['password'] = Hash::make($this->new_password);
            }

            $user->update($userData);

            $message = 'User berhasil diperbarui!';
        } else {
            // Validation untuk create
            $this->validate([
                'name' => 'required|min:3',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
                'role' => 'required|in:admin,viewer',
            ]);

            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
            ]);

            $message = 'User berhasil ditambahkan!';
        }

        $this->reset(['userId', 'name', 'email', 'password', 'new_password', 'role', 'isEdit']);
        $this->dispatch('closeModal');
        $this->dispatch('showAlert', ['message' => $message, 'type' => 'success']);
    }

    public function confirmDelete($id)
    {
        // Cek jika user mencoba menghapus dirinya sendiri
        if ($id === auth()->id()) {
            $this->dispatch('showAlert', [
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri!',
                'type' => 'error',
            ]);
            return;
        }

        $this->userId = $id;
        $this->dispatch('confirmDelete');
    }

    public function delete()
    {
        $user = User::findOrFail($this->userId);

        // Cek jika user memiliki dokumen
        if ($user->documents()->count() > 0) {
            $this->dispatch('showAlert', [
                'message' => 'User tidak dapat dihapus karena masih memiliki dokumen!',
                'type' => 'error',
            ]);
            return;
        }

        $user->delete();

        $this->dispatch('showAlert', [
            'message' => 'User berhasil dihapus!',
            'type' => 'success',
        ]);
    }
};
?>

<div class="users-container">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen User</h1>
            <p class="page-subtitle">Kelola pengguna sistem</p>
        </div>
        <div class="header-actions">
            <button class="btn-primary-dash" wire:click="openCreateModal">
                <i class="fas fa-user-plus"></i> Tambah User
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">
                <i class="fas fa-users"></i> Data User
            </h2>
            <div class="table-filters">
                <div class="filter-group">
                    <input type="text" class="filter-input" placeholder="Cari user..."
                        wire:model.live.debounce.300ms="search">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="25%">Nama</th>
                        <th width="25%">Email</th>
                        <th width="15%">Role</th>
                        <th width="15%" class="text-center">Total Dokumen</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->users() as $index => $user)
                        <tr>
                            <td>{{ $this->users()->firstItem() + $index }}</td>
                            <td>
                                <div class="user-name-cell">
                                    <div class="user-avatar-table">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span class="fw-bold">{{ $user->name }}</span>
                                    @if ($user->id === auth()->id())
                                        <span class="badge-you">You</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="text-muted-table">
                                    <i class="fas fa-envelope"></i> {{ $user->email }}
                                </span>
                            </td>
                            <td>
                                @if ($user->role === 'superadmin')
                                    <span class="badge-role badge-superadmin">
                                        <i class="fas fa-crown"></i> Super
                                    </span>
                                @elseif ($user->role === 'admin')
                                    <span class="badge-role badge-admin">
                                        <i class="fas fa-user-shield"></i> Admin
                                    </span>
                                @else
                                    <span class="badge-role badge-viewer">
                                        <i class="fas fa-eye"></i> Viewer
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge-count">
                                    {{ $user->documents_count }} dokumen
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button wire:click="openEditModal({{ $user->id }})"
                                        class="btn-action-table btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="confirmDelete({{ $user->id }})"
                                        class="btn-action-table btn-delete" title="Hapus"
                                        @if ($user->id === auth()->id() || $user->role === 'superadmin') disabled @endif>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state-table">
                                    <i class="fas fa-inbox"></i>
                                    <p>Tidak ada user ditemukan</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="showing-info">
                Menampilkan {{ $this->users()->firstItem() ?? 0 }} - {{ $this->users()->lastItem() ?? 0 }}
                dari {{ $this->users()->total() }} user
            </div>
            <div class="pagination-wrapper">
                {{ $this->users()->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="userModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas {{ $isEdit ? 'fa-user-edit' : 'fa-user-plus' }}"></i>
                        {{ $isEdit ? 'Edit User' : 'Tambah User Baru' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="save">
                    <div class="modal-body">
                        <!-- Name Field -->
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="fas fa-user text-primary"></i> Nama Lengkap
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" wire:model="name" placeholder="Masukkan nama lengkap">
                            @error('name')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope text-primary"></i> Email
                                <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="email" wire:model="email" placeholder="Masukkan email">
                            @error('email')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Role Field -->
                        <div class="mb-3">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-tag text-primary"></i> Role
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role"
                                wire:model="role" @disabled($isEdit && $role === 'superadmin')>
                                <option value="viewer">Viewer</option>
                                <option value="admin">Admin</option>

                                @if ($isEdit && $role === 'superadmin')
                                    <option value="superadmin">Superadmin</option>
                                @endif
                            </select>
                            @error('role')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                <strong>Admin:</strong> Full akses | <strong>Viewer:</strong> Hanya lihat dokumen
                            </small>
                        </div>

                        @if ($isEdit)
                            <!-- New Password Field (Edit Mode) -->
                            <div class="mb-3">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-key text-primary"></i> Password Baru
                                </label>
                                <input type="password"
                                    class="form-control @error('new_password') is-invalid @enderror" id="new_password"
                                    wire:model="new_password"
                                    placeholder="Masukkan password baru (kosongkan jika tidak ingin mengubah)">
                                @error('new_password')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> Kosongkan jika tidak ingin mengubah password
                                </small>
                            </div>
                        @else
                            <!-- Password Field (Create Mode) -->
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock text-primary"></i> Password
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" wire:model="password" placeholder="Masukkan password">
                                @error('password')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> Minimal 6 karakter
                                </small>
                            </div>
                        @endif

                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i>
                            <small>Pastikan data yang dimasukkan sudah benar sebelum menyimpan.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary-custom" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="fas {{ $isEdit ? 'fa-save' : 'fa-user-plus' }}"></i>
                                {{ $isEdit ? 'Update' : 'Simpan' }}
                            </span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .users-container {
        max-width: 100%;
    }

    /* Page Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }

    .page-subtitle {
        color: #7f8c8d;
        margin: 5px 0 0;
    }

    .btn-primary-dash {
        background: linear-gradient(135deg, #c2a25d 0%, #a88a4d 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .btn-primary-dash:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(194, 162, 93, 0.3);
    }

    /* Table Card */
    .table-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .table-header {
        padding: 25px 30px;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .table-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .table-title i {
        color: #c2a25d;
    }

    .filter-input {
        padding: 10px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        min-width: 300px;
    }

    .filter-input:focus {
        outline: none;
        border-color: #c2a25d;
        box-shadow: 0 0 0 3px rgba(194, 162, 93, 0.1);
    }

    /* Table Styles */
    .table-responsive {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table thead {
        background: #f8f9fa;
    }

    .data-table th {
        padding: 15px 20px;
        text-align: left;
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
        border-bottom: 2px solid #e0e0e0;
        white-space: nowrap;
    }

    .data-table td {
        padding: 18px 20px;
        border-bottom: 1px solid #f0f0f0;
        color: #555;
        font-size: 0.9rem;
    }

    .data-table tbody tr {
        transition: all 0.2s ease;
    }

    .data-table tbody tr:hover {
        background: #fafafa;
    }

    .user-name-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar-table {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .badge-you {
        background: linear-gradient(135deg, #2196f3, #1976d2);
        color: white;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-role {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .badge-superadmin {
        background: linear-gradient(135deg, rgba(255, 87, 34, 0.1), rgba(255, 87, 34, 0.2));
        color: #ff5722;
    }

    .badge-admin {
        background: linear-gradient(135deg, rgba(194, 162, 93, 0.2), rgba(194, 162, 93, 0.3));
        color: #c2a25d;
    }

    .badge-viewer {
        background: linear-gradient(135deg, rgba(33, 150, 243, 0.1), rgba(33, 150, 243, 0.2));
        color: #2196f3;
    }

    .text-muted-table {
        color: #6c757d;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .badge-count {
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.2));
        color: #4caf50;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
    }

    .btn-action-table {
        width: 35px;
        height: 35px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-edit {
        background: #fff3e0;
        color: #ff9800;
    }

    .btn-edit:hover {
        background: #ff9800;
        color: white;
    }

    .btn-delete {
        background: #ffebee;
        color: #f44336;
    }

    .btn-delete:hover:not(:disabled) {
        background: #f44336;
        color: white;
    }

    .btn-delete:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    /* Empty State */
    .empty-state-table {
        padding: 40px 20px;
        text-align: center;
        color: #999;
    }

    .empty-state-table i {
        font-size: 3rem;
        color: #ddd;
        margin-bottom: 15px;
    }

    .empty-state-table p {
        margin: 0;
        font-size: 1rem;
    }

    /* Table Footer */
    .table-footer {
        padding: 20px 30px;
        border-top: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .showing-info {
        color: #7f8c8d;
        font-size: 0.9rem;
    }

    /* Modal Styles */
    .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        background: linear-gradient(135deg, #c2a25d 0%, #a88a4d 100%);
        color: white;
        border-radius: 16px 16px 0 0;
        padding: 20px 25px;
        border: none;
    }

    .modal-title {
        font-weight: 600;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-header .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }

    .modal-body {
        padding: 25px;
    }

    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-control,
    .form-select {
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        padding: 12px 15px;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #c2a25d;
        box-shadow: 0 0 0 3px rgba(194, 162, 93, 0.1);
    }

    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        display: block;
        margin-top: 5px;
        font-size: 0.875rem;
    }

    .modal-footer {
        padding: 20px 25px;
        border-top: 1px solid #f0f0f0;
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #c2a25d 0%, #a88a4d 100%);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(194, 162, 93, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
    }

    /* Alert in Modal */
    .alert {
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
    }

    .alert-warning {
        background: #fff3cd;
        border: 1px solid #ffc107;
        color: #856404;
    }

    /* Pagination */
    .pagination .page-link {
        color: #c2a25d !important;
        border: 1px solid #e0e0e0;
        padding: 8px 14px;
        margin: 0 3px;
        border-radius: 8px;
    }

    .pagination .page-link:hover {
        background: #c2a25d !important;
        color: white !important;
        border-color: #c2a25d !important;
    }

    .pagination .page-item.active .page-link {
        background: #c2a25d !important;
        border-color: #c2a25d !important;
        color: white !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .filter-input {
            width: 100%;
            min-width: auto;
        }

        .table-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .data-table th,
        .data-table td {
            padding: 12px 10px;
        }

        .table-footer {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

@script
    <script>
        // SweetAlert CDN
        if (!document.querySelector('script[src*="sweetalert2"]')) {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
            document.head.appendChild(script);
        }

        // Modal Control
        $wire.on('openModal', () => {
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        });

        $wire.on('closeModal', () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
            if (modal) {
                modal.hide();
            }
        });

        // SweetAlert for Success/Error
        $wire.on('showAlert', (event) => {
            const data = event[0];
            Swal.fire({
                icon: data.type,
                title: data.type === 'success' ? 'Berhasil!' : 'Oops...',
                text: data.message,
                confirmButtonColor: '#c2a25d',
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true,
            });
        });

        // SweetAlert for Delete Confirmation
        $wire.on('confirmDelete', () => {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "User ini akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c2a25d',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $wire.call('delete');
                }
            });
        });

        // Scroll to table on pagination
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({
                component
            }) => {
                setTimeout(() => {
                    const section = document.querySelector('.table-card');
                    if (section && window.location.search.includes('page=')) {
                        section.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    }
                }, 100);
            });
        });
    </script>
@endscript
