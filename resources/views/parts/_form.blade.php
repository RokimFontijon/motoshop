{{-- Shared by create and edit (admin only). Needs $part. --}}
<div class="form-grid">
    <div>
        <label for="part_code">Part code</label>
        <input type="text" id="part_code" name="part_code" value="{{ old('part_code', $part->part_code) }}"
               maxlength="30" required @class(['invalid' => $errors->has('part_code')])>
        @error('part_code') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $part->name) }}"
               maxlength="120" required @class(['invalid' => $errors->has('name')])>
        @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="unit_price">Unit price (&#8369;)</label>
        <input type="number" id="unit_price" name="unit_price" min="0" step="0.01"
               value="{{ old('unit_price', $part->unit_price) }}" required @class(['invalid' => $errors->has('unit_price')])>
        <span class="hint">A price change does not change old job orders. They keep the price at that time.</span>
        @error('unit_price') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="stock_qty">Stock on hand</label>
        <input type="number" id="stock_qty" name="stock_qty" min="0"
               value="{{ old('stock_qty', $part->stock_qty) }}" required @class(['invalid' => $errors->has('stock_qty')])>
        <span class="hint">Use this to record a delivery. Parts used in job orders are subtracted automatically.</span>
        @error('stock_qty') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="reorder_level">Reorder level</label>
        <input type="number" id="reorder_level" name="reorder_level" min="0"
               value="{{ old('reorder_level', $part->reorder_level) }}" required @class(['invalid' => $errors->has('reorder_level')])>
        <span class="hint">A low-stock alert shows when the stock is at or below this number.</span>
        @error('reorder_level') <span class="field-error">{{ $message }}</span> @enderror
    </div>
</div>
