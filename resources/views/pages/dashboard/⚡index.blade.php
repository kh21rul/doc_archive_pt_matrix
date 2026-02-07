<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\Document;
use App\Models\Division;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts::dashboard')] #[Title('Dashboard')] class extends Component {
    use WithPagination;

    public $totalDocuments = 0;
    public $totalDivisions = 0;
    public $recentUploads = 0;
    public $search = '';
    public $selectedDivision = null;
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->totalDocuments = Document::count();
        $this->totalDivisions = Division::count();
        $this->recentUploads = Document::whereDate('created_at', today())->count();
    }

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
        $query = Document::with('division');

        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '%')->orWhere('content', 'like', '%' . $this->search . '%');
        }

        if ($this->selectedDivision) {
            $query->where('division_id', $this->selectedDivision);
        }

        return $query->latest()->paginate(10);
    }

    public function deleteDocument($id)
    {
        $document = Document::find($id);
        if ($document) {
            // Delete file from storage
            if (file_exists(storage_path('app/public/' . $document->file_path))) {
                unlink(storage_path('app/public/' . $document->file_path));
            }
            $document->delete();
            session()->flash('success', 'Dokumen berhasil dihapus!');
        }
    }
};
?>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Selamat datang kembali, {{ Auth::user()->name ?? 'User' }}!</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card-dash stat-primary-dash">
            <div class="stat-icon-dash">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <p class="stat-label-dash">Total Dokumen</p>
                <h3 class="stat-value-dash">{{ $totalDocuments }}</h3>
                {{-- <span class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 12% dari bulan lalu
                </span> --}}
            </div>
        </div>

        <div class="stat-card-dash stat-success-dash">
            <div class="stat-icon-dash">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-content">
                <p class="stat-label-dash">Total Divisi</p>
                <h3 class="stat-value-dash">{{ $totalDivisions }}</h3>
                {{-- <span class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 2 divisi baru
                </span> --}}
            </div>
        </div>

        <div class="stat-card-dash stat-warning-dash">
            <div class="stat-icon-dash">
                <i class="fas fa-upload"></i>
            </div>
            <div class="stat-content">
                <p class="stat-label-dash">Upload Hari Ini</p>
                <h3 class="stat-value-dash">{{ $recentUploads }}</h3>
                {{-- <span class="stat-change neutral">
                    <i class="fas fa-minus"></i> Sama seperti kemarin
                </span> --}}
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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
                        <th width="30%">Judul Dokumen</th>
                        <th width="20%">Divisi</th>
                        <th width="25%">Deskripsi</th>
                        <th width="10%">Tanggal</th>
                        <th width="10%" class="text-center">Aksi</th>
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
                                    <span>{{ $document->title }}</span>
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
                                    {{ Str::limit($document->content, 50) }}
                                </span>
                            </td>
                            <td>
                                <span class="date-text">
                                    {{ $document->created_at->format('d M Y') }}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ asset('storage/' . $document->file_path) }}"
                                        class="btn-action-table btn-view" target="_blank" title="Lihat">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ asset('storage/' . $document->file_path) }}"
                                        class="btn-action-table btn-download" download title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button wire:click="deleteDocument({{ $document->id }})"
                                        wire:confirm="Apakah Anda yakin ingin menghapus dokumen ini?"
                                        class="btn-action-table btn-delete" title="Hapus">
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
</div>

<style>
    .dashboard-container {
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

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card-dash {
        background: white;
        border-radius: 16px;
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }

    .stat-card-dash:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }

    .stat-primary-dash {
        border-left-color: #c2a25d;
    }

    .stat-success-dash {
        border-left-color: #28a745;
    }

    .stat-warning-dash {
        border-left-color: #ffc107;
    }

    .stat-info-dash {
        border-left-color: #17a2b8;
    }

    .stat-icon-dash {
        width: 70px;
        height: 70px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        flex-shrink: 0;
    }

    .stat-primary-dash .stat-icon-dash {
        background: linear-gradient(135deg, rgba(194, 162, 93, 0.1), rgba(194, 162, 93, 0.2));
        color: #c2a25d;
    }

    .stat-success-dash .stat-icon-dash {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.2));
        color: #28a745;
    }

    .stat-warning-dash .stat-icon-dash {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.2));
        color: #ffc107;
    }

    .stat-info-dash .stat-icon-dash {
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(23, 162, 184, 0.2));
        color: #17a2b8;
    }

    .stat-content {
        flex: 1;
    }

    .stat-label-dash {
        color: #7f8c8d;
        font-size: 0.85rem;
        margin: 0 0 8px;
        font-weight: 500;
    }

    .stat-value-dash {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 8px;
    }

    .stat-change {
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .stat-change.positive {
        color: #28a745;
    }

    .stat-change.neutral {
        color: #6c757d;
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

    .date-text {
        color: #7f8c8d;
        font-size: 0.85rem;
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
        text-decoration: none;
    }

    .btn-view {
        background: #e3f2fd;
        color: #2196f3;
    }

    .btn-view:hover {
        background: #2196f3;
        color: white;
    }

    .btn-download {
        background: #e8f5e9;
        color: #4caf50;
    }

    .btn-download:hover {
        background: #4caf50;
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

    /* Pagination Styling */
    .pagination-wrapper .pagination {
        margin: 0;
    }

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

    /* Alert Styles */
    .alert {
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert i {
        font-size: 1.2rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .stats-grid {
            grid-template-columns: 1fr;
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
