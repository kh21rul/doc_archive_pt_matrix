<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Division;
use App\Models\Document;

new class extends Component {
    use WithPagination;

    public $divisions;
    public $search = '';
    public $selectedDivision = null;
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->divisions = Division::all();
    }

    public function updatedSearch()
    {
        $this->resetPage(); // Reset ke halaman 1 saat search berubah
    }

    public function updatedSelectedDivision()
    {
        $this->resetPage(); // Reset ke halaman 1 saat divisi berubah
    }

    public function toggleDivision($divisionId)
    {
        if ($this->selectedDivision == $divisionId) {
            $this->selectedDivision = null; // Lepas filter jika diklik lagi
        } else {
            $this->selectedDivision = $divisionId; // Set filter
        }
        $this->resetPage();
    }

    #[Computed]
    public function documents()
    {
        $query = Document::query();

        // Filter berdasarkan pencarian judul
        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '%');
        }

        // Filter berdasarkan divisi yang dipilih
        if ($this->selectedDivision) {
            $query->where('division_id', $this->selectedDivision);
        }

        return $query->latest()->paginate(6);
    }
};
?>

<div>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="{{ asset('asset/images/logoo.png') }}" alt="logooo" class="brand-logo">
            </a>
        </div>
    </nav>

    <div class="menu-bar py-2">
        <div class="container">
            <a href="#">Informations</a>
        </div>
    </div>

    <section class="hero">
        <img src="{{ asset('asset/images/LIBRARY.png') }}" alt="logo" class="bn-logo mx-auto d-block">
    </section>

    <section class="container help-wrapper mb-1">
        <div class="row g-4">
            @foreach ($divisions as $division)
                <div class="col-md-4">
                    <div class="help-card {{ $selectedDivision == $division->id ? 'active' : '' }}"
                        wire:click="toggleDivision({{ $division->id }})" style="cursor: pointer;">
                        <div class="help-icon"><img src="{{ asset('asset/images/1.png') }}" alt="1"></div>
                        <div>
                            <h5>{{ $division->name }}</h5>
                            <p>{{ $division->description }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="container help-wrapper mb-1 py-4">
        <div class="hero-search mb-4">
            <div class="row g-2">
                <div>
                    <input type="text" class="form-control" placeholder="Search Document..."
                        wire:model.live.debounce.300ms="search">
                </div>
            </div>
        </div>

        <div class="documents-list">
            @forelse ($this->documents as $document)
                <div
                    class="document-item d-flex justify-content-between align-items-center mb-3 p-3 border rounded bg-white">
                    <div class="document-info">
                        <h5 class="mb-1">{{ $document->title }}</h5>
                        <p class="text-muted mb-0">{{ \Str::limit($document->content, 100) }}</p>
                        <small class="text-muted">
                            <i class="fas fa-building"></i> {{ $document->division->name ?? 'N/A' }}
                        </small>
                    </div>

                    <a href="{{ asset('storage/' . $document->file_path) }}" class="btn-download" target="_blank"
                        download title="Download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            @empty
                <div class="text-center alert alert-custom">
                    <i class="fas fa-info-circle"></i> No documents found.
                </div>
            @endforelse
        </div>

        <!-- Pagination Links -->
        <div class="mt-4">
            {{ $this->documents->links() }}
        </div>
    </section>

    {{-- buatkan footer --}}
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; {{ date('Y') }} Document Archive. All rights reserved.</p>
        </div>
    </footer>
    <a href="#" class="chat-btn">💬 Chat</a>
</div>
