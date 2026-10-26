{{-- Shared by create and edit. Needs $customer. --}}
<div class="form-grid">
    <div class="full">
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $customer->full_name) }}"
               maxlength="120" required @class(['invalid' => $errors->has('full_name')])>
        @error('full_name') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="phone">Phone <span class="hint">(optional)</span></label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}"
               maxlength="30" @class(['invalid' => $errors->has('phone')])>
        @error('phone') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="address">Address <span class="hint">(optional)</span></label>
        <input type="text" id="address" name="address" value="{{ old('address', $customer->address) }}"
               maxlength="255" @class(['invalid' => $errors->has('address')])>
        @error('address') <span class="field-error">{{ $message }}</span> @enderror
    </div>
</div>
