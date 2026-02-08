<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Division;
use App\Models\Document;
use App\Models\Comment;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Storage;

new #[Title('Home')] class extends Component {
    use WithPagination;

    public $divisions;
    public $search = '';
    public $selectedDivision = null;
    protected $paginationTheme = 'bootstrap';

    // Properties untuk modal komentar
    public $selectedDocumentId = null;
    public $selectedDocumentTitle = '';
    public $newComment = '';
    public $documentComments = [];

    public function mount()
    {
        if (Auth::check()) {
            if (Auth::user()->isAdmin()) {
                $this->divisions = Division::all();
            } else {
                $this->divisions = Auth::user()->divisions;
            }
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedDivision()
    {
        $this->resetPage();
    }

    public function toggleDivision($divisionId)
    {
        if ($this->selectedDivision == $divisionId) {
            $this->selectedDivision = null;
        } else {
            $this->selectedDivision = $divisionId;
        }
        $this->resetPage();
    }

    #[Computed]
    public function documents()
    {
        $query = Auth::user()
            ->getAccessibleDocuments()
            ->with(['division', 'comments']);

        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '%');
        }

        if ($this->selectedDivision) {
            $query->where('division_id', $this->selectedDivision);
        }

        return $query->latest()->paginate(6);
    }

    public function openCommentModal($documentId)
    {
        $document = Document::find($documentId);
        $this->selectedDocumentId = $documentId;
        $this->selectedDocumentTitle = $document->title ?? 'Dokumen';
        $this->loadComments();
        $this->newComment = '';

        $this->dispatch('open-comment-modal');
    }

    public function closeCommentModal()
    {
        $this->selectedDocumentId = null;
        $this->selectedDocumentTitle = '';
        $this->newComment = '';
        $this->documentComments = [];
    }

    public function loadComments()
    {
        if ($this->selectedDocumentId) {
            $this->documentComments = Comment::where('document_id', $this->selectedDocumentId)->with('user')->latest()->get()->toArray();
        }
    }

    public function addComment()
    {
        $this->validate(
            [
                'newComment' => 'required|min:1|max:1000',
            ],
            [
                'newComment.required' => 'Komentar tidak boleh kosong.',
                'newComment.min' => 'Komentar minimal 1 karakter.',
                'newComment.max' => 'Komentar maksimal 1000 karakter.',
            ],
        );

        Comment::create([
            'document_id' => $this->selectedDocumentId,
            'user_id' => Auth::id(),
            'content' => $this->newComment,
        ]);

        $this->newComment = '';
        $this->loadComments();

        $this->dispatch('comment-added');
    }

    public function deleteComment($commentId)
    {
        try {
            $comment = Comment::find($commentId);

            if (!$comment) {
                $this->dispatch('comment-not-found');
                return;
            }

            if ($comment->user_id != Auth::id()) {
                $this->dispatch('comment-unauthorized');
                return;
            }

            $comment->delete();
            $this->loadComments();
            $this->dispatch('comment-deleted');
        } catch (\Exception $e) {
            $this->dispatch('comment-error', ['message' => $e->getMessage()]);
        }
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

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                @auth
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
                            @endif
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <button type="button" wire:click="logout" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </button>
                            </li>
                        </ul>
                    </div>
                @else
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
                            @if ($division->logo)
                                <div class="help-icon">
                                    <img src="{{ Storage::url($division->logo) }}" alt="{{ $division->name }}"
                                        class="division-logo-img">
                                </div>
                            @else
                                <div class="division-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                            @endif
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
                        <div class="document-info flex-grow-1">
                            <h5 class="mb-1">{{ $document->title }}</h5>
                            <p class="text-muted mb-0">{{ \Str::limit($document->content, 100) }}</p>
                            <small class="text-muted">
                                <i class="fas fa-building"></i> {{ $document->division->name ?? 'N/A' }}
                                <span class="ms-3">
                                    <i class="fas fa-comments"></i> {{ $document->comments->count() }} Komentar
                                </span>
                            </small>
                        </div>

                        <div class="document-actions d-flex gap-2">
                            <button wire:click="openCommentModal({{ $document->id }})" class="btn-comment"
                                title="Komentar">
                                <i class="fas fa-comment"></i> Komentar
                            </button>
                            <button wire:click="downloadDocument({{ $document->id }})" class="btn-download"
                                title="Download">
                                <i class="fas fa-download"></i> Download
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center alert alert-custom">
                        <i class="fas fa-info-circle"></i> No documents found.
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $this->documents->links() }}
            </div>
        </section>
    @endauth

    <!-- Modal Bootstrap untuk Komentar -->
    <div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true"
        wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-golden">
                    <h5 class="modal-title text-white" id="commentModalLabel">
                        <i class="fas fa-comments me-2"></i>Komentar - {{ $selectedDocumentTitle }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form Tambah Komentar -->
                    <div class="add-comment-section mb-4">
                        <h6 class="mb-3"><i class="fas fa-pen me-2"></i>Tambah Komentar</h6>
                        <form wire:submit.prevent="addComment">
                            <div class="mb-3">
                                <textarea wire:model="newComment" class="form-control @error('newComment') is-invalid @enderror" rows="3"
                                    placeholder="Tulis komentar Anda..."></textarea>
                                @error('newComment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-golden">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Komentar
                            </button>
                        </form>
                    </div>

                    <hr>

                    <!-- Daftar Komentar -->
                    <div class="comments-list">
                        <h6 class="mb-3">
                            <i class="fas fa-list me-2"></i>Semua Komentar
                            <span class="badge bg-golden">{{ count($documentComments) }}</span>
                        </h6>

                        @forelse($documentComments as $comment)
                            <div class="comment-item mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar-comment me-3">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <strong class="d-block">{{ $comment['user']['name'] }}</strong>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ \Carbon\Carbon::parse($comment['created_at'])->diffForHumans() }}
                                            </small>
                                        </div>
                                    </div>
                                    @if ($comment['user']['id'] == Auth::id() || Auth::user()->role == 'superadmin')
                                        <button
                                            wire:click="$dispatch('confirm-delete', { commentId: {{ $comment['id'] }} })"
                                            class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                                <div class="comment-content">
                                    {{ $comment['content'] }}
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-comment-slash fa-3x mb-3 opacity-50"></i>
                                <p class="mb-0">Belum ada komentar. Jadilah yang pertama berkomentar!</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; {{ date('Y') }} Document Archive. All rights reserved.</p>
        </div>
    </footer>
    {{-- <a href="#" class="chat-btn">💬 Chat</a> --}}
</div>

@script
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Open modal
            Livewire.on('open-comment-modal', () => {
                const modal = new bootstrap.Modal(document.getElementById('commentModal'));
                modal.show();
            });

            // Event untuk konfirmasi hapus
            Livewire.on('confirm-delete', (event) => {
                Swal.fire({
                    title: 'Hapus Komentar?',
                    text: "Komentar yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#c2a25d',
                    cancelButtonColor: '#dc3545',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.call('deleteComment', event.commentId);
                    }
                });
            });

            // Event ketika komentar berhasil ditambahkan
            Livewire.on('comment-added', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Komentar berhasil ditambahkan',
                    showConfirmButton: false,
                    timer: 1500,
                    toast: true,
                    position: 'top-end'
                });
            });

            // Event ketika komentar berhasil dihapus
            Livewire.on('comment-deleted', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Terhapus!',
                    text: 'Komentar berhasil dihapus',
                    showConfirmButton: false,
                    timer: 1500,
                    toast: true,
                    position: 'top-end'
                });
            });

            // Event ketika komentar tidak ditemukan
            Livewire.on('comment-not-found', () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Komentar tidak ditemukan',
                    showConfirmButton: false,
                    timer: 1500,
                    toast: true,
                    position: 'top-end'
                });
            });

            // Event ketika tidak ada akses hapus
            Livewire.on('comment-unauthorized', () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Diizinkan!',
                    text: 'Anda tidak memiliki akses untuk menghapus komentar ini',
                    showConfirmButton: false,
                    timer: 1500,
                    toast: true,
                    position: 'top-end'
                });
            });

            // Event ketika terjadi error
            Livewire.on('comment-error', (event) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: event.message || 'Terjadi kesalahan',
                    showConfirmButton: true
                });
            });
        });

        (function() {
            if (!window.chatbase || window.chatbase("getState") !== "initialized") {
                window.chatbase = (...arguments) => {
                    if (!window.chatbase.q) {
                        window.chatbase.q = []
                    }
                    window.chatbase.q.push(arguments)
                };
                window.chatbase = new Proxy(window.chatbase, {
                    get(target, prop) {
                        if (prop === "q") {
                            return target.q
                        }
                        return (...args) => target(prop, ...args)
                    }
                })
            }

            const onLoad = function() {
                const script = document.createElement("script");
                script.src = "https://www.chatbase.co/embed.min.js";
                script.id = "ZcSQVrjpC3O0zMCgjJTpt"; // CHATBOT ID KAMU
                script.domain = window.location.hostname;
                document.body.appendChild(script);
            };

            if (document.readyState === "complete") {
                onLoad();
            } else {
                window.addEventListener("load", onLoad);
            }
        })();
    </script>
@endscript

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

    /* Document Actions */
    .document-actions {
        flex-shrink: 0;
    }

    .btn-comment {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: linear-gradient(135deg, #d4af37, #c2a25d);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-comment:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(194, 162, 93, 0.4);
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
    }

    .btn-download {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-download:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(194, 162, 93, 0.4);
    }

    /* Modal Bootstrap Customization */
    .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .modal-header.bg-golden {
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        border-radius: 16px 16px 0 0;
        padding: 20px 25px;
        border: none;
    }

    .modal-header .modal-title {
        font-weight: 700;
        font-size: 1.25rem;
    }

    .modal-body {
        padding: 25px;
        max-height: 70vh;
    }

    /* Form Komentar */
    .add-comment-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        border: 2px solid #e9ecef;
    }

    .add-comment-section h6 {
        color: #333;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #c2a25d;
        box-shadow: 0 0 0 3px rgba(194, 162, 93, 0.1);
    }

    .btn-golden {
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-golden:hover {
        background: linear-gradient(135deg, #d4af37, #c2a25d);
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(194, 162, 93, 0.4);
        color: white;
    }

    .bg-golden {
        background: linear-gradient(135deg, #c2a25d, #a88a4d);
    }

    .badge.bg-golden {
        background: linear-gradient(135deg, #c2a25d, #a88a4d) !important;
    }

    /* Daftar Komentar */
    .comments-list h6 {
        color: #333;
        font-weight: 700;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .comment-item {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px;
        transition: all 0.3s ease;
    }

    .comment-item:hover {
        border-color: #c2a25d;
        box-shadow: 0 4px 12px rgba(194, 162, 93, 0.1);
    }

    .user-avatar-comment {
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

    .comment-content {
        background: #f8f9fa;
        padding: 12px 15px;
        border-radius: 8px;
        color: #555;
        line-height: 1.6;
        word-wrap: break-word;
        margin-top: 8px;
    }

    /* SweetAlert Customization */
    .swal2-confirm {
        background: linear-gradient(135deg, #c2a25d, #a88a4d) !important;
    }

    .division-logo-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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
        overflow: hidden;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .document-actions {
            flex-direction: column;
            width: 100%;
            margin-top: 10px;
        }

        .btn-comment,
        .btn-download {
            width: 100%;
            justify-content: center;
        }

        .document-item {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .document-info {
            margin-bottom: 10px;
            width: 100%;
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

        .modal-body {
            padding: 15px;
        }

        .add-comment-section {
            padding: 15px;
        }
    }

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
</style>
