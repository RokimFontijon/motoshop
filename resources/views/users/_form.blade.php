{{-- Shared by create and edit (admin only). Needs $user and $roles. --}}
<div class="form-grid">
    <div>
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
               maxlength="100" required @class(['invalid' => $errors->has('name')])>
        @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
               maxlength="255" required @class(['invalid' => $errors->has('email')])>
        @error('email') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="role_id">Role</label>
        <select id="role_id" name="role_id" required @class(['invalid' => $errors->has('role_id')])>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ ucfirst($role->name) }}</option>
            @endforeach
        </select>
        <span class="hint">{{ $roles->pluck('description', 'name')->map(fn ($d, $n) => ucfirst($n).': '.$d)->join(' ') }}</span>
        @error('role_id') <span class="field-error">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="password">Password @if ($user->exists) <span class="hint">(leave empty to keep the current one)</span> @endif</label>
        <input type="password" id="password" name="password" minlength="8" autocomplete="new-password"
               @required(! $user->exists) @class(['invalid' => $errors->has('password')])>
        <span class="hint">At least 8 characters.</span>
        @error('password') <span class="field-error">{{ $message }}</span> @enderror
    </div>
</div>
