<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Check if user is admin
    public function isAdmin()
    {
        return $this->role === 'admin' || $this->role === 'superadmin';
    }

    // Check if user is viewer
    public function isViewer()
    {
        return $this->role === 'viewer';
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // Relationship dengan divisions (many-to-many)
    public function divisions()
    {
        return $this->belongsToMany(Division::class, 'division_user')
            ->withTimestamps();
    }

    // Check if user has access to a specific division
    public function hasAccessToDivision($divisionId)
    {
        // Admin has access to all divisions
        if ($this->isAdmin()) {
            return true;
        }

        return $this->divisions()->where('division_id', $divisionId)->exists();
    }

    // Check if user has access to a document
    public function hasAccessToDocument(Document $document)
    {
        // Admin has access to all documents
        if ($this->isAdmin()) {
            return true;
        }

        return $this->hasAccessToDivision($document->division_id);
    }

    // Get accessible documents
    public function getAccessibleDocuments()
    {
        // Admin can access all documents
        if ($this->isAdmin()) {
            return Document::query();
        }

        // Viewer can only access documents from their allowed divisions
        $divisionIds = $this->divisions()->pluck('divisions.id');

        return Document::whereIn('division_id', $divisionIds);
    }
}
