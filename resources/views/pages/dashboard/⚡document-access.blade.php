<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Models\Division;

new #[Layout('layouts::dashboard')] #[Title('Dashboard Divisi')] class extends Component {
    public $users;
    public $divisions;
    public $selectedUser = null;
    public $selectedDivisions = [];

    public function mount()
    {
        $this->loadUsers();
        $this->divisions = Division::all();
    }

    public function loadUsers()
    {
        $this->users = User::with('divisions')->get();
    }

    public function selectUser($userId)
    {
        $this->selectedUser = User::with('divisions')->findOrFail($userId);
        $this->selectedDivisions = $this->selectedUser->divisions->pluck('id')->toArray();
        $this->dispatch('openModal');
    }

    public function saveAccess()
    {
        if (!$this->selectedUser) {
            return;
        }

        // Sync divisions for the selected user
        $this->selectedUser->divisions()->sync($this->selectedDivisions);

        $this->dispatch('closeModal');
        $this->dispatch('showAlert', [
            'message' => 'Hak akses berhasil diperbarui!',
            'type' => 'success',
        ]);

        $this->loadUsers();
        $this->reset(['selectedUser', 'selectedDivisions']);
    }

    public function grantAllAccess($userId)
    {
        $user = User::findOrFail($userId);
        $allDivisionIds = Division::pluck('id');
        $user->divisions()->sync($allDivisionIds);

        $this->dispatch('showAlert', [
            'message' => 'Akses penuh telah diberikan!',
            'type' => 'success',
        ]);

        $this->loadUsers();
    }

    public function revokeAllAccess($userId)
    {
        $user = User::findOrFail($userId);
        $user->divisions()->detach();

        $this->dispatch('showAlert', [
            'message' => 'Semua akses telah dicabut!',
            'type' => 'success',
        ]);

        $this->loadUsers();
    }
};
?>

<div class="access-container">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Akses User</h1>
            <p class="page-subtitle">Kelola hak akses user terhadap divisi</p>
        </div>
    </div>

    <!-- Users Grid -->
    <div class="row g-4">
        @foreach ($users as $user)
            <div class="col-md-6 col-lg-4">
                <div class="user-access-card">
                    <!-- Card Header -->
                    <div class="card-header-access">
                        <div class="user-info-header">
                            <div class="user-avatar-access">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="user-details-header">
                                <h4 class="user-name-access">{{ $user->name }}</h4>
                                <p class="user-email-access">{{ $user->email }}</p>
                                @if ($user->role === 'superadmin')
                                    <span class="badge-role-access badge-superadmin-access">
                                        <i class="fas fa-crown"></i> Super
                                    </span>
                                @elseif ($user->role === 'admin')
                                    <span class="badge-role-access badge-admin-access">
                                        <i class="fas fa-user-shield"></i> Admin
                                    </span>
                                @else
                                    <span class="badge-role-access badge-viewer-access">
                                        <i class="fas fa-eye"></i> Viewer
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body-access">
                        @if ($user->isAdmin())
                            <!-- Admin has full access -->
                            <div class="access-status full-access">
                                <i class="fas fa-check-circle"></i>
                                <span>Akses Penuh ke Semua Divisi</span>
                            </div>
                        @else
                            <!-- Show accessible divisions -->
                            <div class="access-info">
                                <div class="access-label">
                                    <i class="fas fa-building"></i>
                                    Akses Divisi ({{ $user->divisions->count() }}/{{ $divisions->count() }})
                                </div>

                                @if ($user->divisions->count() > 0)
                                    <div class="divisions-list">
                                        @foreach ($user->divisions->take(3) as $division)
                                            <span class="division-badge">
                                                <i class="fas fa-folder"></i>
                                                {{ $division->name }}
                                            </span>
                                        @endforeach
                                        @if ($user->divisions->count() > 3)
                                            <span class="division-badge more">
                                                +{{ $user->divisions->count() - 3 }} lainnya
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <div class="no-access">
                                        <i class="fas fa-lock"></i>
                                        <span>Belum memiliki akses</span>
                                    </div>
                                @endif

                                <!-- Progress Bar -->
                                <div class="access-progress">
                                    <div class="progress-bar-access"
                                        style="width: {{ $divisions->count() > 0 ? ($user->divisions->count() / $divisions->count()) * 100 : 0 }}%">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Card Footer -->
                    <!-- Card Footer -->
                    @if (!$user->isAdmin())
                        <div class="card-footer-access">
                            <button wire:click="selectUser({{ $user->id }})" class="btn-access-manage">
                                <i class="fas fa-edit"></i> Kelola Akses
                            </button>
                            <div class="dropdown">
                                <button class="btn-access-more dropdown-toggle" type="button"
                                    id="dropdownUser{{ $user->id }}" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end"
                                    aria-labelledby="dropdownUser{{ $user->id }}">
                                    <li>
                                        <a class="dropdown-item" href="#"
                                            wire:click.prevent="grantAllAccess({{ $user->id }})">
                                            <i class="fas fa-check-double text-success"></i>
                                            Berikan Akses Penuh
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#"
                                            wire:click.prevent="revokeAllAccess({{ $user->id }})">
                                            <i class="fas fa-ban"></i>
                                            Cabut Semua Akses
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal -->
    <div class="modal fade" id="accessModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shield-alt"></i>
                        Kelola Hak Akses
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="saveAccess">
                    <div class="modal-body">
                        @if ($selectedUser)
                            <!-- User Info -->
                            <div class="selected-user-info">
                                <div class="user-avatar-modal">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">{{ $selectedUser->name }}</h5>
                                    <p class="text-muted mb-0">{{ $selectedUser->email }}</p>
                                </div>
                            </div>

                            <hr>

                            <!-- Division Selection -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-building text-primary"></i>
                                    Pilih Divisi yang Dapat Diakses
                                </label>
                                <p class="text-muted small mb-3">
                                    Centang divisi yang boleh diakses oleh user ini
                                </p>

                                <div class="divisions-checkbox-grid">
                                    @foreach ($divisions as $division)
                                        <div class="division-checkbox-item">
                                            <input type="checkbox" class="form-check-input"
                                                id="division-{{ $division->id }}" value="{{ $division->id }}"
                                                wire:model="selectedDivisions">
                                            <label class="form-check-label" for="division-{{ $division->id }}">
                                                <div class="division-checkbox-content">
                                                    <i class="fas fa-folder"></i>
                                                    <div>
                                                        <strong>{{ $division->name }}</strong>
                                                        <small class="d-block text-muted">
                                                            {{ $division->documents_count ?? $division->documents->count() }}
                                                            dokumen
                                                        </small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Summary -->
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i>
                                <small>
                                    Terpilih: <strong>{{ count($selectedDivisions) }}</strong> dari
                                    {{ $divisions->count() }} divisi
                                </small>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .access-container {
        max-width: 100%;
    }

    /* Page Header */
    .page-header {
        margin-bottom: 30px;
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

    /* User Access Card */
    .user-access-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .user-access-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }

    .card-header-access {
        padding: 25px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 2px solid #e0e0e0;
    }

    .user-info-header {
        display: flex;
        gap: 15px;
        align-items: flex-start;
    }

    .user-avatar-access {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.8rem;
        flex-shrink: 0;
    }

    .user-details-header {
        flex: 1;
    }

    .user-name-access {
        font-size: 1.2rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 5px;
    }

    .user-email-access {
        font-size: 0.85rem;
        color: #7f8c8d;
        margin: 0 0 10px;
    }

    .badge-role-access {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .badge-superadmin-access {
        background: linear-gradient(135deg, rgba(255, 87, 34, 0.1), rgba(255, 87, 34, 0.2));
        color: #ff5722;
    }

    .badge-admin-access {
        background: linear-gradient(135deg, rgba(194, 162, 93, 0.2), rgba(194, 162, 93, 0.3));
        color: #c2a25d;
    }

    .badge-viewer-access {
        background: linear-gradient(135deg, rgba(33, 150, 243, 0.1), rgba(33, 150, 243, 0.2));
        color: #2196f3;
    }

    /* Card Body */
    .card-body-access {
        padding: 25px;
        min-height: 200px;
    }

    .access-status {
        padding: 20px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
    }

    .full-access {
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.2));
        color: #4caf50;
        border: 2px solid rgba(76, 175, 80, 0.3);
    }

    .full-access i {
        font-size: 1.5rem;
    }

    .access-info {
        margin-top: 10px;
    }

    .access-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .access-label i {
        color: #c2a25d;
    }

    .divisions-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 15px;
    }

    .division-badge {
        background: #f0f0f0;
        color: #555;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .division-badge i {
        color: #c2a25d;
        font-size: 0.75rem;
    }

    .division-badge.more {
        background: #c2a25d;
        color: white;
        font-weight: 600;
    }

    .no-access {
        padding: 30px;
        text-align: center;
        color: #999;
        background: #fafafa;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .no-access i {
        font-size: 2rem;
        color: #ddd;
    }

    /* Progress Bar */
    .access-progress {
        height: 8px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        margin-top: 15px;
    }

    .progress-bar-access {
        height: 100%;
        background: linear-gradient(90deg, #c2a25d, #a88a4d);
        border-radius: 10px;
        transition: width 0.6s ease;
    }

    /* Card Footer */
    .card-footer-access {
        padding: 15px 25px;
        background: #fafafa;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .btn-access-manage {
        flex: 1;
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-access-manage:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(194, 162, 93, 0.3);
    }

    .btn-access-more {
        width: 40px;
        height: 40px;
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #666;
    }

    .btn-access-more:hover {
        border-color: #c2a25d;
        color: #c2a25d;
    }

    /* Modal */
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

    .selected-user-info {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
    }

    .user-avatar-modal {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.8rem;
    }

    /* Division Checkbox Grid */
    .divisions-checkbox-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 12px;
    }

    .division-checkbox-item {
        position: relative;
    }

    .division-checkbox-item input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .division-checkbox-item label {
        display: block;
        padding: 15px;
        background: #f8f9fa;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin: 0;
    }

    .division-checkbox-item input[type="checkbox"]:checked+label {
        background: linear-gradient(135deg, rgba(194, 162, 93, 0.1), rgba(194, 162, 93, 0.2));
        border-color: #c2a25d;
    }

    .division-checkbox-item label:hover {
        border-color: #c2a25d;
        transform: translateY(-2px);
    }

    .division-checkbox-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .division-checkbox-content i {
        font-size: 1.5rem;
        color: #c2a25d;
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

    .alert {
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
    }

    .alert-info {
        background: #e3f2fd;
        border: 1px solid #90caf9;
        color: #1976d2;
    }

    .dropdown-menu {
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        border: none;
        padding: 8px 0;
    }

    .dropdown-item {
        padding: 10px 20px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
        padding-left: 25px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .divisions-checkbox-grid {
            grid-template-columns: 1fr;
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
        let accessModalInstance = null;

        $wire.on('openModal', () => {
            const modalElement = document.getElementById('accessModal');
            if (accessModalInstance) {
                accessModalInstance.hide();
            }
            accessModalInstance = new bootstrap.Modal(modalElement);
            accessModalInstance.show();
        });

        $wire.on('closeModal', () => {
            if (accessModalInstance) {
                accessModalInstance.hide();
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

        // Re-initialize dropdowns after Livewire updates
        document.addEventListener('livewire:navigated', function() {
            const dropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            dropdowns.forEach(dropdown => {
                new bootstrap.Dropdown(dropdown);
            });
        });
    </script>
@endscript
