<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Division;

new #[Layout('layouts::dashboard')] #[Title('Dashboard Divisi')] class extends Component {
    use WithPagination;

    public $search = '';
    public $divisionId = null;

    #[Validate('required|min:3')]
    public $name = '';

    #[Validate('nullable|max:500')]
    public $description = '';

    public $isEdit = false;
    protected $paginationTheme = 'bootstrap';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function divisions()
    {
        $query = Division::withCount('documents');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')->orWhere('description', 'like', '%' . $this->search . '%');
        }

        return $query->latest()->paginate(10);
    }

    public function openCreateModal()
    {
        $this->reset(['divisionId', 'name', 'description', 'isEdit']);
        $this->resetValidation();
        $this->dispatch('openModal');
    }

    public function openEditModal($id)
    {
        $division = Division::findOrFail($id);
        $this->divisionId = $division->id;
        $this->name = $division->name;
        $this->description = $division->description;
        $this->isEdit = true;
        $this->resetValidation();
        $this->dispatch('openModal');
    }

    public function save()
    {
        $this->validate();

        if ($this->isEdit) {
            $division = Division::findOrFail($this->divisionId);
            $division->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);
            $message = 'Divisi berhasil diperbarui!';
        } else {
            Division::create([
                'name' => $this->name,
                'description' => $this->description,
            ]);
            $message = 'Divisi berhasil ditambahkan!';
        }

        $this->reset(['divisionId', 'name', 'description', 'isEdit']);
        $this->dispatch('closeModal');
        $this->dispatch('showAlert', ['message' => $message, 'type' => 'success']);
    }

    public function confirmDelete($id)
    {
        $this->divisionId = $id;
        $this->dispatch('confirmDelete');
    }

    public function delete()
    {
        $division = Division::findOrFail($this->divisionId);

        // Check if division has documents
        if ($division->documents()->count() > 0) {
            $this->dispatch('showAlert', [
                'message' => 'Divisi tidak dapat dihapus karena masih memiliki dokumen!',
                'type' => 'error',
            ]);
            return;
        }

        $division->delete();
        $this->dispatch('showAlert', [
            'message' => 'Divisi berhasil dihapus!',
            'type' => 'success',
        ]);
    }
};
?>

<div class="divisions-container">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Divisi</h1>
            <p class="page-subtitle">Kelola divisi dan kategori dokumen</p>
        </div>
        <div class="header-actions">
            <button class="btn-primary-dash" wire:click="openCreateModal">
                <i class="fas fa-plus"></i> Tambah Divisi
            </button>
        </div>
    </div>

    <!-- Divisions Table -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">
                <i class="fas fa-building"></i> Data Divisi
            </h2>
            <div class="table-filters">
                <div class="filter-group">
                    <input type="text" class="filter-input" placeholder="Cari divisi..."
                        wire:model.live.debounce.300ms="search">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="25%">Nama Divisi</th>
                        <th width="40%">Deskripsi</th>
                        <th width="15%" class="text-center">Total Dokumen</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->divisions() as $index => $division)
                        <tr>
                            <td>{{ $this->divisions()->firstItem() + $index }}</td>
                            <td>
                                <div class="division-name-cell">
                                    <div class="division-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <span class="fw-bold">{{ $division->name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted-table">
                                    {{ $division->description ?: '-' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge-count">
                                    {{ $division->documents_count }} dokumen
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button wire:click="openEditModal({{ $division->id }})"
                                        class="btn-action-table btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="confirmDelete({{ $division->id }})"
                                        class="btn-action-table btn-delete" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="empty-state-table">
                                    <i class="fas fa-inbox"></i>
                                    <p>Tidak ada divisi ditemukan</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="showing-info">
                Menampilkan {{ $this->divisions()->firstItem() ?? 0 }} - {{ $this->divisions()->lastItem() ?? 0 }}
                dari {{ $this->divisions()->total() }} divisi
            </div>
            <div class="pagination-wrapper">
                {{ $this->divisions()->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="divisionModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas {{ $isEdit ? 'fa-edit' : 'fa-plus' }}"></i>
                        {{ $isEdit ? 'Edit Divisi' : 'Tambah Divisi Baru' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="save">
                    <div class="modal-body">
                        <!-- Name Field -->
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="fas fa-building text-primary"></i> Nama Divisi
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" wire:model="name" placeholder="Masukkan nama divisi">
                            @error('name')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Description Field -->
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left text-primary"></i> Deskripsi
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" wire:model="description"
                                rows="4" placeholder="Masukkan deskripsi divisi (opsional)"></textarea>
                            @error('description')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i>
                            <small>Pastikan nama divisi unik dan mudah diingat.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary-custom" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="fas {{ $isEdit ? 'fa-save' : 'fa-plus' }}"></i>
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
    .divisions-container {
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

    .division-name-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .division-icon {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .text-muted-table {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .badge-count {
        background: linear-gradient(135deg, rgba(194, 162, 93, 0.1), rgba(194, 162, 93, 0.2));
        color: #c2a25d;
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

    .btn-delete:hover {
        background: #f44336;
        color: white;
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

    .form-control {
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        padding: 12px 15px;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #c2a25d;
        box-shadow: 0 0 0 3px rgba(194, 162, 93, 0.1);
    }

    .form-control.is-invalid {
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

    .alert-info {
        background: #e3f2fd;
        border: 1px solid #90caf9;
        color: #1976d2;
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
            const modal = new bootstrap.Modal(document.getElementById('divisionModal'));
            modal.show();
        });

        $wire.on('closeModal', () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('divisionModal'));
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
                text: "Divisi ini akan dihapus permanen!",
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
