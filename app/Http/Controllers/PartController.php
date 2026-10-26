<?php

namespace App\Http\Controllers;

use App\Models\Part;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartController extends Controller
{
    private function rules(?Part $part = null): array
    {
        return [
            'part_code' => ['required', 'string', 'max:30', Rule::unique('parts', 'part_code')->ignore($part?->id)],
            'name' => ['required', 'string', 'max:120'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'stock_qty' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /** Everybody who is logged in can look at the inventory. */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $lowOnly = $request->boolean('low');

        $parts = Part::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('part_code', 'ilike', "%{$search}%");
                });
            })
            ->when($lowOnly, fn ($query) => $query->lowStock())
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('parts.index', compact('parts', 'search', 'lowOnly'));
    }

    // The methods below are admin only (see routes/web.php).

    public function create()
    {
        return view('parts.create', ['part' => new Part(['stock_qty' => 0, 'reorder_level' => 5])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Part::create($request->validate($this->rules()));

        return redirect()->route('parts.index')->with('status', 'Part added to the inventory.');
    }

    public function edit(Part $part)
    {
        return view('parts.edit', compact('part'));
    }

    public function update(Request $request, Part $part): RedirectResponse
    {
        $part->update($request->validate($this->rules($part)));

        return redirect()->route('parts.index')->with('status', 'Part updated.');
    }

    public function destroy(Part $part): RedirectResponse
    {
        if ($part->jobOrderParts()->exists()) {
            return back()->with('error', 'This part was used in job orders, so it cannot be deleted.');
        }

        $part->delete();

        return redirect()->route('parts.index')->with('status', 'Part deleted.');
    }
}
