<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use App\Models\Document;
use App\Models\Division;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts::dashboard')] class extends Component {
    use WithPagination, WithFileUploads;

    public $search = '';
    public $selectedDivision = null;
    public $documentId = null;

    #[Validate('required|min:3')]
    public $title = '';

    #[Validate('nullable|max:1000')]
    public $description = '';

    #[Validate('required|exists:divisions,id')]
    public $division_id = ''; // Max 10MB

    #[Validate('nullable|file|mimes:pdf|max:10240')]
    public $file;

    public $currentFile = null;
    public $isEdit = false;
    protected $paginationTheme = 'bootstrap';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedDivision()
    {
        $this->resetPage();
    }

    public function documents()
    {
        $query = Document::with('division', 'user');

        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '%')->orWhere('content', 'like', '%' . $this->search . '%');
        }

        if ($this->selectedDivision) {
            $query->where('division_id', $this->selectedDivision);
        }

        return $query->latest()->paginate(10);
    }

    public function openCreateModal()
    {
        $this->reset(['documentId', 'title', 'description', 'division_id', 'file', 'currentFile', 'isEdit']);
        $this->resetValidation();
        $this->dispatch('openModal');
    }

    public function openEditModal($id)
    {
        $document = Document::findOrFail($id);
        $this->documentId = $document->id;
        $this->title = $document->title;
        $this->description = $document->content;
        $this->division_id = $document->division_id;
        $this->currentFile = $document->file_path;
        $this->isEdit = true;
        $this->resetValidation();
        $this->dispatch('openModal');
    }

    public function save()
    {
        // Validation rules berbeda untuk create dan edit
        if ($this->isEdit) {
            $this->validate([
                'title' => 'required|min:3',
                'description' => 'nullable|max:1000',
                'division_id' => 'required|exists:divisions,id',
                'file' => 'nullable|file|mimes:pdf|max:10240',
            ]);
        } else {
            $this->validate([
                'title' => 'required|min:3',
                'description' => 'nullable|max:1000',
                'division_id' => 'required|exists:divisions,id',
                'file' => 'required|file|mimes:pdf|max:10240',
            ]);
        }

        if ($this->isEdit) {
            $document = Document::findOrFail($this->documentId);

            // Update file jika ada file baru
            if ($this->file) {
                // Hapus file lama
                if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }

                // Upload file baru
                $fileName = time() . '_' . $this->file->getClientOriginalName();
                $filePath = $this->file->storeAs('documents', $fileName, 'public');
            } else {
                $filePath = $document->file_path;
            }

            $document->update([
                'title' => $this->title,
                'content' => $this->description,
                'division_id' => $this->division_id,
                'file_path' => $filePath,
            ]);

            $message = 'Dokumen berhasil diperbarui!';
        } else {
            // Upload file
            $fileName = time() . '_' . $this->file->getClientOriginalName();
            $filePath = $this->file->storeAs('documents', $fileName, 'public');

            Document::create([
                'title' => $this->title,
                'content' => $this->description,
                'division_id' => $this->division_id,
                'user_id' => Auth::id(),
                'file_path' => $filePath,
            ]);

            $message = 'Dokumen berhasil ditambahkan!';
        }

        $this->reset(['documentId', 'title', 'description', 'division_id', 'file', 'currentFile', 'isEdit']);
        $this->dispatch('closeModal');
        $this->dispatch('showAlert', ['message' => $message, 'type' => 'success']);
    }

    public function confirmDelete($id)
    {
        $this->documentId = $id;
        $this->dispatch('confirmDelete');
    }

    public function delete()
    {
        $document = Document::findOrFail($this->documentId);

        // Delete file from storage
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        $this->dispatch('showAlert', [
            'message' => 'Dokumen berhasil dihapus!',
            'type' => 'success',
        ]);
    }

    public function downloadDocument($id)
    {
        $document = Document::findOrFail($id);
        return Storage::disk('public')->download($document->file_path, $document->title . '.pdf');
    }
};
?>

<div class="documents-container">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Dokumen</h1>
            <p class="page-subtitle">Kelola dan organisir dokumen Anda</p>
        </div>
        <div class="header-actions">
            <button class="btn-primary-dash" wire:click="openCreateModal">
                <i class="fas fa-plus"></i> Tambah Dokumen
            </button>
        </div>
    </div>

    <!-- Documents Table -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">
                <i class="fas fa-folder-open"></i> Data Dokumen
            </h2>
            <div class="table-filters">
                <div class="filter-group">
                    <input type="text" class="filter-input" placeholder="Cari dokumen..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="filter-group">
                    <select class="filter-select" wire:model.live="selectedDivision">
                        <option value="">Semua Divisi</option>
                        @foreach (Division::all() as $division)
                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="25%">Judul Dokumen</th>
                        <th width="15%">Divisi</th>
                        <th width="20%">Deskripsi</th>
                        <th width="12%">Upload Oleh</th>
                        <th width="10%">Tanggal</th>
                        <th width="13%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->documents() as $index => $document)
                        <tr>
                            <td>{{ $this->documents()->firstItem() + $index }}</td>
                            <td>
                                <div class="document-title-cell">
                                    <div class="doc-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <span class="fw-bold">{{ $document->title }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge-table badge-division-table">
                                    <i class="fas fa-building"></i>
                                    {{ $document->division->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted-table">
                                    {{ Str::limit($document->content, 50) ?: '-' }}
                                </span>
                            </td>
                            <td>
                                <div class="user-cell">
                                    <i class="fas fa-user-circle text-primary"></i>
                                    <span>{{ $document->user->name ?? 'Unknown' }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="date-text">
                                    {{ $document->created_at->format('d M Y') }}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button wire:click="downloadDocument({{ $document->id }})"
                                        class="btn-action-table btn-download" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button wire:click="openEditModal({{ $document->id }})"
                                        class="btn-action-table btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="confirmDelete({{ $document->id }})"
                                        class="btn-action-table btn-delete" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state-table">
                                    <i class="fas fa-inbox"></i>
                                    <p>Tidak ada dokumen ditemukan</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="showing-info">
                Menampilkan {{ $this->documents()->firstItem() ?? 0 }} - {{ $this->documents()->lastItem() ?? 0 }}
                dari {{ $this->documents()->total() }} dokumen
            </div>
            <div class="pagination-wrapper">
                {{ $this->documents()->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="documentModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas {{ $isEdit ? 'fa-edit' : 'fa-plus' }}"></i>
                        {{ $isEdit ? 'Edit Dokumen' : 'Tambah Dokumen Baru' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="save">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Title Field -->
                            <div class="col-md-12 mb-3">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading text-primary"></i> Judul Dokumen
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror"
                                    id="title" wire:model="title" placeholder="Masukkan judul dokumen">
                                @error('title')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Division Field -->
                            <div class="col-md-12 mb-3">
                                <label for="division_id" class="form-label">
                                    <i class="fas fa-building text-primary"></i> Divisi
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('division_id') is-invalid @enderror" id="division_id"
                                    wire:model="division_id">
                                    <option value="">Pilih Divisi</option>
                                    @foreach (Division::all() as $division)
                                        <option value="{{ $division->id }}">{{ $division->name }}</option>
                                    @endforeach
                                </select>
                                @error('division_id')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Description Field -->
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left text-primary"></i> Deskripsi
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" wire:model="description"
                                    rows="3" placeholder="Masukkan deskripsi dokumen (opsional)"></textarea>
                                @error('description')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- File Upload Field -->
                            <div class="col-md-12 mb-3">
                                <label for="file" class="form-label">
                                    <i class="fas fa-file-pdf text-primary"></i> File PDF
                                    @if (!$isEdit)
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>

                                @if ($isEdit && $currentFile)
                                    <div class="current-file-info mb-2">
                                        <div class="alert alert-info mb-0 d-flex align-items-center gap-2">
                                            <i class="fas fa-file-pdf"></i>
                                            <div>
                                                <strong>File saat ini:</strong>
                                                <a href="{{ asset('storage/' . $currentFile) }}" target="_blank"
                                                    class="text-primary">
                                                    Lihat file
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="file-upload-wrapper">
                                    <input type="file" class="form-control @error('file') is-invalid @enderror"
                                        id="file" wire:model="file" accept=".pdf">
                                    @error('file')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                @if ($file)
                                    <div class="file-preview mt-2">
                                        <div class="alert alert-success mb-0 d-flex align-items-center gap-2">
                                            <i class="fas fa-check-circle"></i>
                                            <span>File dipilih:
                                                <strong>{{ $file->getClientOriginalName() }}</strong></span>
                                        </div>
                                    </div>
                                @endif

                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Format: PDF | Maksimal: 10MB
                                    @if ($isEdit)
                                        | Kosongkan jika tidak ingin mengubah file
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i>
                            <small>Pastikan file yang diupload adalah dokumen yang valid dan tidak mengandung
                                virus.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary-custom" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">
                                <i class="fas {{ $isEdit ? 'fa-save' : 'fa-upload' }}"></i>
                                {{ $isEdit ? 'Update' : 'Upload' }}
                            </span>
                            <span wire:loading wire:target="save">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                {{ $isEdit ? 'Mengupdate...' : 'Mengupload...' }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .documents-container {
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

    .table-filters {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .filter-input,
    .filter-select {
        padding: 10px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        min-width: 200px;
    }

    .filter-input:focus,
    .filter-select:focus {
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

    .document-title-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .doc-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        flex-shrink: 0;
    }

    .badge-table {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .badge-division-table {
        background: #e8f4f8;
        color: #17a2b8;
    }

    .text-muted-table {
        color: #6c757d;
        font-size: 0.85rem;
    }

    .user-cell {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
    }

    .date-text {
        color: #7f8c8d;
        font-size: 0.85rem;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 6px;
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

    .btn-download {
        background: #e8f5e9;
        color: #4caf50;
    }

    .btn-download:hover {
        background: #4caf50;
        color: white;
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

    /* File Upload Styles */
    .current-file-info .alert {
        padding: 10px 15px;
        font-size: 0.9rem;
    }

    .file-upload-wrapper {
        position: relative;
    }

    .file-preview .alert {
        padding: 10px 15px;
        font-size: 0.9rem;
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

    .alert-info {
        background: #e3f2fd;
        border: 1px solid #90caf9;
        color: #1976d2;
    }

    .alert-success {
        background: #e8f5e9;
        border: 1px solid #81c784;
        color: #2e7d32;
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

        .table-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .table-filters {
            width: 100%;
            flex-direction: column;
        }

        .filter-input,
        .filter-select {
            width: 100%;
            min-width: auto;
        }

        .data-table {
            font-size: 0.85rem;
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
            const modal = new bootstrap.Modal(document.getElementById('documentModal'));
            modal.show();
        });

        $wire.on('closeModal', () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('documentModal'));
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
                text: "Dokumen ini akan dihapus permanen!",
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
