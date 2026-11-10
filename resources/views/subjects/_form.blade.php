<div class="form-grid">
    <div class="form-group">
        <label for="code">Subject Code <span class="req">*</span></label>
        <input id="code" name="code" value="{{ old('code', $subject->code ?? '') }}" class="form-control no-icon @error('code') is-invalid @enderror" required maxlength="255">
        @error('code') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="name">Subject Name <span class="req">*</span></label>
        <input id="name" name="name" value="{{ old('name', $subject->name ?? '') }}" class="form-control no-icon @error('name') is-invalid @enderror" required maxlength="255">
        @error('name') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="units">Units <span class="req">*</span></label>
        <input id="units" name="units" type="number" min="0" max="255" step="1" value="{{ old('units', $subject->units ?? '') }}" class="form-control no-icon @error('units') is-invalid @enderror" required>
        @error('units') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group full">
        <label for="description">Description</label>
        <textarea id="description" name="description" class="form-control no-icon @error('description') is-invalid @enderror" rows="3">{{ old('description', $subject->description ?? '') }}</textarea>
        @error('description') <p class="error-msg">{{ $message }}</p> @enderror
    </div>
</div>

<div class="form-actions">
    <button type="submit" class="btn btn-primary">{{ isset($subject) ? 'Save Changes' : 'Create Subject' }}</button>
    <a href="{{ route('subjects.index') }}" class="btn btn-secondary">Cancel</a>
</div>
