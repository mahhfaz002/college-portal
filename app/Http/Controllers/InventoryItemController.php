<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    // READ: View all items
    public function index()
    {
        $items = InventoryItem::all();
        return view('inventory.index', compact('items'));
    }

    // CREATE: Show form
    public function create()
    {
        return view('inventory.create');
    }

    // CREATE: Save item
    public function store(Request $request)
    {
        // Only whitelisted fields — never $request->all(): college_id is stamped
        // by the BelongsToCollege hook so it can't be spoofed to another tenant.
        $data = $request->validate([
            'item_name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'status' => 'required|string|max:50',
            'location' => 'required|string|max:255',
        ]);

        InventoryItem::create($data);

        return redirect()->route('inventory.index')->with('success', 'Item added successfully!');
    }

    // UPDATE: Show form
    public function edit(InventoryItem $item)
    {
        return view('inventory.edit', compact('item'));
    }

    // UPDATE: Save changes
    public function update(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'item_name' => 'required|string|max:255',
            'category'  => 'nullable|string|max:255',
            'quantity'  => 'required|integer|min:0',
            'status'    => 'nullable|string|max:50',
            'location'  => 'nullable|string|max:255',
        ]);

        $item->update(array_filter($data, fn ($v) => $v !== null));

        return redirect()->route('inventory.index')->with('success', 'Item updated successfully!');
    }

    // DELETE: Remove item
    public function destroy(InventoryItem $item)
    {
        $item->delete();
        return redirect()->route('inventory.index')->with('success', 'Item deleted successfully!');
    }
}
