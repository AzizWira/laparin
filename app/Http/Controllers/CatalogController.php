<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->query('q');
        $search = is_string($query) ? mb_substr(trim($query), 0, 80) : '';
        $tenants = Tenant::query()
            ->withCount('menuItems')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $pattern = '%'.$search.'%';
                    $query->whereLike('name', $pattern)
                        ->orWhereLike('category', $pattern)
                        ->orWhereHas('menuItems', fn (Builder $menu) => $menu->whereLike('name', $pattern));
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->appends($search === '' ? [] : ['q' => $search]);

        return view('catalog.index', compact('tenants', 'search'));
    }

    public function show(Tenant $tenant): View
    {
        $menu = $tenant->menuItems()->with('todayStock')->orderBy('name')->paginate(12);

        return view('catalog.show', compact('tenant', 'menu'));
    }

    public function photo(string $filename): StreamedResponse
    {
        $disk = Storage::disk('public');
        $path = 'catalog/'.$filename;
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
