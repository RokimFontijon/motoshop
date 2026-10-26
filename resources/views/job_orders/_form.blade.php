{{-- Shared by create and edit. Needs $jobOrder, $selectedServices and the lists from JobOrderController::formData(). --}}
@php($isAdmin = auth()->user()->isAdmin())
<div class="form-grid">
    <div class="full">
        <label for="motorcycle_id">Motorcycle</label>
        <select id="motorcycle_id" name="motorcycle_id" required @class(['invalid' => $errors->has('motorcycle_id')])>
            <option value="">Choose a motorcycle</option>
            @foreach ($motorcycles as $motorcycle)
                <option value="{{ $motorcycle->id }}" @selected(old('motorcycle_id', $jobOrder->motorcycle_id) == $motorcycle->id)>
                    {{ $motorcycle->plate_number }} &middot; {{ $motorcycle->motorcycleModel->label() }} &middot; {{ $motorcycle->customer->full_name }}
                </option>
            @endforeach
        </select>
        @error('motorcycle_id') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="service_date">Service date</label>
        <input type="date" id="service_date" name="service_date" max="{{ now()->toDateString() }}"
               value="{{ old('service_date', $jobOrder->service_date?->format('Y-m-d')) }}" required
               @class(['invalid' => $errors->has('service_date')])>
        @error('service_date') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="odometer_at_service">Odometer now (km)</label>
        <input type="number" id="odometer_at_service" name="odometer_at_service" min="0" max="999999"
               value="{{ old('odometer_at_service', $jobOrder->odometer_at_service) }}" required
               @class(['invalid' => $errors->has('odometer_at_service')])>
        <span class="hint">Read it from the motorcycle. The due dates are computed from this number.</span>
        @error('odometer_at_service') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="status">Status</label>
        <select id="status" name="status" required @class(['invalid' => $errors->has('status')])>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $jobOrder->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <span class="hint">Only completed or released work is used for service estimates.</span>
        @error('status') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="labor_cost">Labor cost (&#8369;)</label>
        <input type="number" id="labor_cost" name="labor_cost" min="0" step="0.01"
               value="{{ old('labor_cost', $jobOrder->labor_cost ?? 0) }}" required
               @class(['invalid' => $errors->has('labor_cost')])>
        @error('labor_cost') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    @if ($isAdmin)
        <div class="full">
            <label for="mechanic_id">Assigned mechanic</label>
            <select id="mechanic_id" name="mechanic_id" @class(['invalid' => $errors->has('mechanic_id')])>
                <option value="">Not assigned</option>
                @foreach ($mechanics as $mechanic)
                    <option value="{{ $mechanic->id }}" @selected(old('mechanic_id', $jobOrder->mechanic_id) == $mechanic->id)>
                        {{ $mechanic->name }} ({{ $mechanic->role->name }})
                    </option>
                @endforeach
            </select>
            @error('mechanic_id') <span class="field-error">{{ $message }}</span> @enderror
        </div>
    @endif

    <div class="full">
        <label for="complaint">Customer complaint / notes <span class="hint">(optional)</span></label>
        <textarea id="complaint" name="complaint" maxlength="1000" @class(['invalid' => $errors->has('complaint')])>{{ old('complaint', $jobOrder->complaint) }}</textarea>
        @error('complaint') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="full">
        <label>Services done</label>
        <div class="checks">
            @foreach ($serviceTypes as $serviceType)
                <label>
                    <input type="checkbox" name="service_type_ids[]" value="{{ $serviceType->id }}"
                           @checked(in_array($serviceType->id, array_map('intval', old('service_type_ids', $selectedServices))))>
                    {{ $serviceType->name }}
                </label>
            @endforeach
        </div>
        <span class="hint">Tick every service that was performed. The next due date of each one is estimated from this.</span>
        @error('service_type_ids') <span class="field-error">{{ $message }}</span> @enderror
    </div>
</div>
