{{-- Shared by create and edit. Needs $motorcycle, $customers and $models. --}}
<div class="form-grid">
    <div>
        <label for="customer_id">Owner</label>
        <select id="customer_id" name="customer_id" required @class(['invalid' => $errors->has('customer_id')])>
            <option value="">Choose a customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id', $motorcycle->customer_id) == $customer->id)>
                    {{ $customer->full_name }}
                </option>
            @endforeach
        </select>
        @error('customer_id') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="motorcycle_model_id">Model</label>
        <select id="motorcycle_model_id" name="motorcycle_model_id" required @class(['invalid' => $errors->has('motorcycle_model_id')])>
            <option value="">Choose a model</option>
            @foreach ($models as $model)
                <option value="{{ $model->id }}" @selected(old('motorcycle_model_id', $motorcycle->motorcycle_model_id) == $model->id)>
                    {{ $model->label() }}
                </option>
            @endforeach
        </select>
        @error('motorcycle_model_id') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="plate_number">Plate number</label>
        <input type="text" id="plate_number" name="plate_number" value="{{ old('plate_number', $motorcycle->plate_number) }}"
               maxlength="15" required style="text-transform: uppercase;" @class(['invalid' => $errors->has('plate_number')])>
        @error('plate_number') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="current_odometer">Current odometer (km)</label>
        <input type="number" id="current_odometer" name="current_odometer" min="0" max="999999"
               value="{{ old('current_odometer', $motorcycle->current_odometer ?? 0) }}" required
               @class(['invalid' => $errors->has('current_odometer')])>
        @error('current_odometer') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="year">Year <span class="hint">(optional)</span></label>
        <input type="number" id="year" name="year" min="1980" max="2100" value="{{ old('year', $motorcycle->year) }}"
               @class(['invalid' => $errors->has('year')])>
        @error('year') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="color">Color <span class="hint">(optional)</span></label>
        <input type="text" id="color" name="color" value="{{ old('color', $motorcycle->color) }}" maxlength="30"
               @class(['invalid' => $errors->has('color')])>
        @error('color') <span class="field-error">{{ $message }}</span> @enderror
    </div>
</div>
