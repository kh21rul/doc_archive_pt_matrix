<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Division;
use App\Models\Document;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Storage;

new #[Title('Home')] class extends Component {
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

    public function downloadDocument($id)
    {
        $document = Document::findOrFail($id);
        return Storage::disk('public')->download($document->file_path, $document->title);
    }

    public function logout()
    {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirect('/', navigate: true);
    }
};
?>

<div>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" wire:navigate href="{{ route('home') }}">
                <img src="{{ asset('asset/images/logoo.png') }}" alt="logooo" class="brand-logo">
            </a>

            <!-- Navbar Toggle for Mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Content -->
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                @auth
                    <!-- User Dropdown (Jika sudah login) -->
                    <div class="dropdown">
                        <a class="nav-user-dropdown dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar-nav">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <span class="user-name-nav">{{ Auth::user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li class="dropdown-header">
                                <div class="dropdown-user-info">
                                    <strong>{{ Auth::user()->name }}</strong>
                                    <small class="text-muted d-block">{{ Auth::user()->email }}</small>
                                </div>
                            </li>
                            @if (Auth::user()->isAdmin())
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item" wire:navigate href="{{ route('dashboard') }}">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" wire:navigate href="{{ route('dashboard.documents') }}">
                                        <i class="fas fa-file-alt"></i> Kelola Dokumen
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" wire:navigate href="{{ route('dashboard.divisions') }}">
                                        <i class="fas fa-building"></i> Divisi
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" wire:navigate href="{{ route('dashboard.users') }}">
                                        <i class="fas fa-users"></i> Kelola User
                                    </a>
                                </li>
                            @endif
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                {{-- <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </button>
                                </form> --}}
                                <button type="button" wire:click="logout" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </button>
                            </li>
                        </ul>
                    </div>
                @else
                    <!-- Login Link (Jika belum login) -->
                    <a wire:navigate href="{{ route('login') }}" class="btn-login-nav">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                @endauth
            </div>
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

    @auth
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

                        <button wire:click="downloadDocument({{ $document->id }})" class="btn-download" target="_blank"
                            title="Download">
                            <i class="fas fa-download"></i> Download
                        </button>
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
    @endauth

    {{-- buatkan footer --}}
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; {{ date('Y') }} Document Archive. All rights reserved.</p>
        </div>
    </footer>
    <a href="#" class="chat-btn">💬 Chat</a>
</div>

<style>
    /* Navbar Styles */
    .navbar {
        background: #fff;
        border-bottom: 1px solid #ddd;
        padding: 15px 0;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .navbar-brand {
        display: flex;
        align-items: center;
        padding: 0;
    }

    .brand-logo {
        height: 38px;
        width: auto;
    }

    /* User Dropdown Styles */
    .nav-user-dropdown {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 15px;
        border-radius: 25px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s ease;
        background: #f8f9fa;
        border: 2px solid transparent;
    }

    .nav-user-dropdown:hover {
        background: #e9ecef;
        border-color: #c2a25d;
        color: #333;
    }

    .user-avatar-nav {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.3rem;
    }

    .user-name-nav {
        font-weight: 600;
        font-size: 0.95rem;
        color: #2c3e50;
    }

    .dropdown-menu {
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        border-radius: 12px;
        padding: 8px 0;
        min-width: 250px;
        margin-top: 10px !important;
    }

    .dropdown-header {
        padding: 12px 20px;
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        color: white;
        border-radius: 12px 12px 0 0;
        margin: -8px 0 0 0;
    }

    .dropdown-user-info strong {
        font-size: 1rem;
        color: white;
    }

    .dropdown-user-info small {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.9);
    }

    .dropdown-divider {
        margin: 8px 0;
        border-color: #e9ecef;
    }

    .dropdown-item {
        padding: 10px 20px;
        font-size: 0.9rem;
        color: #333;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dropdown-item i {
        width: 20px;
        text-align: center;
        color: #c2a25d;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
        color: #c2a25d;
        padding-left: 25px;
    }

    .dropdown-item.text-danger:hover {
        background: #ffebee;
        color: #dc3545 !important;
    }

    .dropdown-item.text-danger i {
        color: #dc3545;
    }

    .dropdown-item button {
        background: none;
        border: none;
        width: 100%;
        text-align: left;
        font-family: inherit;
        cursor: pointer;
    }

    /* Login Button Styles */
    .btn-login-nav {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .btn-login-nav:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(194, 162, 93, 0.3);
        color: white;
    }

    .btn-login-nav i {
        font-size: 1rem;
    }

    /* Navbar Toggle for Mobile */
    .navbar-toggler {
        border: 2px solid #c2a25d;
        padding: 8px 12px;
        border-radius: 8px;
    }

    .navbar-toggler:focus {
        box-shadow: 0 0 0 3px rgba(194, 162, 93, 0.25);
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='%23c2a25d' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    /* Responsive */
    @media (max-width: 991px) {
        .navbar-collapse {
            padding: 20px 0;
        }

        .nav-user-dropdown {
            justify-content: space-between;
            width: 100%;
            margin-bottom: 10px;
        }

        .btn-login-nav {
            width: 100%;
            justify-content: center;
        }

        .dropdown-menu {
            width: 100%;
            margin-top: 5px !important;
        }
    }

    @media (max-width: 576px) {
        .navbar {
            padding: 10px 0;
        }

        .brand-logo {
            height: 32px;
        }

        .user-name-nav {
            font-size: 0.9rem;
        }

        .user-avatar-nav {
            width: 32px;
            height: 32px;
            font-size: 1.1rem;
        }
    }
</style>
