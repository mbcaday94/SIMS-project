<div class="student-form-intro">
    <div class="photo-upload">
        <div class="photo-preview" id="photo-preview">
            @if (isset($student) && $student->photo_path)
                <img src="{{ asset('storage/'.$student->photo_path) }}" alt="Current student photo">
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"></path>
                </svg>
            @endif
        </div>
        <div class="photo-copy">
            <strong>Student photo</strong>
            <span>JPG, PNG, or WebP · up to 2 MB</span>
            <label for="photo" class="photo-select">Choose image</label>
            <span id="photo-file-name" class="photo-file-name">No image selected</span>
            <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="photo-input @error('photo') is-invalid @enderror">
            @error('photo') <p class="error-msg">{{ $message }}</p> @enderror
        </div>
    </div>
    <div class="form-required-note"><span class="req">*</span> Required fields</div>
</div>

<div class="form-grid">
    <div class="form-group">
        <label for="student_number">Student Number <span class="req">*</span></label>
        <input id="student_number" name="student_number" value="{{ old('student_number', $student->student_number ?? '') }}" class="form-control no-icon @error('student_number') is-invalid @enderror" required maxlength="50">
        @error('student_number') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="email">Email <span class="req">*</span></label>
        <input id="email" name="email" type="email" value="{{ old('email', $student->email ?? '') }}" class="form-control no-icon @error('email') is-invalid @enderror" required maxlength="255">
        @error('email') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="first_name">First Name <span class="req">*</span></label>
        <input id="first_name" name="first_name" value="{{ old('first_name', $student->first_name ?? '') }}" class="form-control no-icon @error('first_name') is-invalid @enderror" required maxlength="100">
        @error('first_name') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="last_name">Last Name <span class="req">*</span></label>
        <input id="last_name" name="last_name" value="{{ old('last_name', $student->last_name ?? '') }}" class="form-control no-icon @error('last_name') is-invalid @enderror" required maxlength="100">
        @error('last_name') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="course">Course <span class="req">*</span></label>
        <select id="course" name="course" class="form-control no-icon @error('course') is-invalid @enderror" required>
            <option value="">Select a course</option>
            @foreach (\App\Models\Student::COURSES as $course)
                <option value="{{ $course }}" @selected(old('course', $student->course ?? '') === $course)>{{ $course }}</option>
            @endforeach
        </select>
        @error('course') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="year_level">Year Level <span class="req">*</span></label>
        <input id="year_level" name="year_level" value="{{ old('year_level', $student->year_level ?? '') }}" class="form-control no-icon @error('year_level') is-invalid @enderror" required maxlength="50" placeholder="e.g. 1st Year">
        @error('year_level') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="semester">Semester <span class="req">*</span></label>
        <select id="semester" name="semester" class="form-control no-icon @error('semester') is-invalid @enderror" required>
            <option value="">Select a semester</option>
            @foreach (\App\Models\Student::SEMESTERS as $semester)
                <option value="{{ $semester }}" @selected(old('semester', $student->semester ?? '') === $semester)>{{ $semester }}</option>
            @endforeach
        </select>
        @error('semester') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

</div>

<div class="form-actions">
    <button type="submit" class="btn btn-primary">{{ isset($student) ? 'Save Changes' : 'Create Student' }}</button>
    <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
</div>

<script>
    document.getElementById('photo')?.addEventListener('change', function (event) {
        const file = event.target.files?.[0];
        if (!file) return;

        const preview = document.getElementById('photo-preview');
        const image = document.createElement('img');
        image.alt = 'Selected student photo preview';
        const previewUrl = URL.createObjectURL(file);
        image.src = previewUrl;
        image.onload = () => URL.revokeObjectURL(previewUrl);
        preview.replaceChildren(image);
        document.getElementById('photo-file-name').textContent = file.name;
    });
</script>
